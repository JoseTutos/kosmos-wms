<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de solo inserción (kardex): sin updated_at
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 15);
            $table->foreignId('bulto_detalle_id')->constrained('bulto_detalles');
            $table->foreignId('bodega_id')->nullable()->constrained('bodegas');
            $table->string('estado_legal', 12)->nullable();
            $table->decimal('cantidad', 14, 3);
            $table->string('documento_ref', 20)->nullable();
            $table->foreignId('usuario_id')->constrained('users');
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['bulto_detalle_id', 'created_at'], 'mov_linea_fecha_idx');
            $table->index(['bodega_id', 'created_at'], 'mov_bodega_fecha_idx');
            $table->index(['tipo', 'created_at'], 'mov_tipo_fecha_idx');
            $table->index('created_at', 'mov_fecha_brin', 'brin');
        });

        DB::statement("ALTER TABLE movimientos
            ADD CONSTRAINT movimientos_tipo_check CHECK (tipo IN ('SALDO_INICIAL','RECEPCION','TRASLADO','AFORO','LEGALIZACION','AJUSTE','DESPACHO')),
            ADD CONSTRAINT movimientos_estado_legal_check CHECK (estado_legal IN ('CUARENTENA','LEGALIZADA')),
            ADD CONSTRAINT movimientos_cantidad_check CHECK (cantidad <> 0)");
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
