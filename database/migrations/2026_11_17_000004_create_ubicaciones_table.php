<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ubicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bodega_id')->constrained('bodegas');
            $table->string('codigo', 20);
            $table->string('estado', 10)->default('ACTIVA');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['bodega_id', 'codigo']);
        });

        DB::statement("ALTER TABLE ubicaciones
            ADD CONSTRAINT ubicaciones_estado_check CHECK (estado IN ('ACTIVA','INACTIVA'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('ubicaciones');
    }
};
