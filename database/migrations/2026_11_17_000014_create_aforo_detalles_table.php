<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aforo_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aforo_id')->constrained('aforos');
            $table->foreignId('bulto_detalle_id')->constrained('bulto_detalles');
            $table->decimal('cantidad_manifiesto', 14, 3);
            $table->decimal('cantidad_contada', 14, 3)->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['aforo_id', 'bulto_detalle_id']);
            $table->index('bulto_detalle_id', 'aforo_det_bulto_det_idx');
        });

        DB::statement("ALTER TABLE aforo_detalles
            ADD CONSTRAINT aforo_detalles_cantidad_manifiesto_check CHECK (cantidad_manifiesto >= 0),
            ADD CONSTRAINT aforo_detalles_cantidad_contada_check CHECK (cantidad_contada >= 0)");
    }

    public function down(): void
    {
        Schema::dropIfExists('aforo_detalles');
    }
};
