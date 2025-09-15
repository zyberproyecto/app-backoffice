<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'ci_usuario';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'ci_usuario',
        'primer_nombre',
        'primer_apellido',
        'email',
        'telefono',
        'password',
        'rol',              // 'socio'
        'estado_registro',  // 'aprobado' | 'pendiente' | 'rechazado'
    ];

    protected $hidden = ['password'];
}