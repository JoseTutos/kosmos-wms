<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de solo inserción: sin updated_at
        Schema::create('legalizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('consecutivo', 16)->unique();
            $table->foreignId('bl_id')->constrained('bls');
            $table->string('tipo_documento', 30);
            $table->string('numero_autorizacion', 60);
            $table->date('fecha_autorizacion');
            $table->string('soporte_path', 255);
            $table->foreignId('usuario_id')->constrained('users');
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['bl_id', 'created_at'], 'leg_bl_idx');
        });

        DB::statement("ALTER TABLE legalizaciones
            ADD CONSTRAINT legalizaciones_consecutivo_check CHECK (consecutivo ~ '^LEG-[0-9]{4}-[0-9]{5}$'),
            ADD CONSTRAINT legalizaciones_tipo_documento_check CHECK (tipo_documento IN ('RESOLUCION_NACIONALIZACION','VOBO','DECLARACION_IMPORTACION','OTRO'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('legalizaciones');
    }
};
