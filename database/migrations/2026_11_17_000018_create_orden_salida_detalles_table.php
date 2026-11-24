<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_salida_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_salida_id')->constrained('ordenes_salida');
            $table->foreignId('bl_id')->constrained('bls');
            $table->bigInteger('bulto_detalle_id');
            $table->string('tipo_linea', 10);
            $table->decimal('cantidad_solicitada', 14, 3);
            $table->decimal('cantidad_despachada', 14, 3)->default(0);
            $table->boolean('confirmada')->default(false);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['orden_salida_id', 'bulto_detalle_id']);
            $table->foreign(['bulto_detalle_id', 'bl_id'])->references(['id', 'bl_id'])->on('bulto_detalles');

            $table->index(['bulto_detalle_id', 'bl_id'], 'osd_bulto_det_idx');
            $table->index(['orden_salida_id', 'bl_id'], 'osd_orden_bl_idx');
        });

        DB::statement("ALTER TABLE orden_salida_detalles
            ADD CONSTRAINT orden_salida_detalles_tipo_linea_check CHECK (tipo_linea IN ('COMPLETO','PARCIAL','SERIAL')),
            ADD CONSTRAINT orden_salida_detalles_cantidad_solicitada_check CHECK (cantidad_solicitada > 0),
            ADD CONSTRAINT orden_salida_detalles_despachada_rango_check CHECK (cantidad_despachada BETWEEN 0 AND cantidad_solicitada)");
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_salida_detalles');
    }
};
