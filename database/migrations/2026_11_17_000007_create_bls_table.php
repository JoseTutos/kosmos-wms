<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bls', function (Blueprint $table) {
            $table->id();
            $table->string('numero_bl', 40)->unique();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->date('fecha_ingreso');
            $table->string('factura_comercial', 50);
            $table->string('estado', 20)->default('PROGRAMADO');
            $table->integer('discrepancia_bultos')->nullable();
            $table->text('observacion_recepcion')->nullable();
            $table->decimal('m2_granel', 10, 2)->nullable();
            $table->date('fecha_preinspeccion')->nullable();
            $table->string('acta_preinspeccion_path', 255)->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index(['cliente_id', 'estado'], 'bls_cliente_estado_idx');
        });

        DB::statement("ALTER TABLE bls
            ADD CONSTRAINT bls_estado_check CHECK (estado IN ('PROGRAMADO','EN_RECEPCION','RECIBIDO','DESPACHADO_PARCIAL','CERRADO')),
            ADD CONSTRAINT bls_m2_granel_check CHECK (m2_granel >= 0),
            ADD CONSTRAINT bls_discrepancia_observacion_check CHECK (discrepancia_bultos IS NULL OR discrepancia_bultos = 0 OR observacion_recepcion IS NOT NULL)");
    }

    public function down(): void
    {
        Schema::dropIfExists('bls');
    }
};
