<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    protected $table = 'solicitudes';
    protected $fillable = ['ci','nombre','email','telefono','menores_cargo','intereses','mensaje','estado'];
    // Estados: Pendiente | Aprobada | Rechazada
}