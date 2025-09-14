<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exoneracion extends Model
{
    protected $table = 'exoneraciones';    // tabla de API-Cooperativa
    protected $fillable = [
        'ci_usuario','semana_inicio','motivo','estado',
        'resolucion_admin','archivo','created_at','updated_at',
    ];

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_APROBADA  = 'aprobada';
    public const ESTADO_RECHAZADA = 'rechazada';

    protected $casts = [
        'semana_inicio' => 'date',
    ];
}