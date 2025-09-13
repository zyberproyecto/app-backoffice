<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('solicitudes', function (Blueprint $t) {
            $t->id();
            $t->string('ci_usuario', 20)->nullable();                 // a veces llega como ci/ci_usuario
            $t->string('ci', 20)->nullable();
            $t->string('nombre', 191)->nullable();                    // o nombre_completo
            $t->string('nombre_completo', 191)->nullable();
            $t->string('email', 191)->nullable();
            $t->string('telefono', 30)->nullable();

            $t->integer('menores_a_cargo')->nullable();
            $t->integer('dormitorios')->nullable();
            $t->text('comentarios')->nullable();

            $t->enum('estado', ['pendiente','aprobado','rechazado'])->default('pendiente');

            $t->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('solicitudes');
    }
};