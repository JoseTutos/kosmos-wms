<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kosmos WMS · Infraestructura (Migración) · Tabla users
 *
 * Cumple:
 *   - Decisión de diseño (CUS-004): contraseña temporal + cambio obligatorio en el primer ingreso.
 *
 * Restricciones:
 *   - default(true): todo usuario nuevo, y los ya existentes (p. ej., el admin del seeder),
 *     queda obligado a definir su propia clave. Es el comportamiento buscado.
 *   - Si prefieres no tener una migración adicional, agrega esta columna a la
 *     migración original de users y borra este archivo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('debe_cambiar_password')->default(true)->after('intentos_fallidos');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('debe_cambiar_password');
        });
    }
};
