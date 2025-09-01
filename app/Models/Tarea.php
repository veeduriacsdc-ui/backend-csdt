<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Tarea extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tareas';

    protected $fillable = [
        'codigo_tarea',
        'titulo',
        'descripcion',
        'tipo_tarea',
        'categoria',
        'prioridad',
        'estado',
        'veeduria_id',
        'operador_asignado_id',
        'fecha_creacion',
        'fecha_inicio',
        'fecha_limite',
        'fecha_completada',
        'tiempo_estimado_horas',
        'tiempo_real_horas',
        'progreso',
        'dependencias_tareas',
        'recursos_requeridos',
        'presupuesto_estimado',
        'presupuesto_real',
        'ubicacion',
        'coordenadas_lat',
        'coordenadas_lng',
        'evidencia_fotografica',
        'documentos_adjuntos',
        'observaciones_operador',
        'comentarios_cliente',
        'tags',
        'nivel_urgencia',
        'impacto_veeduria',
        'notas_internas',
        'checklist',
        'criterios_aceptacion',
        'riesgos_identificados',
        'medidas_mitigacion'
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'fecha_inicio' => 'datetime',
        'fecha_limite' => 'datetime',
        'fecha_completada' => 'datetime',
        'dependencias_tareas' => 'array',
        'recursos_requeridos' => 'array',
        'evidencia_fotografica' => 'array',
        'documentos_adjuntos' => 'array',
        'tags' => 'array',
        'checklist' => 'array',
        'criterios_aceptacion' => 'array',
        'riesgos_identificados' => 'array',
        'medidas_mitigacion' => 'array',
        'coordenadas_lat' => 'decimal:8',
        'coordenadas_lng' => 'decimal:8',
        'tiempo_estimado_horas' => 'decimal:8,2',
        'tiempo_real_horas' => 'decimal:8,2',
        'progreso' => 'decimal:5,2',
        'presupuesto_estimado' => 'decimal:15,2',
        'presupuesto_real' => 'decimal:15,2',
        'impacto_veeduria' => 'integer'
    ];

    // Relaciones
    public function veeduria(): BelongsTo
    {
        return $this->belongsTo(Veeduria::class, 'veeduria_id');
    }

    public function operadorAsignado(): BelongsTo
    {
        return $this->belongsTo(Operador::class, 'operador_asignado_id');
    }

    public function archivos(): HasMany
    {
        return $this->hasMany(Archivo::class, 'tarea_id');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'entidad_id')
            ->where('entidad', 'tarea');
    }

    public function logsAuditoria(): HasMany
    {
        return $this->hasMany(LogAuditoria::class, 'entidad_id')
            ->where('entidad', 'tarea');
    }

    public function tareasDependientes(): HasMany
    {
        return $this->hasMany(Tarea::class, 'id')
            ->whereJsonContains('dependencias_tareas', $this->id);
    }

    // Scopes
    public function scopeActivas(Builder $query): void
    {
        $query->where('estado', '!=', 'completada');
    }

    public function scopeCompletadas(Builder $query): void
    {
        $query->where('estado', 'completada');
    }

    public function scopePendientes(Builder $query): void
    {
        $query->where('estado', 'pendiente');
    }

    public function scopeEnProceso(Builder $query): void
    {
        $query->where('estado', 'en_proceso');
    }

    public function scopeEnRevision(Builder $query): void
    {
        $query->where('estado', 'en_revision');
    }

    public function scopePorTipo(Builder $query, string $tipo): void
    {
        $query->where('tipo_tarea', $tipo);
    }

    public function scopePorCategoria(Builder $query, string $categoria): void
    {
        $query->where('categoria', $categoria);
    }

    public function scopePorPrioridad(Builder $query, string $prioridad): void
    {
        $query->where('prioridad', $prioridad);
    }

    public function scopePorVeeduria(Builder $query, int $veeduriaId): void
    {
        $query->where('veeduria_id', $veeduriaId);
    }

    public function scopePorOperador(Builder $query, int $operadorId): void
    {
        $query->where('operador_asignado_id', $operadorId);
    }

    public function scopeSinAsignar(Builder $query): void
    {
        $query->whereNull('operador_asignado_id');
    }

    public function scopeConFechaLimite(Builder $query): void
    {
        $query->whereNotNull('fecha_limite');
    }

    public function scopeVencidas(Builder $query): void
    {
        $query->where('fecha_limite', '<', now())
            ->where('estado', '!=', 'completada');
    }

    public function scopePorRangoFechas(Builder $query, string $fechaInicio, string $fechaFin): void
    {
        $query->whereBetween('fecha_creacion', [$fechaInicio, $fechaFin]);
    }

    public function scopePorNivelUrgencia(Builder $query, string $nivel): void
    {
        $query->where('nivel_urgencia', $nivel);
    }

    public function scopeConTags(Builder $query, array $tags): void
    {
        foreach ($tags as $tag) {
            $query->whereJsonContains('tags', $tag);
        }
    }

    public function scopePorProgreso(Builder $query, float $progresoMinimo, float $progresoMaximo = 100): void
    {
        $query->whereBetween('progreso', [$progresoMinimo, $progresoMaximo]);
    }

    public function scopeConDependencias(Builder $query): void
    {
        $query->whereNotNull('dependencias_tareas')
            ->where('dependencias_tareas', '!=', '[]');
    }

    // Accessors
    public function getTituloFormateadoAttribute(): string
    {
        return ucfirst($this->titulo);
    }

    public function getEstadoFormateadoAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->estado));
    }

    public function getPrioridadFormateadaAttribute(): string
    {
        return ucfirst($this->prioridad);
    }

    public function getTipoTareaFormateadoAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->tipo_tarea));
    }

    public function getCategoriaFormateadaAttribute(): string
    {
        return ucfirst($this->categoria);
    }

    public function getFechaCreacionFormateadaAttribute(): string
    {
        return $this->fecha_creacion ? $this->fecha_creacion->format('d/m/Y H:i') : 'N/A';
    }

    public function getFechaInicioFormateadaAttribute(): string
    {
        return $this->fecha_inicio ? $this->fecha_inicio->format('d/m/Y H:i') : 'N/A';
    }

    public function getFechaLimiteFormateadaAttribute(): string
    {
        return $this->fecha_limite ? $this->fecha_limite->format('d/m/Y H:i') : 'N/A';
    }

    public function getFechaCompletadaFormateadaAttribute(): string
    {
        return $this->fecha_completada ? $this->fecha_completada->format('d/m/Y H:i') : 'N/A';
    }

    public function getDiasTranscurridosAttribute(): int
    {
        if (!$this->fecha_inicio) {
            return $this->fecha_creacion ? $this->fecha_creacion->diffInDays(now()) : 0;
        }
        
        return $this->fecha_inicio->diffInDays(now());
    }

    public function getDiasRestantesAttribute(): int
    {
        if (!$this->fecha_limite) {
            return -1; // Sin fecha límite
        }
        
        $dias = $this->fecha_limite->diffInDays(now(), false);
        return $dias > 0 ? $dias : 0;
    }

    public function getEstaVencidaAttribute(): bool
    {
        return $this->fecha_limite && $this->fecha_limite < now() && $this->estado !== 'completada';
    }

    public function getProgresoFormateadoAttribute(): string
    {
        return number_format($this->progreso, 1) . '%';
    }

    public function getTiempoEstimadoFormateadoAttribute(): string
    {
        if (!$this->tiempo_estimado_horas) {
            return 'No estimado';
        }
        
        $horas = floor($this->tiempo_estimado_horas);
        $minutos = round(($this->tiempo_estimado_horas - $horas) * 60);
        
        if ($horas > 0 && $minutos > 0) {
            return "{$horas}h {$minutos}m";
        } elseif ($horas > 0) {
            return "{$horas}h";
        } else {
            return "{$minutos}m";
        }
    }

    public function getTiempoRealFormateadoAttribute(): string
    {
        if (!$this->tiempo_real_horas) {
            return 'No registrado';
        }
        
        $horas = floor($this->tiempo_real_horas);
        $minutos = round(($this->tiempo_real_horas - $horas) * 60);
        
        if ($horas > 0 && $minutos > 0) {
            return "{$horas}h {$minutos}m";
        } elseif ($horas > 0) {
            return "{$horas}h";
        } else {
            return "{$minutos}m";
        }
    }

    public function getEficienciaAttribute(): float
    {
        if (!$this->tiempo_estimado_horas || !$this->tiempo_real_horas) {
            return 0.0;
        }
        
        $eficiencia = ($this->tiempo_estimado_horas / $this->tiempo_real_horas) * 100;
        return round($eficiencia, 2);
    }

    public function getEficienciaFormateadaAttribute(): string
    {
        if ($this->eficiencia === 0.0) {
            return 'N/A';
        }
        
        return number_format($this->eficiencia, 1) . '%';
    }

    public function getChecklistCompletadoAttribute(): bool
    {
        if (empty($this->checklist)) {
            return true;
        }
        
        foreach ($this->checklist as $item) {
            if (!isset($item['completado']) || !$item['completado']) {
                return false;
            }
        }
        
        return true;
    }

    public function getChecklistProgresoAttribute(): float
    {
        if (empty($this->checklist)) {
            return 100.0;
        }
        
        $totalItems = count($this->checklist);
        $itemsCompletados = 0;
        
        foreach ($this->checklist as $item) {
            if (isset($item['completado']) && $item['completado']) {
                $itemsCompletados++;
            }
        }
        
        return round(($itemsCompletados / $totalItems) * 100, 2);
    }

    // Mutators
    public function setTituloAttribute($value): void
    {
        $this->attributes['titulo'] = ucfirst(strtolower($value));
    }

    public function setDescripcionAttribute($value): void
    {
        $this->attributes['descripcion'] = ucfirst(strtolower($value));
    }

    public function setTipoTareaAttribute($value): void
    {
        $this->attributes['tipo_tarea'] = strtolower($value);
    }

    public function setCategoriaAttribute($value): void
    {
        $this->attributes['categoria'] = strtolower($value);
    }

    public function setPrioridadAttribute($value): void
    {
        $this->attributes['prioridad'] = strtolower($value);
    }

    public function setEstadoAttribute($value): void
    {
        $this->attributes['estado'] = strtolower($value);
    }

    public function setTagsAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['tags'] = array_map('strtolower', $value);
        } else {
            $this->attributes['tags'] = [];
        }
    }

    public function setProgresoAttribute($value): void
    {
        $this->attributes['progreso'] = max(0, min(100, $value));
    }

    // Métodos
    public function asignarOperador(int $operadorId): bool
    {
        $operador = Operador::find($operadorId);
        if (!$operador || $operador->estado !== 'activo') {
            return false;
        }

        $this->update(['operador_asignado_id' => $operadorId]);
        
        // Crear notificación para el operador
        Notificacion::crear([
            'usuario_id' => $operadorId,
            'tipo_usuario' => 'operador',
            'titulo' => 'Nueva Tarea Asignada',
            'mensaje' => "Se te ha asignado la tarea: {$this->titulo}",
            'tipo' => 'asignacion',
            'entidad' => 'tarea',
            'entidad_id' => $this->id,
            'leida' => false
        ]);

        return true;
    }

    public function cambiarEstado(string $nuevoEstado): bool
    {
        $estadosValidos = ['pendiente', 'en_proceso', 'en_revision', 'completada', 'cancelada', 'suspendida'];
        
        if (!in_array($nuevoEstado, $estadosValidos)) {
            return false;
        }

        $estadoAnterior = $this->estado;
        $this->update(['estado' => $nuevoEstado]);

        if ($nuevoEstado === 'en_proceso' && !$this->fecha_inicio) {
            $this->update(['fecha_inicio' => now()]);
        }

        if ($nuevoEstado === 'completada') {
            $this->update([
                'fecha_completada' => now(),
                'progreso' => 100
            ]);
        }

        // Log de auditoría
        LogAuditoria::crear([
            'usuario_id' => auth()->id() ?? 1,
            'tipo_usuario' => auth()->user() ? (auth()->user() instanceof Operador ? 'operador' : 'cliente') : 'sistema',
            'accion' => 'cambiar_estado_tarea',
            'entidad' => 'tarea',
            'entidad_id' => $this->id,
            'datos_anteriores' => ['estado' => $estadoAnterior],
            'datos_nuevos' => ['estado' => $nuevoEstado],
            'ip_cliente' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return true;
    }

    public function actualizarProgreso(float $nuevoProgreso): bool
    {
        $progreso = max(0, min(100, $nuevoProgreso));
        
        $this->update(['progreso' => $progreso]);
        
        if ($progreso >= 100 && $this->estado !== 'completada') {
            $this->cambiarEstado('completada');
        }
        
        return true;
    }

    public function registrarTiempoReal(float $horas): bool
    {
        if ($horas < 0) {
            return false;
        }
        
        $tiempoActual = $this->tiempo_real_horas ?? 0;
        $this->update(['tiempo_real_horas' => $tiempoActual + $horas]);
        
        return true;
    }

    public function completarChecklistItem(int $indice): bool
    {
        if (!isset($this->checklist[$indice])) {
            return false;
        }
        
        $checklist = $this->checklist;
        $checklist[$indice]['completado'] = true;
        $checklist[$indice]['fecha_completado'] = now()->toISOString();
        
        $this->update(['checklist' => $checklist]);
        
        // Actualizar progreso basado en checklist
        $this->actualizarProgreso($this->checklist_progreso);
        
        return true;
    }

    public function agregarComentario(string $comentario, string $tipo = 'general'): bool
    {
        $comentarios = $this->comentarios ?? [];
        $comentarios[] = [
            'texto' => $comentario,
            'tipo' => $tipo,
            'fecha' => now()->toISOString(),
            'usuario_id' => auth()->id(),
            'tipo_usuario' => auth()->user() ? (auth()->user() instanceof Operador ? 'operador' : 'cliente') : 'sistema'
        ];
        
        $this->update(['comentarios' => $comentarios]);
        
        return true;
    }

    public function obtenerEstadisticas(): array
    {
        return [
            'dias_transcurridos' => $this->dias_transcurridos,
            'dias_restantes' => $this->dias_restantes,
            'esta_vencida' => $this->esta_vencida,
            'progreso' => $this->progreso,
            'progreso_formateado' => $this->progreso_formateado,
            'tiempo_estimado' => $this->tiempo_estimado_formateado,
            'tiempo_real' => $this->tiempo_real_formateado,
            'eficiencia' => $this->eficiencia_formateada,
            'checklist_completado' => $this->checklist_completado,
            'checklist_progreso' => $this->checklist_progreso,
            'total_archivos' => $this->archivos()->count(),
            'total_comentarios' => count($this->comentarios ?? [])
        ];
    }

    public function verificarDependencias(): bool
    {
        if (empty($this->dependencias_tareas)) {
            return true;
        }
        
        foreach ($this->dependencias_tareas as $tareaId) {
            $tarea = Tarea::find($tareaId);
            if (!$tarea || $tarea->estado !== 'completada') {
                return false;
            }
        }
        
        return true;
    }

    public function puedeIniciar(): bool
    {
        return $this->estado === 'pendiente' && $this->verificarDependencias();
    }

    public function tieneCoordenadas(): bool
    {
        return !is_null($this->coordenadas_lat) && !is_null($this->coordenadas_lng);
    }

    public function obtenerCoordenadas(): array
    {
        if ($this->tieneCoordenadas()) {
            return [
                'lat' => (float) $this->coordenadas_lat,
                'lng' => (float) $this->coordenadas_lng
            ];
        }
        
        return [];
    }

    // Boot method para eventos del modelo
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tarea) {
            if (empty($tarea->codigo_tarea)) {
                $tarea->codigo_tarea = static::generarCodigoTarea();
            }
            
            if (empty($tarea->fecha_creacion)) {
                $tarea->fecha_creacion = now();
            }
            
            if (empty($tarea->estado)) {
                $tarea->estado = 'pendiente';
            }
            
            if (empty($tarea->progreso)) {
                $tarea->progreso = 0;
            }
        });

        static::updating(function ($tarea) {
            // Log de cambios importantes
            if ($tarea->isDirty('estado')) {
                LogAuditoria::crear([
                    'usuario_id' => auth()->id() ?? 1,
                    'tipo_usuario' => auth()->user() ? (auth()->user() instanceof Operador ? 'operador' : 'cliente') : 'sistema',
                    'accion' => 'cambiar_estado_tarea',
                    'entidad' => 'tarea',
                    'entidad_id' => $tarea->id,
                    'datos_anteriores' => ['estado' => $tarea->getOriginal('estado')],
                    'datos_nuevos' => ['estado' => $tarea->estado],
                    'ip_cliente' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]);
            }
        });
    }

    // Método estático para generar código único
    protected static function generarCodigoTarea(): string
    {
        do {
            $codigo = 'TAR-' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (static::where('codigo_tarea', $codigo)->exists());

        return $codigo;
    }
}
