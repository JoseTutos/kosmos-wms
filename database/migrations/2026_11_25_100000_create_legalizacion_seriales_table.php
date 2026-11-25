<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo 3 · L19 (sección 7, cambio 1): qué serial se legalizó en qué LEG (histórico CUS-012).
 * Solo inserción: sin updated_at. Un serial se legaliza una sola vez (serial_id único).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legalizacion_seriales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legalizacion_detalle_id')->constrained('legalizacion_detalles');
            $table->foreignId('serial_id')->unique()->constrained('seriales');
            $table->timestampTz('created_at')->useCurrent();

            $table->index('legalizacion_detalle_id', 'leg_ser_detalle_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legalizacion_seriales');
    }
};
