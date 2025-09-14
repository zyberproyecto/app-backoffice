<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    protected $table = 'solicitudes';
    protected $fillable = [
        'ci','nombre_completo','email','telefono',
        'menores_a_cargo','dormitorios','comentarios',
        'estado','aprobado_por','aprobado_at',
    ];
}