<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pallets', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 16)->unique();
            $table->foreignId('bl_id')->constrained('bls');
            $table->foreignId('bodega_id')->nullable()->constrained('bodegas');
            $table->foreignId('ubicacion_id')->nullable()->constrained('ubicaciones');
            $table->string('referencia_ubicacion', 150)->nullable();
            $table->string('estado', 12)->default('ABIERTO');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            // Necesario para la FK compuesta (pallet_id, bl_id) desde bultos
            $table->unique(['id', 'bl_id']);
            $table->index('bl_id', 'pallets_bl_idx');
            $table->index('ubicacion_id', 'pallets_ubicacion_idx');
        });

        DB::statement("ALTER TABLE pallets
            ADD CONSTRAINT pallets_codigo_check CHECK (codigo ~ '^PLT-[0-9]{6}-[0-9]{4}$'),
            ADD CONSTRAINT pallets_estado_check CHECK (estado IN ('ABIERTO','CERRADO','UBICADO','DESPACHADO')),
            ADD CONSTRAINT pallets_ubicado_bodega_check CHECK (estado <> 'UBICADO' OR bodega_id IS NOT NULL)");

        // Índice parcial (Laravel no lo soporta con el builder)
        DB::statement("CREATE INDEX pallets_bodega_idx ON pallets (bodega_id) WHERE estado = 'UBICADO'");
    }

    public function down(): void
    {
        Schema::dropIfExists('pallets');
    }
};
