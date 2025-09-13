<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('horas_trabajo', function (Blueprint $t) {
            $t->id();
            $t->string('ci_usuario', 20);
            $t->string('semana', 20)->nullable();           // p.ej. 2025-W14
            $t->date('fecha')->nullable();
            $t->decimal('horas', 5, 2)->nullable();
            $t->string('actividad', 191)->nullable();
            $t->text('descripcion')->nullable();
            $t->enum('estado', ['pendiente','aprobado','rechazado'])->default('pendiente');
            $t->text('motivo_rechazo')->nullable();

            $t->timestamps();

            $t->index(['ci_usuario', 'semana']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('horas_trabajo');
    }
};