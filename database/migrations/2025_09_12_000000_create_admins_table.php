<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('admins', function (Blueprint $t) {
            $t->id();
            $t->string('ci_usuario', 20)->nullable()->unique(); // login por CI (opcional)
            $t->string('nombre', 191)->nullable();

            $t->string('email', 191)->unique();
            $t->string('password');

            $t->enum('estado', ['activo','inactivo'])->default('activo');
            $t->rememberToken();
            $t->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('admins');
    }
};