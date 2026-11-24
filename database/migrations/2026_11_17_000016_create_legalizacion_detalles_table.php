<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legalizacion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legalizacion_id')->constrained('legalizaciones');
            $table->foreignId('bulto_detalle_id')->constrained('bulto_detalles');
            $table->decimal('cantidad', 14, 3);
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['legalizacion_id', 'bulto_detalle_id']);
            $table->index('bulto_detalle_id', 'leg_det_bulto_det_idx');
        });

        DB::statement("ALTER TABLE legalizacion_detalles
            ADD CONSTRAINT legalizacion_detalles_cantidad_check CHECK (cantidad > 0)");
    }

    public function down(): void
    {
        Schema::dropIfExists('legalizacion_detalles');
    }
};
