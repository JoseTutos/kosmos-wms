<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Módulo 3 · J7 (sección 7, cambio 2): soporte opcional del ajuste (foto o PDF, privado). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ajustes', function (Blueprint $table) {
            $table->string('soporte_path', 255)->nullable()->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('ajustes', function (Blueprint $table) {
            $table->dropColumn('soporte_path');
        });
    }
};
