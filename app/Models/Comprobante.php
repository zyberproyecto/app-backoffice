<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Comprobante extends Model
{
    protected $table = 'comprobantes';

    protected $fillable = [
        'ci_usuario',
        'tipo',               
        'monto',
        'fecha_pago',
        'estado',        
        'archivo',       
        'nota_admin',
        'motivo_rechazo',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto'      => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'estado' => 'pendiente',
    ];

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_APROBADO  = 'aprobado';
    public const ESTADO_RECHAZADO = 'rechazado';

    public const TIPO_APORTE_INICIAL = 'aporte_inicial';
    public const TIPO_MENSUAL        = 'mensual';

    public function setEstadoAttribute($value): void
    {
        $this->attributes['estado'] = Str::lower(trim((string) $value));
    }

    public function scopeEstado(Builder $q, ?string $estado): Builder
    {
        if (!$estado || $estado === 'todos') return $q;
        return $q->where('estado', Str::lower($estado));
    }

    public function scopeTipo(Builder $q, ?string $tipo): Builder
    {
        if (!$tipo) return $q;
        return $q->where('tipo', $tipo);
    }

    public function scopePendientes(Builder $q): Builder
    {
        return $q->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeMensual(Builder $q): Builder
    {
        return $q->where('tipo', self::TIPO_MENSUAL);
    }

    public function scopeInicial(Builder $q): Builder
    {
        return $q->where('tipo', self::TIPO_APORTE_INICIAL);
    }

    public function getArchivoUrlAttribute(): ?string
    {
        $path = $this->archivo;
        if (!$path) return null;
        if (Str::startsWith($path, ['http://','https://'])) return $path;

        $base = rtrim((string) env('COOP_API_FILES_BASE', ''), '/');
        return $base ? $base . '/' . ltrim($path, '/') : $path;
    }
}