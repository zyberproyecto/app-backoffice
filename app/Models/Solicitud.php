<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    protected $table = 'solicitudes';

    protected $fillable = [
        'ci',
        'nombre_completo',
        'email',
        'telefono',
        'dormitorios',
        'menores_a_cargo',
        'comentarios',
        'estado',        // 'pendiente' | 'aprobado' | 'rechazado'
        'aprobado_por',
        'aprobado_at',
        'usuario_ci',    // CI creado al aprobar
        'nota_admin',
    ];

    protected $casts = [
        'menores_a_cargo' => 'boolean',
        'aprobado_at'     => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    // Relaciones útiles (opcionales)
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_ci', 'ci_usuario');
    }

    // Scopes
    public function scopeEstado($q, string $estado)
    {
        return $q->where('estado', $estado);
    }
}