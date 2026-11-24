<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seriales', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->foreignId('bulto_detalle_id')->constrained('bulto_detalles');
            $table->string('estado_legal', 15)->default('EN_CUARENTENA');
            $table->string('estado', 12)->default('DISPONIBLE');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('bulto_detalle_id', 'seriales_bulto_det_idx');
        });

        DB::statement("ALTER TABLE seriales
            ADD CONSTRAINT seriales_codigo_check CHECK (codigo ~ '^[A-Z]{3,6}-[0-9]{5}-[0-9]{4}$'),
            ADD CONSTRAINT seriales_estado_legal_check CHECK (estado_legal IN ('EN_CUARENTENA','LEGALIZADO')),
            ADD CONSTRAINT seriales_estado_check CHECK (estado IN ('DISPONIBLE','RESERVADO','DESPACHADO')),
            ADD CONSTRAINT seriales_disponible_o_legalizado_check CHECK (estado = 'DISPONIBLE' OR estado_legal = 'LEGALIZADO')");
    }

    public function down(): void
    {
        Schema::dropIfExists('seriales');
    }
};
