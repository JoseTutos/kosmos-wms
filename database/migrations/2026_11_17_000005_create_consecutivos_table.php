<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consecutivos', function (Blueprint $table) {
            $table->string('tipo', 10);
            $table->string('periodo', 20);
            $table->integer('ultimo')->default(0);
            $table->timestampTz('updated_at')->useCurrent();

            $table->primary(['tipo', 'periodo']);
        });

        DB::statement("ALTER TABLE consecutivos
            ADD CONSTRAINT consecutivos_tipo_check CHECK (tipo IN ('SKU','PLT','BUL','SER','LEG','AJ','OS')),
            ADD CONSTRAINT consecutivos_ultimo_check CHECK (ultimo >= 0)");
    }

    public function down(): void
    {
        Schema::dropIfExists('consecutivos');
    }
};
