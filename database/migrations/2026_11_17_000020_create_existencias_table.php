<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('existencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('producto_id')->constrained('productos');
            $table->foreignId('bodega_id')->constrained('bodegas');
            $table->foreignId('bl_id')->constrained('bls');
            $table->decimal('cantidad_cuarentena', 14, 3)->default(0);
            $table->decimal('cantidad_legalizada', 14, 3)->default(0);
            $table->decimal('cantidad_reservada', 14, 3)->default(0);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['cliente_id', 'producto_id', 'bodega_id', 'bl_id']);
            $table->index(['bodega_id', 'cliente_id'], 'existencias_bodega_cliente_idx');
            $table->index('producto_id', 'existencias_producto_idx');
        });

        DB::statement("ALTER TABLE existencias
            ADD CONSTRAINT existencias_cantidad_cuarentena_check CHECK (cantidad_cuarentena >= 0),
            ADD CONSTRAINT existencias_cantidad_legalizada_check CHECK (cantidad_legalizada >= 0),
            ADD CONSTRAINT existencias_cantidad_reservada_check CHECK (cantidad_reservada >= 0),
            ADD CONSTRAINT existencias_reservada_legalizada_check CHECK (cantidad_reservada <= cantidad_legalizada)");
    }

    public function down(): void
    {
        Schema::dropIfExists('existencias');
    }
};
