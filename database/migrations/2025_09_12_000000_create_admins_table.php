<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('admins', function (Blueprint $t) {
            $t->string('ci_usuario', 20)->primary();      
            $t->string('primer_nombre', 50);
            $t->string('segundo_nombre', 50)->nullable();
            $t->string('primer_apellido', 50);
            $t->string('segundo_apellido', 50)->nullable();

            $t->string('email', 100)->unique();
            $t->string('telefono', 30)->nullable();

            $t->string('password');                       // hash (bcrypt/argon)
            $t->enum('estado', ['activo','inactivo'])->default('activo');
            $t->rememberToken();
            $t->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('admins');
    }
};