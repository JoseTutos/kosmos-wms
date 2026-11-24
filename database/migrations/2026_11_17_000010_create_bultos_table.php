<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bultos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 16)->unique();
            $table->foreignId('bl_id')->constrained('bls');
            $table->bigInteger('pallet_id')->nullable();
            $table->foreignId('bodega_id')->nullable()->constrained('bodegas');
            $table->decimal('peso_bruto_kg', 12, 3);
            $table->string('estado', 12)->default('RECIBIDO');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['id', 'bl_id']);
            // El bulto solo puede ir en un pallet del mismo BL
            $table->foreign(['pallet_id', 'bl_id'])->references(['id', 'bl_id'])->on('pallets');

            $table->index('bodega_id', 'bultos_bodega_idx');
            $table->index(['bl_id', 'estado'], 'bultos_bl_estado_idx');
            $table->index('pallet_id', 'bultos_pallet_idx');
        });

        DB::statement("ALTER TABLE bultos
            ADD CONSTRAINT bultos_codigo_check CHECK (codigo ~ '^BUL-[0-9]{6}-[0-9]{4}$'),
            ADD CONSTRAINT bultos_peso_bruto_kg_check CHECK (peso_bruto_kg >= 0),
            ADD CONSTRAINT bultos_estado_check CHECK (estado IN ('RECIBIDO','UBICADO','EN_AFORO','AFORADO','DESPACHADO','ANULADO')),
            ADD CONSTRAINT bultos_pallet_o_bodega_check CHECK (pallet_id IS NULL OR bodega_id IS NULL)");
    }

    public function down(): void
    {
        Schema::dropIfExists('bultos');
    }
};
