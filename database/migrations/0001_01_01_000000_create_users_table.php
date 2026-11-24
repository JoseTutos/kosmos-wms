<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Reemplaza la migración de users que trae Laravel por defecto.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('email', 150)->unique();
            $table->string('password', 255);
            $table->string('rol', 20);
            $table->string('estado', 10)->default('ACTIVO');
            $table->smallInteger('intentos_fallidos')->default(0);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        DB::statement("ALTER TABLE users
            ADD CONSTRAINT users_rol_check CHECK (rol IN ('ADMIN_SISTEMA','ADMIN_MAESTROS','SUPERVISOR','ANALISTA','OPERARIO','GERENTE')),
            ADD CONSTRAINT users_estado_check CHECK (estado IN ('ACTIVO','INACTIVO','BLOQUEADO')),
            ADD CONSTRAINT users_intentos_fallidos_check CHECK (intentos_fallidos BETWEEN 0 AND 5)");

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
