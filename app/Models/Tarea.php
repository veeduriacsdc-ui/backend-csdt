<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Tarea extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tareas';
    
    protected $fillable = [
        'vee_id', 'asig_por', 'asig_a', 'tit', 'des', 'est', 'pri', 
        'fec_ini', 'fec_fin', 'fec_ven', 'not', 'arc'
    ];

    protected $casts = [
        'fec_ini' => 'datetime',
        'fec_fin' => 'datetime',
        'fec_ven' => 'datetime',
        'arc' => 'array',
    ];

    // Relaciones
    public function veeduria()
    {
        return $this->belongsTo(Veeduria::class, 'vee_id');
    }

    public function asignadoPor()
    {
        return $this->belongsTo(Usuario::class, 'asig_por');
    }

    public function asignadoA()
    {
        return $this->belongsTo(Usuario::class, 'asig_a');
    }

    public function archivos()
    {
        return $this->hasMany(Archivo::class, 'tar_id');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('est', '!=', 'can');
    }

    public function scopePorEstado($query, $estado)
    {
        return $query->where('est', $estado);
    }

    public function scopePorPrioridad($query, $prioridad)
    {
        return $query->where('pri', $prioridad);
    }

    public function scopePorVeeduria($query, $veeduriaId)
    {
        return $query->where('vee_id', $veeduriaId);
    }

    public function scopePorAsignado($query, $usuarioId)
    {
        return $query->where('asig_a', $usuarioId);
    }

    public function scopePendientes($query)
    {
        return $query->where('est', 'pen');
    }

    public function scopeEnProceso($query)
    {
        return $query->where('est', 'pro');
    }

    public function scopeCompletadas($query)
    {
        return $query->where('est', 'com');
    }

    public function scopeCanceladas($query)
    {
        return $query->where('est', 'can');
    }

    public function scopeSuspendidas($query)
    {
        return $query->where('est', 'sus');
    }

    public function scopeVencidas($query)
    {
        return $query->where('fec_ven', '<', now())
                    ->whereIn('est', ['pen', 'pro']);
    }

    public function scopePorVencer($query, $dias = 3)
    {
        return $query->where('fec_ven', '<=', now()->addDays($dias))
                    ->whereIn('est', ['pen', 'pro']);
    }

    // Métodos de negocio
    public function getEstadoColorAttribute()
    {
        return match ($this->est) {
            'pen' => 'warning',
            'pro' => 'info',
            'com' => 'success',
            'can' => 'danger',
            'sus' => 'secondary',
            default => 'secondary'
        };
    }

    public function getPrioridadColorAttribute()
    {
        return match ($this->pri) {
            'baj' => 'success',
            'med' => 'warning',
            'alt' => 'danger',
            'urg' => 'dark',
            default => 'secondary'
        };
    }

    public function getEstadoTextoAttribute()
    {
        return match ($this->est) {
            'pen' => 'Pendiente',
            'pro' => 'En Proceso',
            'com' => 'Completada',
            'can' => 'Cancelada',
            'sus' => 'Suspendida',
            default => 'Desconocido'
        };
    }

    public function getPrioridadTextoAttribute()
    {
        return match ($this->pri) {
            'baj' => 'Baja',
            'med' => 'Media',
            'alt' => 'Alta',
            'urg' => 'Urgente',
            default => 'Desconocida'
        };
    }

    public function estaVencida()
    {
        return $this->fec_ven && $this->fec_ven < now() && in_array($this->est, ['pen', 'pro']);
    }

    public function estaPorVencer($dias = 3)
    {
        return $this->fec_ven && $this->fec_ven <= now()->addDays($dias) && in_array($this->est, ['pen', 'pro']);
    }

    public function iniciar()
    {
        $this->update([
            'est' => 'pro',
            'fec_ini' => now()
        ]);
    }

    public function completar()
    {
        $this->update([
            'est' => 'com',
            'fec_fin' => now()
        ]);
    }

    public function cancelar($motivo = null)
    {
        $this->update([
            'est' => 'can',
            'not' => $motivo ? $this->not . "\nCancelada: " . $motivo : $this->not
        ]);
    }

    public function suspender($motivo = null)
    {
        $this->update([
            'est' => 'sus',
            'not' => $motivo ? $this->not . "\nSuspendida: " . $motivo : $this->not
        ]);
    }

    public function reanudar()
    {
        $this->update(['est' => 'pro']);
    }

    public function asignar($usuarioId, $asignadoPorId = null)
    {
        $this->update([
            'asig_a' => $usuarioId,
            'asig_por' => $asignadoPorId ?: auth()->id()
        ]);
    }

    // Validaciones
    public static function reglas($id = null)
    {
        return [
            'vee_id' => 'required|exists:veedurias,id',
            'asig_por' => 'required|exists:usuarios,id',
            'asig_a' => 'nullable|exists:usuarios,id',
            'tit' => 'required|string|max:200',
            'des' => 'required|string',
            'est' => 'sometimes|in:pen,pro,com,can,sus',
            'pri' => 'sometimes|in:baj,med,alt,urg',
            'fec_ini' => 'nullable|date|after_or_equal:today',
            'fec_fin' => 'nullable|date|after_or_equal:fec_ini',
            'fec_ven' => 'nullable|date|after:today',
            'not' => 'nullable|string',
        ];
    }

    public static function mensajes()
    {
        return [
            'vee_id.required' => 'La veeduría es obligatoria.',
            'vee_id.exists' => 'La veeduría seleccionada no existe.',
            'asig_por.required' => 'El asignador es obligatorio.',
            'asig_por.exists' => 'El asignador seleccionado no existe.',
            'asig_a.exists' => 'El asignado seleccionado no existe.',
            'tit.required' => 'El título es obligatorio.',
            'tit.max' => 'El título no puede exceder 200 caracteres.',
            'des.required' => 'La descripción es obligatoria.',
            'est.in' => 'El estado debe ser válido.',
            'pri.in' => 'La prioridad debe ser válida.',
            'fec_ini.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fec_ini.after_or_equal' => 'La fecha de inicio no puede ser anterior a hoy.',
            'fec_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fec_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
            'fec_ven.date' => 'La fecha de vencimiento debe ser una fecha válida.',
            'fec_ven.after' => 'La fecha de vencimiento debe ser posterior a hoy.',
        ];
    }
}