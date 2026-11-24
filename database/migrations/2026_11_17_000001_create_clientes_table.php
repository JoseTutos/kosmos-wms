<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nit', 20)->unique();
            $table->string('codigo_corto', 6)->unique();
            $table->string('razon_social', 200);
            $table->string('direccion', 255);
            $table->string('telefono', 30);
            $table->string('contacto_nombre', 150);
            $table->string('estado', 10)->default('ACTIVO');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        DB::statement("ALTER TABLE clientes
            ADD CONSTRAINT clientes_codigo_corto_check CHECK (codigo_corto ~ '^[A-Z]{3,6}$'),
            ADD CONSTRAINT clientes_estado_check CHECK (estado IN ('ACTIVO','INACTIVO'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
