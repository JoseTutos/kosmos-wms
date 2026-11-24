<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();
            $table->string('log_name', 50)->nullable();
            $table->string('description', 255);
            $table->string('subject_type', 100)->nullable();
            $table->bigInteger('subject_id')->nullable();
            $table->string('event', 30)->nullable();
            $table->string('causer_type', 100)->nullable();
            $table->foreignId('causer_id')->nullable()->constrained('users');
            $table->jsonb('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->index(['causer_id', 'created_at'], 'auditoria_causer_fecha_idx');
            $table->index(['subject_type', 'subject_id'], 'auditoria_subject_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};
