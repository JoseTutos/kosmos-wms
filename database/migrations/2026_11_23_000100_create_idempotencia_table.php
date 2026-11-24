<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D13: cada POST de la app lleva Idempotency-Key (UUID). El servidor procesa cada clave
 * una sola vez por usuario y, ante un reintento, devuelve la respuesta guardada.
 * Tabla técnica, fuera de las 24 del modelo de datos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotencia', function (Blueprint $table) {
            $table->id();
            // cascade: no es trazabilidad de negocio; no debe impedir eliminar un usuario sin operaciones
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('clave');
            $table->string('metodo', 10);
            $table->string('ruta', 255);
            $table->char('huella', 64);                          // sha256 de método + ruta + cuerpo
            $table->unsignedSmallInteger('estado_http')->nullable(); // null = en proceso
            $table->text('cuerpo')->nullable();
            $table->string('tipo_contenido', 100)->nullable();
            $table->timestamps();

            $table->unique(['usuario_id', 'clave']);
            $table->index('created_at');                          // purga diaria
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotencia');
    }
};
