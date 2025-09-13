<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('usuarios', function (Blueprint $t) {
            $t->string('ci_usuario', 20)->primary();     // PK = CI (string)
            $t->string('primer_nombre', 50)->nullable();
            $t->string('segundo_nombre', 50)->nullable();
            $t->string('primer_apellido', 50)->nullable();
            $t->string('segundo_apellido', 50)->nullable();

            $t->string('email', 191)->nullable()->unique();
            $t->string('telefono', 30)->nullable();

            $t->string('password');                      // hash
            $t->enum('estado_registro', ['pendiente','aprobado','rechazado'])->default('pendiente');
            $t->string('rol', 20)->default('socio');     // p.ej. socio

            $t->rememberToken();
            $t->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('usuarios');
    }
};