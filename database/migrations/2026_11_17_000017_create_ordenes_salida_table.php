<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_salida', function (Blueprint $table) {
            $table->id();
            $table->string('consecutivo', 16)->unique();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->string('destinatario', 255);
            $table->date('fecha_estimada');
            $table->string('estado', 20)->default('PENDIENTE_PICKING');
            $table->string('nota_entrega_path', 255)->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index(['cliente_id', 'estado'], 'os_cliente_estado_idx');
        });

        DB::statement("ALTER TABLE ordenes_salida
            ADD CONSTRAINT ordenes_salida_consecutivo_check CHECK (consecutivo ~ '^OS-[0-9]{4}-[0-9]{5}$'),
            ADD CONSTRAINT ordenes_salida_estado_check CHECK (estado IN ('PENDIENTE_PICKING','EN_PICKING','DESPACHADO','DESPACHADO_PARCIAL','ANULADA'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_salida');
    }
};
