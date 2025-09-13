<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Solicitud extends Model
{
    protected $table = 'solicitudes';

    // Usamos los nombres "canónicos" que queremos mantener a futuro.
    // (Si tu tabla hoy tiene los alias, este modelo igual lee bien gracias a los accessors.)
    protected $fillable = [
        'ci_usuario',
        'nombre',
        'email',
        'telefono',
        'menores_a_cargo',
        'dormitorios',
        'comentarios',
        'estado', // 'pendiente' | 'aprobado' | 'rechazado'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'estado' => self::ESTADO_PENDIENTE,
    ];

    // --------- Constantes de negocio ---------
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_APROBADO  = 'aprobado';
    public const ESTADO_RECHAZADO = 'rechazado';

    // --------- Normalizadores ---------
    public function setEstadoAttribute($value): void
    {
        $this->attributes['estado'] = Str::lower(trim((string) $value));
    }

    // --------- Lectura tolerante a alias (solo lectura) ---------
    // Si el campo canónico no existe en la fila, devolvemos el alias histórico.
    public function getCiUsuarioAttribute($value)
    {
        return $value ?? ($this->attributes['ci'] ?? null);
    }

    public function getMenoresACargoAttribute($value)
    {
        return $value ?? ($this->attributes['menores_cargo'] ?? null);
    }

    public function getDormitoriosAttribute($value)
    {
        return $value ?? ($this->attributes['intereses'] ?? null);
    }

    public function getComentariosAttribute($value)
    {
        return $value ?? ($this->attributes['mensaje'] ?? null);
    }

    // --------- Scopes útiles ---------
    public function scopeEstado(Builder $q, ?string $estado): Builder
    {
        if (!$estado || $estado === 'todos') {
            return $q;
        }
        return $q->where('estado', Str::lower($estado));
    }

    public function scopePendientes(Builder $q): Builder
    {
        return $q->where('estado', self::ESTADO_PENDIENTE);
    }
}