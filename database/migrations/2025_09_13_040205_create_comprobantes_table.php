<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('comprobantes', function (Blueprint $t) {
            $t->id();
            $t->string('ci_usuario', 20);
            $t->enum('tipo', ['aporte_inicial','mensual']);
            $t->decimal('monto', 10, 2)->nullable();
            $t->date('fecha_pago')->nullable();

            $t->string('archivo', 255)->nullable();      // ruta/URL de archivo
            $t->text('nota_admin')->nullable();
            $t->text('motivo_rechazo')->nullable();

            $t->enum('estado', ['pendiente','aprobado','rechazado'])->default('pendiente');

            $t->timestamps();

            $t->index(['ci_usuario', 'tipo']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('comprobantes');
    }
};