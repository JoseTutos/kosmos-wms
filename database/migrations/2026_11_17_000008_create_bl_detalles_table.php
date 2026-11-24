<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bl_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bl_id')->constrained('bls');
            $table->foreignId('producto_id')->constrained('productos');
            $table->integer('bultos_esperados');
            $table->decimal('unidades_esperadas', 14, 3);
            $table->decimal('peso_bruto_kg', 12, 3);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['bl_id', 'producto_id']);
            $table->index('producto_id', 'bl_detalles_producto_idx');
        });

        DB::statement("ALTER TABLE bl_detalles
            ADD CONSTRAINT bl_detalles_bultos_esperados_check CHECK (bultos_esperados >= 0),
            ADD CONSTRAINT bl_detalles_unidades_esperadas_check CHECK (unidades_esperadas >= 0),
            ADD CONSTRAINT bl_detalles_peso_bruto_kg_check CHECK (peso_bruto_kg >= 0)");
    }

    public function down(): void
    {
        Schema::dropIfExists('bl_detalles');
    }
};
