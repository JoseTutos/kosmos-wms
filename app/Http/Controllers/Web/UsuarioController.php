<?php

namespace App\Http\Controllers\Web;

use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\Usuario\StoreUsuarioRequest;
use App\Http\Requests\Usuario\UpdateUsuarioRequest;
use App\Models\User;
use App\Services\Seguridad\AutenticacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
/**
 * Kosmos WMS · Capa de Presentación (Controlador Web) · CUS-004 Administrar Usuarios y Roles
 *
 * Actor: ADMIN_SISTEMA (middleware 'role:ADMIN_SISTEMA' en la ruta).
 *
 * Cumple:
 *   - CUS-004: alta, edición, bloqueo, desbloqueo e inactivación; un rol por usuario.
 *   - RE-7: segregación; ADMIN_SISTEMA gestiona usuarios pero no maestros.
 *   - SEG02: sin borrado físico, porque usuario_id está en movimientos,
 *     legalizaciones, ajustes y bitácora. Borrarlo rompería la trazabilidad.
 *
 * Por qué no hay UsuarioService:
 *   Toca una sola tabla, no genera movimientos ni consecutivos y solo se usa desde
 *   la web. Lo que sí se comparte (contraseña temporal) ya está en AutenticacionService.
 *
 * Restricciones:
 *   - Un ADMIN_SISTEMA no puede bloquearse, inactivarse ni cambiarse el rol a sí mismo,
 *     y siempre debe quedar al menos uno ACTIVO (protegerAdministrador).
 *   - Bloquear, inactivar o cambiar el rol revoca los tokens de la app.
 *   - La contraseña temporal se muestra UNA sola vez (flash) y nunca se registra.
 *   - Los cambios de nombre, email, rol y estado van a la bitácora mediante el trait
 *     LogsActivity del modelo User (CUS-018).
 */
class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $usuarios = User::query()
            ->when($request->string('q')->trim()->substr(0, 100)->value(), fn ($q, $txt) => $q->where(fn ($w) => $w
                ->where('nombre', 'ilike', "%{$txt}%")     // ilike: búsqueda sin distinguir mayúsculas (PostgreSQL)
                ->orWhere('email', 'ilike', "%{$txt}%")))
            ->when($request->rol, fn ($q, $rol) => $q->where('rol', $rol))
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))
            ->orderBy('nombre')
            ->paginate(20)                                 // rendimiento: nunca la tabla completa
            ->withQueryString();

        return view('usuarios.index', [
            'usuarios' => $usuarios,
            'roles'    => Rol::cases(),
            'estados'  => EstadoUsuario::cases(),
        ]);
    }

    public function create(): View
    {
        return view('usuarios.create', ['roles' => Rol::cases()]);
    }

    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        // 1. Creamos la instancia con los datos validados (nombre, email, rol)
        $usuario = new User($request->validated());

        // 2. Asignamos la contraseña fija desde el .env y forzamos el cambio
        $usuario->password = Hash::make(env('DEFAULT_USER_PASSWORD', 'Kosmos2024!'));
        $usuario->debe_cambiar_password = true;

        // 3. Guardamos en la base de datos (¡Importante!)
        $usuario->save();

        // 4. Redirigimos con un mensaje de éxito simple (YA NO enviamos 'password_temporal')
        return redirect()->route('usuarios.index')
            ->with('ok', "Usuario {$usuario->email} creado exitosamente. Deberá usar la contraseña por defecto y cambiarla en su primer ingreso.");
    }

    //METODO STORE ANULADO POR CONFLICTO
    /*public function store(StoreUsuarioRequest $request, AutenticacionService $auth): RedirectResponse
    {
        $usuario = new User($request->validated()); // solo nombre, email, rol ($fillable)
        // estado ACTIVO e intentos 0 vienen de $attributes del modelo

        //$temporal = $auth->asignarTemporal($usuario); // guarda el usuario con clave temporal (hash)

        // 2. Asignamos la contraseña fija desde el .env y forzamos el cambio
        $usuario->password = Hash::make(env('DEFAULT_USER_PASSWORD', 'Kosmos2024!'));
        $usuario->debe_cambiar_password = true;

        return redirect()->route('usuarios.index')
            ->with('ok', "Usuario {$usuario->email} creado.")
            ->with('password_temporal', $temporal);
    }*/

    public function edit(User $usuario): View
    {
        return view('usuarios.edit', ['usuario' => $usuario, 'roles' => Rol::cases()]);
    }

    public function update(UpdateUsuarioRequest $request, User $usuario): RedirectResponse
    {
        $cambiaRol = Rol::from($request->rol) !== $usuario->rol;

        if ($cambiaRol) {
            $this->protegerAdministrador($request->user(), $usuario, 'cambiar el rol de');
        }

        $usuario->update($request->validated());

        if ($cambiaRol) {
            $usuario->tokens()->delete(); // la app no sigue operando con permisos del rol anterior
        }

        return redirect()->route('usuarios.index')->with('ok', 'Usuario actualizado.');
    }

    public function bloquear(Request $request, User $usuario): RedirectResponse
    {
        $this->protegerAdministrador($request->user(), $usuario, 'bloquear a');

        $usuario->bloquear();               // dominio
        $this->guardarYRevocar($usuario);

        return back()->with('ok', 'Usuario bloqueado.');
    }

    public function inactivar(Request $request, User $usuario): RedirectResponse
    {
        $this->protegerAdministrador($request->user(), $usuario, 'inactivar a');

        $usuario->inactivar();              // dominio
        $this->guardarYRevocar($usuario);

        return back()->with('ok', 'Usuario inactivado.');
    }

    /** CUS-004: desbloquear (BLOQUEADO) o reactivar (INACTIVO). Reinicia los intentos fallidos. */
    public function activar(User $usuario): RedirectResponse
    {
        $usuario->estado === EstadoUsuario::BLOQUEADO
            ? $usuario->desbloquear()       // dominio
            : $usuario->activar();
        $usuario->save();

        return back()->with('ok', 'Usuario activado.');
    }

    /** Usuario que olvidó su clave: nueva temporal + cambio obligatorio en el siguiente ingreso. */
    /*public function resetPassword(User $usuario, AutenticacionService $auth): RedirectResponse
    {
        $temporal = $auth->asignarTemporal($usuario);

        return back()
            ->with('ok', "Contraseña temporal generada para {$usuario->email}.")
            ->with('password_temporal', $temporal);
    }*/
        public function resetPassword(User $usuario, AutenticacionService $auth): RedirectResponse
    {
        // El servicio se encarga de hashear, guardar, borrar tokens y auditar
        $passwordPlano = $auth->restablecerConClavePorDefecto($usuario);

        return back()
            ->with('ok', "Contraseña de {$usuario->email} restablecida exitosamente.")
            ->with('password_temporal', $passwordPlano); // Solo si tu UI necesita mostrarla al admin
    }
    /** Los métodos de dominio no persisten: aquí se guarda y se revoca la app. */
    private function guardarYRevocar(User $usuario): void
    {
        $usuario->save();
        $usuario->tokens()->delete(); // la sesión web la cierra el middleware cuenta.activa
    }

        public function destroy(Request $request, User $usuario): RedirectResponse
    {
        $this->protegerAdministrador($request->user(), $usuario, 'eliminar a');

        if ($usuario->estado !== EstadoUsuario::BLOQUEADO) {
            throw ValidationException::withMessages(['usuario' => 'Solo se pueden eliminar usuarios bloqueados.']);
        }

        if ($usuario->tieneOperaciones()) {
            throw ValidationException::withMessages(['usuario' => 'Este usuario tiene operaciones registradas. Por trazabilidad no se puede eliminar: déjelo bloqueado o inactívelo.']);
        }

        // Confirmación escrita: evita eliminar al usuario equivocado con un clic.
        if (mb_strtolower(trim((string) $request->input('confirmacion'))) !== $usuario->email) {
            throw ValidationException::withMessages(['confirmacion' => 'El correo escrito no coincide con el del usuario.']);
        }

        $email = $usuario->email;

        try {
            DB::transaction(function () use ($usuario) {
                $usuario->tokens()->delete();
                $usuario->delete();
            });
        } catch (QueryException $e) {
            // Última barrera: una FK que no contemplamos (código PostgreSQL 23503).
            if ($e->getCode() === '23503') {
                throw ValidationException::withMessages(['usuario' => 'Este usuario está referenciado en otros registros y no se puede eliminar. Déjelo bloqueado o inactívelo.']);
            }
            throw $e;
        }

        return redirect()->route('usuarios.index')->with('ok', "Usuario {$email} eliminado.");
    }

    /**
     * Protección del administrador (decisión de diseño):
     * no se afecta a sí mismo y siempre queda al menos un ADMIN_SISTEMA ACTIVO,
     * para que el sistema nunca quede sin quién desbloquee usuarios.
     */
    private function protegerAdministrador(User $actor, User $objetivo, string $accion): void
    {
        if ($actor->is($objetivo)) {
            throw ValidationException::withMessages(['usuario' => "No puede {$accion} su propio usuario."]);
        }

        $esUltimoAdmin = $objetivo->tieneRol(Rol::ADMIN_SISTEMA)
            && $objetivo->estaActivo()
            && User::where('rol', Rol::ADMIN_SISTEMA)->where('estado', EstadoUsuario::ACTIVO)->count() === 1;

        if ($esUltimoAdmin) {
            throw ValidationException::withMessages(['usuario' => 'Debe quedar al menos un Administrador del Sistema activo.']);
        }
    }
}
