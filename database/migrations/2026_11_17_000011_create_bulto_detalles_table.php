<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulto_detalles', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('bulto_id');
            $table->bigInteger('bl_id');
            $table->foreignId('producto_id')->constrained('productos');
            $table->decimal('cantidad_recibida', 14, 3);
            $table->decimal('cantidad_legalizada', 14, 3)->default(0);
            $table->decimal('cantidad_reservada', 14, 3)->default(0);
            $table->decimal('cantidad_despachada', 14, 3)->default(0);
            $table->boolean('serializado')->default(false);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->unique(['bulto_id', 'producto_id']);
            $table->unique(['id', 'bl_id']);
            $table->foreign(['bulto_id', 'bl_id'])->references(['id', 'bl_id'])->on('bultos');
            $table->foreign(['bl_id', 'producto_id'])->references(['bl_id', 'producto_id'])->on('bl_detalles');

            $table->index('producto_id', 'bulto_det_producto_idx');
            $table->index(['bl_id', 'producto_id'], 'bulto_det_bl_prod_idx');
        });

        DB::statement("ALTER TABLE bulto_detalles
            ADD CONSTRAINT bulto_detalles_cantidad_recibida_check CHECK (cantidad_recibida > 0),
            ADD CONSTRAINT bulto_detalles_cantidad_reservada_check CHECK (cantidad_reservada >= 0),
            ADD CONSTRAINT bulto_detalles_cantidad_despachada_check CHECK (cantidad_despachada >= 0),
            ADD CONSTRAINT bulto_detalles_legalizada_rango_check CHECK (cantidad_legalizada BETWEEN 0 AND cantidad_recibida),
            ADD CONSTRAINT bulto_detalles_reserva_despacho_check CHECK (cantidad_reservada + cantidad_despachada <= cantidad_legalizada)");

        DB::statement("CREATE INDEX bulto_det_cuarentena_pidx ON bulto_detalles (bl_id) WHERE cantidad_legalizada < cantidad_recibida");
    }

    public function down(): void
    {
        Schema::dropIfExists('bulto_detalles');
    }
};
