<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $table = 'admins';

    protected $fillable = [
        'nombre_completo',
        'ci_usuario',
        'email',
        'password',
        'estado', // 'activo' | 'inactivo'
    ];

    protected $hidden = ['password', 'remember_token'];
}