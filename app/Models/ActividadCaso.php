<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActividadCaso extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ActividadesCaso';
    protected $primaryKey = 'IdActividad';

    protected $fillable = [
        'IdPQRSFD', 'IdOperadorResponsable', 'TipoActividad', 'Descripcion',
        'FechaInicio', 'FechaFin', 'Estado', 'Prioridad', 'Notas', 'Resultado'
    ];

    protected $casts = [
        'FechaInicio' => 'datetime',
        'FechaFin' => 'datetime',
        'Prioridad' => 'integer',
    ];

    // Relaciones
    public function pqrsfd()
    {
        return $this->belongsTo(PQRSFD::class, 'IdPQRSFD', 'IdPQRSFD');
    }

    public function operadorResponsable()
    {
        return $this->belongsTo(Operador::class, 'IdOperadorResponsable', 'IdOperador');
    }

    // Métodos de negocio
    public function getEstadoColorAttribute()
    {
        $colores = [
            'Pendiente' => 'warning',
            'EnProceso' => 'info',
            'Completada' => 'success',
            'Cancelada' => 'danger',
            'Pausada' => 'secondary'
        ];
        return $colores[$this->Estado] ?? 'secondary';
    }

    public function getPrioridadColorAttribute()
    {
        $colores = [
            1 => 'success',   // Baja
            2 => 'info',      // Media
            3 => 'warning',   // Alta
            4 => 'danger'     // Crítica
        ];
        return $colores[$this->Prioridad] ?? 'secondary';
    }

    public function getPrioridadTextoAttribute()
    {
        $textos = [
            1 => 'Baja',
            2 => 'Media',
            3 => 'Alta',
            4 => 'Crítica'
        ];
        return $textos[$this->Prioridad] ?? 'No definida';
    }

    public function getDuracionAttribute()
    {
        if ($this->FechaInicio && $this->FechaFin) {
            return $this->FechaInicio->diffInHours($this->FechaFin);
        }
        return null;
    }

    public function getDuracionFormateadaAttribute()
    {
        if ($this->FechaInicio && $this->FechaFin) {
            $duracion = $this->FechaInicio->diff($this->FechaFin);
            return $duracion->format('%H:%I:%S');
        }
        return 'En proceso';
    }

    public function iniciar()
    {
        $this->update([
            'Estado' => 'EnProceso',
            'FechaInicio' => now()
        ]);
    }

    public function completar($resultado = null)
    {
        $this->update([
            'Estado' => 'Completada',
            'FechaFin' => now(),
            'Resultado' => $resultado
        ]);
    }

    public function pausar()
    {
        $this->update(['Estado' => 'Pausada']);
    }

    public function cancelar()
    {
        $this->update(['Estado' => 'Cancelada']);
    }

    public function reanudar()
    {
        $this->update(['Estado' => 'EnProceso']);
    }

    // Scopes para consultas
    public function scopePorEstado($query, $estado)
    {
        return $query->where('Estado', $estado);
    }

    public function scopePorOperador($query, $idOperador)
    {
        return $query->where('IdOperadorResponsable', $idOperador);
    }

    public function scopePorPQRSFD($query, $idPQRSFD)
    {
        return $query->where('IdPQRSFD', $idPQRSFD);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('TipoActividad', $tipo);
    }

    public function scopePorPrioridad($query, $prioridad)
    {
        return $query->where('Prioridad', $prioridad);
    }

    public function scopePendientes($query)
    {
        return $query->whereIn('Estado', ['Pendiente', 'EnProceso']);
    }

    public function scopeCompletadas($query)
    {
        return $query->where('Estado', 'Completada');
    }

    public function scopePorFecha($query, $fechaInicio, $fechaFin = null)
    {
        if ($fechaFin) {
            return $query->whereBetween('FechaInicio', [$fechaInicio, $fechaFin]);
        }
        return $query->whereDate('FechaInicio', $fechaInicio);
    }

    // Validaciones
    public static function rules($id = null)
    {
        return [
            'IdPQRSFD' => 'required|exists:PQRSFD,IdPQRSFD',
            'IdOperadorResponsable' => 'required|exists:Operadores,IdOperador',
            'TipoActividad' => 'required|string|max:100',
            'Descripcion' => 'required|string|min:10',
            'FechaInicio' => 'nullable|date',
            'FechaFin' => 'nullable|date|after_or_equal:FechaInicio',
            'Estado' => 'required|in:Pendiente,EnProceso,Completada,Cancelada,Pausada',
            'Prioridad' => 'required|integer|between:1,4',
            'Notas' => 'nullable|string|max:1000',
            'Resultado' => 'nullable|string|max:1000',
        ];
    }

    // Eventos del modelo
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($actividad) {
            if (empty($actividad->Estado)) {
                $actividad->Estado = 'Pendiente';
            }
            if (empty($actividad->Prioridad)) {
                $actividad->Prioridad = 2; // Media por defecto
            }
        });
    }
}
