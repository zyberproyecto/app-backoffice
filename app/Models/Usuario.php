<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'ci_usuario';   // PK según tu BD
    public $incrementing = false;           // la CI no autoincrementa
    protected $keyType = 'string';          // en tu dump era VARCHAR

    protected $fillable = [
        'ci_usuario',
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'email',
        'telefono',
        'password',
        'estado_registro',   // este es el campo real
        'rol',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
