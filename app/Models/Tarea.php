<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tarea extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tareas';

    protected $fillable = [
        'titulo',
        'descripcion',
        'tipo_tarea',
        'estado',
        'prioridad',
        'operador_id',
        'cliente_id',
        'pqrsfd_id',
        'fecha_asignacion',
        'fecha_vencimiento',
        'fecha_inicio',
        'fecha_completacion',
        'tiempo_estimado',
        'tiempo_real',
        'observaciones',
        'archivos_adjuntos',
        'asignado_por',
        'activo'
    ];

    protected $casts = [
        'fecha_asignacion' => 'datetime',
        'fecha_vencimiento' => 'datetime',
        'fecha_inicio' => 'datetime',
        'fecha_completacion' => 'datetime',
        'archivos_adjuntos' => 'array',
        'activo' => 'boolean'
    ];

    protected $dates = ['deleted_at'];

    public function operador()
    {
        return $this->belongsTo(Operador::class, 'operador_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function pqrsfd()
    {
        return $this->belongsTo(PQRSFD::class, 'pqrsfd_id');
    }

    public function asignadoPor()
    {
        return $this->belongsTo(UsuarioSistema::class, 'asignado_por');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeVencidas($query)
    {
        return $query->where('fecha_vencimiento', '<', now())
                    ->whereNotIn('estado', ['completada', 'cancelada']);
    }

    public function getDiasRestantesAttribute()
    {
        if ($this->fecha_vencimiento && !in_array($this->estado, ['completada', 'cancelada'])) {
            return now()->diffInDays($this->fecha_vencimiento, false);
        }
        return null;
    }

    public function iniciar()
    {
        $this->update([
            'estado' => 'en_progreso',
            'fecha_inicio' => now()
        ]);
    }

    public function completar($observaciones = null)
    {
        $tiempoReal = $this->fecha_inicio ? $this->fecha_inicio->diffInHours(now()) : null;

        $this->update([
            'estado' => 'completada',
            'fecha_completacion' => now(),
            'tiempo_real' => $tiempoReal,
            'observaciones' => $observaciones ?? $this->observaciones
        ]);
    }
}