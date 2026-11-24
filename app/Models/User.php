<?php

namespace App\Models;

use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Models\Concerns\TieneEstado;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
//use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Kosmos WMS · Capa de Dominio (Modelo) · Usuario «users» — Paquete Seguridad (CUS-004)
 *
 * Cumple:
 *   - CUS-004: un rol excluyente (RS-19), bcrypt y bloqueo tras N intentos fallidos.
 *   - An. §3.1.1: métodos de dominio del diagrama de clases (autenticar,
 *     registrarIntentoFallido, bloquear, desbloquear, tieneRol).
 *   - SEG02: relaciones de trazabilidad (movimientos, legalizaciones, ajustes, auditoría).
 *   - CUS-018: los cambios de nombre, email, rol y estado van a la bitácora (LogsActivity).
 *
 * Restricciones:
 *   - Los métodos de dominio NO hacen save(): los persiste el servicio o el controlador,
 *     dentro de su transacción.
 *   - La contraseña no es asignable en masa: se fija solo con forceFill desde
 *     AutenticacionService (temporal o cambio propio).
 *   - La tabla no tiene remember_token: el login no usa "recordarme".
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, /*LogsActivity*/ TieneEstado;

    /** Solo lo que llega validado desde Store/UpdateUsuarioRequest. */
    protected $fillable = ['nombre', 'email', 'rol'];

    protected $hidden = ['password'];

    /** Valores por defecto (coinciden con los DEFAULT de la BD). */
    protected $attributes = [
        'estado'                => 'ACTIVO',
        'intentos_fallidos'     => 0,
        'debe_cambiar_password' => true,
    ];

    protected function casts(): array
    {
        return [
            'password'              => 'hashed',   // bcrypt con BCRYPT_ROUNDS=12
            'rol'                   => Rol::class,
            'estado'                => EstadoUsuario::class,
            'intentos_fallidos'     => 'integer',
            'debe_cambiar_password' => 'boolean',
        ];
    }

    /** CUS-018: qué cambios del usuario van a la bitácora. Nunca la contraseña. */
    /*public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('usuarios')
            ->logOnly(['nombre', 'email', 'rol', 'estado'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }*/

    // ---------------- Relaciones (SEG02: trazabilidad) ----------------

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class, 'usuario_id');
    }

    public function legalizaciones(): HasMany
    {
        return $this->hasMany(Legalizacion::class, 'usuario_id');
    }

    public function ajustes(): HasMany
    {
        return $this->hasMany(Ajuste::class, 'usuario_id');
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(Auditoria::class, 'causer_id');
    }

    // ---------------- Dominio (CUS-004) ----------------

    /**
     * Valida la contraseña y actualiza el contador de intentos. El llamador hace save().
     *
     * No revisa el estado: AutenticacionService informa "bloqueado" o "inactivo"
     * solo DESPUÉS de una clave correcta, para no revelar el estado de cuentas ajenas.
     * Una cuenta que no está ACTIVA no acumula intentos.
     */
    public function autenticar(string $password): bool
    {
        if (Hash::check($password, $this->password)) {
            if ($this->estaActivo()) {
                $this->intentos_fallidos = 0; // un bloqueado conserva su contador hasta que lo desbloqueen
            }

            return true;
        }

        if ($this->estaActivo()) {
            $this->registrarIntentoFallido();
        }

        return false;
    }

    /** Bloquea al llegar al máximo. La BD limita intentos_fallidos a 0..5 (CHECK). */
    public function registrarIntentoFallido(): void
    {
        $maximo = min(5, (int) Configuracion::obtener('max_intentos_login', 5));

        $this->intentos_fallidos = min($this->intentos_fallidos + 1, 5);

        if ($this->intentos_fallidos >= $maximo) {
            $this->bloquear();
        }
    }

    public function bloquear(): void
    {
        if ($this->estado !== EstadoUsuario::BLOQUEADO) {
            $this->cambiarEstado(EstadoUsuario::BLOQUEADO);
        }
    }

    public function desbloquear(): void
    {
        $this->cambiarEstado(EstadoUsuario::ACTIVO);
        $this->intentos_fallidos = 0;
    }

    public function inactivar(): void
    {
        $this->cambiarEstado(EstadoUsuario::INACTIVO);
    }

    public function activar(): void
    {
        $this->cambiarEstado(EstadoUsuario::ACTIVO);
        $this->intentos_fallidos = 0;
    }

    public function tieneRol(Rol ...$roles): bool
    {
        return in_array($this->rol, $roles, true);
    }

    public function estaActivo(): bool
    {
        return $this->estado === EstadoUsuario::ACTIVO;
    }

        public function tieneOperaciones(): bool
    {
        return $this->movimientos()->exists()
            || $this->legalizaciones()->exists()
            || $this->ajustes()->exists()
            || $this->auditorias()->where('log_name', '!=', 'seguridad')->exists();
    }

    /** Decisión de diseño: solo se elimina un usuario BLOQUEADO y sin operaciones registradas. */
    public function puedeEliminarse(): bool
    {
        return $this->estado === EstadoUsuario::BLOQUEADO && ! $this->tieneOperaciones();
    }
}
