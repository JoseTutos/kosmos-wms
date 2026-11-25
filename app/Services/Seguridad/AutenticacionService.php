<?php

namespace App\Services\Seguridad;

use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Enums\CodigoError;
use App\Exceptions\ReglaNegocioException;
use Laravel\Sanctum\NewAccessToken;

/**
 * Kosmos WMS · Capa de Aplicación (Services) · Módulo Seguridad
 *
 * Por qué es un servicio (An. Preliminar §3.3, conclusión):
 *   La misma regla de autenticación se usa desde la web (sesión) y desde la
 *   API de la app Flutter (token Sanctum). Si se duplicara en los dos
 *   controladores, tarde o temprano quedarían distintas.
 *
 * Reparto de responsabilidades:
 *   - Modelo User (dominio): contador de intentos y bloqueo al máximo
 *     (autenticar, registrarIntentoFallido, bloquear). El máximo sale de
 *     configuraciones: 'max_intentos_login'.
 *   - Este servicio (aplicación): transacción con bloqueo de fila, orden de los
 *     mensajes, control de canal por rol, revocación de tokens y bitácora.
 *
 * Cumple:
 *   - CUS-004: un rol por usuario, bcrypt y bloqueo tras N intentos fallidos.
 *   - RS-19: seis roles excluyentes; cada canal admite solo algunos roles.
 *   - SEG01: autenticación antes de cualquier operación.
 *   - SEG02 / CUS-018: login, fallos, bloqueos y cambios de clave quedan en la bitácora.
 *   - Decisión de diseño: contraseña temporal asignada por ADMIN_SISTEMA y
 *     cambio obligatorio en el primer ingreso (aplica a todos los roles).
 *
 * Restricciones de seguridad:
 *   - El mismo mensaje y un tiempo de respuesta similar si el correo no existe
 *     o si la clave está mal: no se revela qué usuarios existen.
 *   - El estado (bloqueado/inactivo) solo se informa si la clave es correcta.
 *   - El contador se actualiza con bloqueo de fila: dos intentos simultáneos no se pierden.
 *   - Las contraseñas nunca se guardan ni se registran en texto plano.
 */
class AutenticacionService
{
    private const MSG_CREDENCIALES = 'Credenciales inválidas.';

    /**
     * Hash bcrypt (cost 12) de un valor aleatorio. Se compara cuando el correo
     * no existe, para que esa respuesta tarde lo mismo que una clave incorrecta.
     */
    private const HASH_FICTICIO = '$2y$12$TpIjg.WTzhMRNA7Gp0bM6.W74GPYJ9jDpJL6fu6Ed3own/afzWyGq';

    /**
     * Roles admitidos por canal (RS-19 / Plan M2 §2.1):
     *   - api: la app Flutter es solo del OPERARIO.
     *   - web: todos menos el OPERARIO, que no tiene pantallas web en el MVP (RS-74).
     */
    private const ROLES_POR_CANAL = [
        'api' => [Rol::OPERARIO],
        'web' => [Rol::ADMIN_SISTEMA, Rol::ADMIN_MAESTROS, Rol::SUPERVISOR, Rol::ANALISTA, Rol::GERENTE],
    ];

    /**
     * Valida credenciales, estado y canal. Devuelve el usuario autenticado
     * o lanza ValidationException (web: back()->withErrors · API: 422 JSON).
     *
     * @param 'web'|'api' $canal
     */
 
    public function autenticar(string $email, string $password, string $canal): User
    {
        $existe = User::where('email', $email)->first();

        if (! $existe) {
            Hash::check($password, self::HASH_FICTICIO);
            $this->fallar(CodigoError::CREDENCIALES_INVALIDAS);
        }

        [$user, $claveCorrecta] = DB::transaction(function () use ($existe, $password, $canal) {
            $u = User::whereKey($existe->getKey())->lockForUpdate()->first();
            $estabaBloqueado = $u->estado === EstadoUsuario::BLOQUEADO;

            $ok = $u->autenticar($password);   // dominio: contador y bloqueo
            $u->save();                         // solo escribe si hubo cambios

            if (! $ok && ! $estabaBloqueado && $u->estado === EstadoUsuario::BLOQUEADO) {
                $u->tokens()->delete();
                $this->auditar($u, 'usuario bloqueado por intentos fallidos', ['canal' => $canal]);
            } elseif (! $ok && $u->wasChanged('intentos_fallidos')) {
                $this->auditar($u, 'login fallido', ['canal' => $canal, 'intentos' => $u->intentos_fallidos]);
            }

            return [$u, $ok];
        });

        if (! $claveCorrecta) {
            $this->fallar(CodigoError::CREDENCIALES_INVALIDAS);
        }

        // Clave correcta: ahora sí se puede decir por qué no entra.
        if ($user->estado === EstadoUsuario::BLOQUEADO) {
            $this->fallar(CodigoError::USUARIO_BLOQUEADO);
        }
        if ($user->estado === EstadoUsuario::INACTIVO) {
            $this->fallar(CodigoError::USUARIO_INACTIVO);
        }

        if (! $user->tieneRol(...self::ROLES_POR_CANAL[$canal])) {
            $this->fallar(CodigoError::ROL_SIN_ACCESO, $canal === 'api'
                ? 'Su rol no tiene acceso a la aplicación móvil.'
                : 'Su rol solo tiene acceso desde la aplicación móvil.');
        }

        $this->auditar($user, 'login', ['canal' => $canal], causante: $user);

        return $user;
    }
    /**
     * Cambio de contraseña por el propio usuario (primer ingreso o voluntario).
     * Revoca los demás tokens de la app: si la clave estaba comprometida,
     * una sesión robada deja de servir.
     */
    public function cambiarPassword(User $user, string $actual, string $nueva, ?int $conservarTokenId = null): void
    {
        if (! Hash::check($actual, $user->password)) {
            throw ValidationException::withMessages(['password_actual' => 'La contraseña actual no es correcta.']);
        }

        $user->forceFill([
            'password'              => $nueva, // cast 'hashed' del modelo => bcrypt
            'debe_cambiar_password' => false,
        ])->save();

        $tokens = $user->tokens();
        if ($conservarTokenId) {
            $tokens->where('id', '!=', $conservarTokenId);
        }
        $tokens->delete();

        $this->auditar($user, 'cambio de contraseña', causante: $user);
    }

    /**
     * Asigna una contraseña temporal (alta de usuario o restablecimiento por ADMIN_SISTEMA).
     * Devuelve el texto plano para mostrarlo UNA sola vez al administrador, que lo
     * entrega al usuario de forma interna y confidencial (no hay correo en el MVP).
     */
    /*public function asignarTemporal(User $user): string
    {
        $temporal = Str::password(10, symbols: false); // generador criptográficamente seguro

        $user->forceFill([
            'password'              => $temporal,
            'debe_cambiar_password' => true,
            'intentos_fallidos'     => 0,
        ])->save();

        $user->tokens()->delete(); // la clave anterior deja de servir también en la app

        $this->auditar($user, 'contraseña temporal asignada'); // causante = admin autenticado

        return $temporal;
    }*/
        public function restablecerConClavePorDefecto(User $user): string
    {
        // 1. Obtenemos la contraseña fija. 
        // NOTA ACADÉMICA: En producción, es mejor usar config('services.default_password') 
        // que env() directo, ya que env() devuelve null si el config está cacheado.
        $passwordPlano = (string) config('kosmos.password_por_defecto');

        // 2. ¡CRÍTICO! Encriptamos la contraseña antes de guardarla
        $passwordHasheada = Hash::make($passwordPlano);

        // 3. Actualizamos el modelo (manteniendo tus excelentes prácticas de seguridad)
        $user->forceFill([
            'password'              => $passwordHasheada,
            'debe_cambiar_password' => true,
            'intentos_fallidos'     => 0, // Reiniciamos intentos por si estaba bloqueado
        ])->save();

        // 4. Invalidamos sesiones/token anteriores (Excelente práctica de seguridad)
        $user->tokens()->delete(); 

        // 5. Registro de auditoría
        $this->auditar($user, 'contraseña restablecida a valor por defecto por administrador');

        // 6. Retornamos la contraseña en texto plano para que el controlador pueda 
        // mostrarla en la vista si la política de la empresa lo requiere.
        return $passwordPlano;
    }

    /** CUS-018 / SEG02: registra el evento con IP en la bitácora (spatie/laravel-activitylog). */
    private function auditar(User $sujeto, string $evento, array $props = [], ?User $causante = null): void
    {
        $log = activity('seguridad')->performedOn($sujeto)
            ->withProperties($props + ['ip' => request()->ip()]);

        if ($causante) {
            $log->causedBy($causante);
        }

        $log->log($evento);
    }

    /**
     * Web: back()->withErrors(['email' => ...]) (igual que antes).
     * API: 422 { mensaje, codigo } — la app distingue bloqueado / inactivo / rol por el código.
     */
    private function fallar(CodigoError $codigo, ?string $mensaje = null): never
    {
        throw new ReglaNegocioException($codigo, $mensaje, campo: 'email');
    }

    /** Un token por dispositivo: volver a entrar desde el mismo equipo reemplaza el anterior. */
    public function emitirToken(User $user, string $dispositivo): NewAccessToken
    {
        $user->tokens()->where('name', $dispositivo)->delete();

        return $user->createToken($dispositivo, ['*'], now()->addHours(config('kosmos.api.token_horas', 12)));
    }

    /** Logout de la app: revoca solo el token de este dispositivo. */
    public function cerrarSesionApi(User $user): void
    {
        $user->currentAccessToken()?->delete();

        $this->auditar($user, 'logout', ['canal' => 'api'], causante: $user);
    }
}
