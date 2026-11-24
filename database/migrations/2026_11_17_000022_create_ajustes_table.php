<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de solo inserción: sin updated_at
        Schema::create('ajustes', function (Blueprint $table) {
            $table->id();
            $table->string('consecutivo', 16)->unique();
            $table->foreignId('bulto_detalle_id')->constrained('bulto_detalles');
            $table->string('tipo', 8);
            $table->string('estado_afectado', 12);
            $table->decimal('cantidad', 14, 3);
            $table->string('motivo', 25);
            $table->text('descripcion');
            $table->foreignId('movimiento_corregido_id')->nullable()->constrained('movimientos');
            $table->foreignId('usuario_id')->constrained('users');
            $table->timestampTz('created_at')->useCurrent();

            $table->index('bulto_detalle_id', 'ajustes_bulto_det_idx');
        });

        DB::statement("ALTER TABLE ajustes
            ADD CONSTRAINT ajustes_consecutivo_check CHECK (consecutivo ~ '^AJ-[0-9]{4}-[0-9]{5}$'),
            ADD CONSTRAINT ajustes_tipo_check CHECK (tipo IN ('POSITIVO','NEGATIVO')),
            ADD CONSTRAINT ajustes_estado_afectado_check CHECK (estado_afectado IN ('CUARENTENA','LEGALIZADA')),
            ADD CONSTRAINT ajustes_cantidad_check CHECK (cantidad > 0),
            ADD CONSTRAINT ajustes_motivo_check CHECK (motivo IN ('ERROR_CONTEO','DANO','FALTANTE','SOBRANTE','CORRECCION_REGISTRO','OTRO'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes');
    }
};
