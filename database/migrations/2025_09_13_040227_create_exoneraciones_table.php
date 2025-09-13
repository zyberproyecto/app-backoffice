<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('exoneraciones', function (Blueprint $t) {
            $t->id();
            $t->string('ci_usuario', 20);
            $t->string('periodo', 20)->nullable();          // normalmente la misma "semana"
            $t->text('motivo')->nullable();
            $t->text('nota_admin')->nullable();
            $t->text('motivo_rechazo')->nullable();
            $t->enum('estado', ['pendiente','aprobado','rechazado'])->default('pendiente');

            $t->timestamps();

            $t->index(['ci_usuario', 'periodo']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('exoneraciones');
    }
};