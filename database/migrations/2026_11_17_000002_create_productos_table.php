<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->string('sku', 20)->unique();
            $table->string('descripcion', 255);
            $table->string('tipo_almacenamiento', 20);
            $table->string('unidad_medida', 5);
            $table->decimal('peso_unitario_kg', 12, 3);
            $table->decimal('stock_minimo', 14, 3)->default(0);
            $table->string('estado', 10)->default('ACTIVO');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('cliente_id', 'productos_cliente_idx');
        });

        DB::statement("ALTER TABLE productos
            ADD CONSTRAINT productos_sku_check CHECK (sku ~ '^[A-Z]{3,6}-[0-9]{5}$'),
            ADD CONSTRAINT productos_tipo_almacenamiento_check CHECK (tipo_almacenamiento IN ('PALETIZADO','GRANEL','CONTENERIZADO','ENSAMBLADO','MEZCLA','EMPACADO')),
            ADD CONSTRAINT productos_unidad_medida_check CHECK (unidad_medida IN ('UND','KG','LT')),
            ADD CONSTRAINT productos_peso_unitario_kg_check CHECK (peso_unitario_kg >= 0),
            ADD CONSTRAINT productos_stock_minimo_check CHECK (stock_minimo >= 0),
            ADD CONSTRAINT productos_estado_check CHECK (estado IN ('ACTIVO','INACTIVO'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
