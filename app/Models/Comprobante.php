<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comprobante extends Model
{
    protected $table = 'comprobantes';
    protected $fillable = ['ci_usuario','fecha','concepto','archivo_path','estado'];

    // Estados válidos según tu enum: Pendiente | Validado | Rechazado
}