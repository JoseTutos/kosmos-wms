<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bodegas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 100);
            $table->decimal('tamano_m2', 10, 2);
            $table->boolean('maneja_ubicaciones')->default(false);
            $table->string('estado', 10)->default('ACTIVA');
            $table->decimal('m2_ocupados', 10, 2)->default(0);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        DB::statement("ALTER TABLE bodegas
            ADD CONSTRAINT bodegas_tamano_m2_check CHECK (tamano_m2 > 0),
            ADD CONSTRAINT bodegas_estado_check CHECK (estado IN ('ACTIVA','INACTIVA')),
            ADD CONSTRAINT bodegas_m2_ocupados_check CHECK (m2_ocupados >= 0)");
    }

    public function down(): void
    {
        Schema::dropIfExists('bodegas');
    }
};
