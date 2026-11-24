<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aforos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bl_id')->constrained('bls');
            $table->string('estado', 12)->default('EN_PROCESO');
            $table->string('reporte_path', 255)->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('bl_id', 'aforos_bl_idx');
        });

        DB::statement("ALTER TABLE aforos
            ADD CONSTRAINT aforos_estado_check CHECK (estado IN ('EN_PROCESO','CERRADO','CANCELADO'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('aforos');
    }
};
