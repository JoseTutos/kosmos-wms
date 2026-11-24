<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_salida_seriales', function (Blueprint $table) {
            $table->foreignId('orden_salida_detalle_id')->constrained('orden_salida_detalles');
            $table->foreignId('serial_id')->constrained('seriales');
            $table->boolean('confirmado')->default(false);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->primary(['orden_salida_detalle_id', 'serial_id']);
            $table->index('serial_id', 'oss_serial_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_salida_seriales');
    }
};
