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

class Reporte extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'reportes';

    protected $fillable = [
        'codigo_reporte',
        'titulo',
        'descripcion',
        'tipo_reporte',
        'categoria',
        'estado',
        'usuario_id',
        'tipo_usuario',
        'fecha_creacion',
        'fecha_generacion',
        'fecha_completado',
        'parametros_reporte',
        'filtros_aplicados',
        'rango_fechas',
        'formato_salida',
        'ruta_archivo',
        'tamanio_archivo',
        'hash_archivo',
        'hash_verificacion',
        'metadatos',
        'tags',
        'nivel_prioridad',
        'tiempo_estimado_generacion',
        'tiempo_real_generacion',
        'recursos_utilizados',
        'notas_internas',
        'version_reporte',
        'plantilla_utilizada',
        'configuracion_exportacion',
        'estadisticas_generadas',
        'errores_encountered',
        'advertencias',
        'comentarios_usuario',
        'calificacion_usuario'
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'fecha_generacion' => 'datetime',
        'fecha_completado' => 'datetime',
        'parametros_reporte' => 'array',
        'filtros_aplicados' => 'array',
        'rango_fechas' => 'array',
        'metadatos' => 'array',
        'tags' => 'array',
        'recursos_utilizados' => 'array',
        'configuracion_exportacion' => 'array',
        'estadisticas_generadas' => 'array',
        'errores_encountered' => 'array',
        'advertencias' => 'array',
        'tamanio_archivo' => 'integer',
        'nivel_prioridad' => 'integer',
        'tiempo_estimado_generacion' => 'decimal:8,2',
        'tiempo_real_generacion' => 'decimal:8,2',
        'version_reporte' => 'integer',
        'calificacion_usuario' => 'decimal:3,2'
    ];

    // Relaciones
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'usuario_id')
            ->where('tipo_usuario', 'cliente');
    }

    public function operador(): BelongsTo
    {
        return $this->belongsTo(Operador::class, 'usuario_id')
            ->where('tipo_usuario', 'operador');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'entidad_id')
            ->where('entidad', 'reporte');
    }

    public function logsAuditoria(): HasMany
    {
        return $this->hasMany(LogAuditoria::class, 'entidad_id')
            ->where('entidad', 'reporte');
    }

    // Scopes
    public function scopeActivos(Builder $query): void
    {
        $query->where('estado', '!=', 'eliminado');
    }

    public function scopePendientes(Builder $query): void
    {
        $query->where('estado', 'pendiente');
    }

    public function scopeEnGeneracion(Builder $query): void
    {
        $query->where('estado', 'en_generacion');
    }

    public function scopeCompletados(Builder $query): void
    {
        $query->where('estado', 'completado');
    }

    public function scopeConError(Builder $query): void
    {
        $query->where('estado', 'error');
    }

    public function scopePorTipo(Builder $query, string $tipo): void
    {
        $query->where('tipo_reporte', $tipo);
    }

    public function scopePorCategoria(Builder $query, string $categoria): void
    {
        $query->where('categoria', $categoria);
    }

    public function scopePorUsuario(Builder $query, int $usuarioId): void
    {
        $query->where('usuario_id', $usuarioId);
    }

    public function scopePorTipoUsuario(Builder $query, string $tipoUsuario): void
    {
        $query->where('tipo_usuario', $tipoUsuario);
    }

    public function scopePorEstado(Builder $query, string $estado): void
    {
        $query->where('estado', $estado);
    }

    public function scopePorFormato(Builder $query, string $formato): void
    {
        $query->where('formato_salida', $formato);
    }

    public function scopePorRangoFechas(Builder $query, string $fechaInicio, string $fechaFin): void
    {
        $query->whereBetween('fecha_creacion', [$fechaInicio, $fechaFin]);
    }

    public function scopePorNivelPrioridad(Builder $query, int $nivel): void
    {
        $query->where('nivel_prioridad', $nivel);
    }

    public function scopeConTags(Builder $query, array $tags): void
    {
        foreach ($tags as $tag) {
            $query->whereJsonContains('tags', $tag);
        }
    }

    public function scopePorVersion(Builder $query, int $version): void
    {
        $query->where('version_reporte', $version);
    }

    public function scopeRecientes(Builder $query, int $dias = 7): void
    {
        $query->where('fecha_creacion', '>=', now()->subDays($dias));
    }

    public function scopeAntiguos(Builder $query, int $dias = 30): void
    {
        $query->where('fecha_creacion', '<', now()->subDays($dias));
    }

    public function scopeConArchivo(Builder $query): void
    {
        $query->whereNotNull('ruta_archivo');
    }

    public function scopeSinArchivo(Builder $query): void
    {
        $query->whereNull('ruta_archivo');
    }

    // Accessors
    public function getTituloFormateadoAttribute(): string
    {
        return ucfirst($this->titulo);
    }

    public function getDescripcionFormateadaAttribute(): string
    {
        return ucfirst($this->descripcion);
    }

    public function getEstadoFormateadoAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->estado));
    }

    public function getTipoReporteFormateadoAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->tipo_reporte));
    }

    public function getCategoriaFormateadaAttribute(): string
    {
        return ucfirst($this->categoria);
    }

    public function getFormatoSalidaFormateadoAttribute(): string
    {
        return strtoupper($this->formato_salida);
    }

    public function getFechaCreacionFormateadaAttribute(): string
    {
        return $this->fecha_creacion ? $this->fecha_creacion->format('d/m/Y H:i') : 'N/A';
    }

    public function getFechaGeneracionFormateadaAttribute(): string
    {
        return $this->fecha_generacion ? $this->fecha_generacion->format('d/m/Y H:i') : 'N/A';
    }

    public function getFechaCompletadoFormateadaAttribute(): string
    {
        return $this->fecha_completado ? $this->fecha_completado->format('d/m/Y H:i') : 'N/A';
    }

    public function getDiasTranscurridosAttribute(): int
    {
        return $this->fecha_creacion ? $this->fecha_creacion->diffInDays(now()) : 0;
    }

    public function getDiasDesdeGeneracionAttribute(): int
    {
        if (!$this->fecha_generacion) {
            return -1;
        }
        
        return $this->fecha_generacion->diffInDays(now());
    }

    public function getTamanioArchivoFormateadoAttribute(): string
    {
        if (!$this->tamanio_archivo) {
            return 'No disponible';
        }
        
        $unidades = ['B', 'KB', 'MB', 'GB'];
        $tamanio = $this->tamanio_archivo;
        $unidad = 0;
        
        while ($tamanio >= 1024 && $unidad < count($unidades) - 1) {
            $tamanio /= 1024;
            $unidad++;
        }
        
        return round($tamanio, 2) . ' ' . $unidades[$unidad];
    }

    public function getTiempoEstimadoFormateadoAttribute(): string
    {
        if (!$this->tiempo_estimado_generacion) {
            return 'No estimado';
        }
        
        if ($this->tiempo_estimado_generacion < 60) {
            return round($this->tiempo_estimado_generacion, 2) . ' segundos';
        } else {
            return round($this->tiempo_estimado_generacion / 60, 2) . ' minutos';
        }
    }

    public function getTiempoRealFormateadoAttribute(): string
    {
        if (!$this->tiempo_real_generacion) {
            return 'No registrado';
        }
        
        if ($this->tiempo_real_generacion < 60) {
            return round($this->tiempo_real_generacion, 2) . ' segundos';
        } else {
            return round($this->tiempo_real_generacion / 60, 2) . ' minutos';
        }
    }

    public function getEficienciaGeneracionAttribute(): float
    {
        if (!$this->tiempo_estimado_generacion || !$this->tiempo_real_generacion) {
            return 0.0;
        }
        
        $eficiencia = ($this->tiempo_estimado_generacion / $this->tiempo_real_generacion) * 100;
        return round($eficiencia, 2);
    }

    public function getEficienciaFormateadaAttribute(): string
    {
        if ($this->eficiencia_generacion === 0.0) {
            return 'N/A';
        }
        
        return number_format($this->eficiencia_generacion, 1) . '%';
    }

    public function getNivelPrioridadFormateadoAttribute(): string
    {
        $niveles = [
            1 => 'Baja',
            2 => 'Media',
            3 => 'Alta',
            4 => 'Urgente',
            5 => 'Crítica'
        ];
        
        return $niveles[$this->nivel_prioridad] ?? 'No especificada';
    }

    public function getVersionFormateadaAttribute(): string
    {
        return 'v' . $this->version_reporte;
    }

    public function getCalificacionFormateadaAttribute(): string
    {
        if (!$this->calificacion_usuario) {
            return 'Sin calificar';
        }
        
        $estrellas = str_repeat('★', round($this->calificacion_usuario));
        $estrellasVacias = str_repeat('☆', 5 - round($this->calificacion_usuario));
        
        return $estrellas . $estrellasVacias . ' (' . $this->calificacion_usuario . '/5)';
    }

    public function getEstaPendienteAttribute(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function getEstaEnGeneracionAttribute(): bool
    {
        return $this->estado === 'en_generacion';
    }

    public function getEstaCompletadoAttribute(): bool
    {
        return $this->estado === 'completado';
    }

    public function getTieneErrorAttribute(): bool
    {
        return $this->estado === 'error';
    }

    public function getPuedeGenerarAttribute(): bool
    {
        return in_array($this->estado, ['pendiente', 'error']);
    }

    public function getPuedeDescargarAttribute(): bool
    {
        return $this->estado === 'completado' && !empty($this->ruta_archivo);
    }

    public function getPuedeEliminarAttribute(): bool
    {
        return in_array($this->estado, ['pendiente', 'error', 'completado']);
    }

    public function getTieneArchivoAttribute(): bool
    {
        return !empty($this->ruta_archivo);
    }

    public function getUrlDescargaAttribute(): string
    {
        if (!$this->puede_descargar) {
            return '';
        }
        
        return route('reportes.descargar', $this->id);
    }

    public function getTagsFormateadosAttribute(): array
    {
        return $this->tags ?? [];
    }

    public function getMetadatosFormateadosAttribute(): array
    {
        return $this->metadatos ?? [];
    }

    public function getParametrosFormateadosAttribute(): array
    {
        return $this->parametros_reporte ?? [];
    }

    public function getFiltrosFormateadosAttribute(): array
    {
        return $this->filtros_aplicados ?? [];
    }

    public function getEstadisticasFormateadasAttribute(): array
    {
        return $this->estadisticas_generadas ?? [];
    }

    public function getErroresFormateadosAttribute(): array
    {
        return $this->errores_encountered ?? [];
    }

    public function getAdvertenciasFormateadasAttribute(): array
    {
        return $this->advertencias ?? [];
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

    public function setTipoReporteAttribute($value): void
    {
        $this->attributes['tipo_reporte'] = strtolower($value);
    }

    public function setCategoriaAttribute($value): void
    {
        $this->attributes['categoria'] = strtolower($value);
    }

    public function setEstadoAttribute($value): void
    {
        $this->attributes['estado'] = strtolower($value);
    }

    public function setFormatoSalidaAttribute($value): void
    {
        $this->attributes['formato_salida'] = strtolower($value);
    }

    public function setTagsAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['tags'] = array_map('strtolower', $value);
        } else {
            $this->attributes['tags'] = [];
        }
    }

    // Métodos
    public function cambiarEstado(string $nuevoEstado): bool
    {
        $estadosValidos = ['pendiente', 'en_generacion', 'completado', 'error', 'cancelado', 'eliminado'];
        
        if (!in_array($nuevoEstado, $estadosValidos)) {
            return false;
        }

        $estadoAnterior = $this->estado;
        $this->update(['estado' => $nuevoEstado]);

        if ($nuevoEstado === 'en_generacion') {
            $this->update(['fecha_generacion' => now()]);
        }

        if ($nuevoEstado === 'completado') {
            $this->update(['fecha_completado' => now()]);
        }

        // Log de auditoría
        LogAuditoria::crear([
            'usuario_id' => auth()->id() ?? $this->usuario_id,
            'tipo_usuario' => auth()->user() ? (auth()->user() instanceof Operador ? 'operador' : 'cliente') : $this->tipo_usuario,
            'accion' => 'cambiar_estado_reporte',
            'entidad' => 'reporte',
            'entidad_id' => $this->id,
            'datos_anteriores' => ['estado' => $estadoAnterior],
            'datos_nuevos' => ['estado' => $nuevoEstado],
            'ip_cliente' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return true;
    }

    public function iniciarGeneracion(): bool
    {
        if (!$this->puede_generar) {
            return false;
        }

        $this->cambiarEstado('en_generacion');
        return true;
    }

    public function completarGeneracion(string $rutaArchivo, int $tamanioArchivo, array $estadisticas = []): bool
    {
        if (!$this->esta_en_generacion) {
            return false;
        }

        $datosActualizacion = [
            'estado' => 'completado',
            'ruta_archivo' => $rutaArchivo,
            'tamanio_archivo' => $tamanioArchivo,
            'fecha_completado' => now()
        ];

        if (!empty($estadisticas)) {
            $datosActualizacion['estadisticas_generadas'] = $estadisticas;
        }

        $this->update($datosActualizacion);

        // Crear notificación para el usuario
        Notificacion::crear([
            'usuario_id' => $this->usuario_id,
            'tipo_usuario' => $this->tipo_usuario,
            'titulo' => 'Reporte Completado',
            'mensaje' => "Tu reporte '{$this->titulo}' ha sido generado exitosamente",
            'tipo' => 'completado',
            'entidad' => 'reporte',
            'entidad_id' => $this->id,
            'leida' => false
        ]);

        return true;
    }

    public function marcarError(array $errores, array $advertencias = []): bool
    {
        if (!$this->esta_en_generacion) {
            return false;
        }

        $datosActualizacion = [
            'estado' => 'error',
            'errores_encountered' => $errores
        ];

        if (!empty($advertencias)) {
            $datosActualizacion['advertencias'] = $advertencias;
        }

        $this->update($datosActualizacion);

        // Crear notificación para el usuario
        Notificacion::crear([
            'usuario_id' => $this->usuario_id,
            'tipo_usuario' => $this->tipo_usuario,
            'titulo' => 'Error en Reporte',
            'mensaje' => "Hubo un error al generar tu reporte '{$this->titulo}'",
            'tipo' => 'error',
            'entidad' => 'reporte',
            'entidad_id' => $this->id,
            'leida' => false
        ]);

        return true;
    }

    public function registrarTiempoGeneracion(float $tiempoReal): bool
    {
        if ($tiempoReal < 0) {
            return false;
        }

        $this->update(['tiempo_real_generacion' => $tiempoReal]);
        return true;
    }

    public function actualizarMetadatos(array $nuevosMetadatos): bool
    {
        $metadatosActuales = $this->metadatos ?? [];
        $metadatosActualizados = array_merge($metadatosActuales, $nuevosMetadatos);
        
        $this->update(['metadatos' => $metadatosActualizados]);
        return true;
    }

    public function agregarTag(string $tag): bool
    {
        $tags = $this->tags ?? [];
        if (!in_array(strtolower($tag), $tags)) {
            $tags[] = strtolower($tag);
            $this->update(['tags' => $tags]);
        }
        
        return true;
    }

    public function removerTag(string $tag): bool
    {
        $tags = $this->tags ?? [];
        $tagLower = strtolower($tag);
        
        if (($key = array_search($tagLower, $tags)) !== false) {
            unset($tags[$key]);
            $this->update(['tags' => array_values($tags)]);
        }
        
        return true;
    }

    public function establecerPrioridad(int $nuevaPrioridad): bool
    {
        if ($nuevaPrioridad < 1 || $nuevaPrioridad > 5) {
            return false;
        }

        $this->update(['nivel_prioridad' => $nuevaPrioridad]);
        return true;
    }

    public function generarHashVerificacion(): string
    {
        $datos = [
            'id' => $this->id,
            'codigo_reporte' => $this->codigo_reporte,
            'titulo' => $this->titulo,
            'tipo_reporte' => $this->tipo_reporte,
            'fecha_creacion' => $this->fecha_creacion->toISOString(),
            'usuario_id' => $this->usuario_id
        ];
        
        $hash = hash('sha256', json_encode($datos));
        $this->update(['hash_verificacion' => $hash]);
        
        return $hash;
    }

    public function verificarIntegridad(): bool
    {
        if (!$this->hash_verificacion) {
            return false;
        }
        
        $hashCalculado = $this->generarHashVerificacion();
        return $hashCalculado === $this->hash_verificacion;
    }

    public function obtenerEstadisticas(): array
    {
        return [
            'dias_transcurridos' => $this->dias_transcurridos,
            'dias_desde_generacion' => $this->dias_desde_generacion,
            'estado' => $this->estado_formateado,
            'tipo_reporte' => $this->tipo_reporte_formateado,
            'categoria' => $this->categoria_formateada,
            'formato_salida' => $this->formato_salida_formateado,
            'tamanio_archivo' => $this->tamanio_archivo_formateado,
            'tiempo_estimado' => $this->tiempo_estimado_formateado,
            'tiempo_real' => $this->tiempo_real_formateado,
            'eficiencia' => $this->eficiencia_formateada,
            'nivel_prioridad' => $this->nivel_prioridad_formateado,
            'version' => $this->version_formateada,
            'calificacion' => $this->calificacion_formateada,
            'esta_pendiente' => $this->esta_pendiente,
            'esta_en_generacion' => $this->esta_en_generacion,
            'esta_completado' => $this->esta_completado,
            'tiene_error' => $this->tiene_error,
            'puede_generar' => $this->puede_generar,
            'puede_descargar' => $this->puede_descargar,
            'puede_eliminar' => $this->puede_eliminar,
            'tiene_archivo' => $this->tiene_archivo,
            'total_tags' => count($this->tags ?? []),
            'total_metadatos' => count($this->metadatos ?? [])
        ];
    }

    // Métodos estáticos
    public static function crear(array $datos): Reporte
    {
        $reporte = static::create($datos);
        
        // Log de auditoría
        LogAuditoria::crear([
            'usuario_id' => $reporte->usuario_id,
            'tipo_usuario' => $reporte->tipo_usuario,
            'accion' => 'crear_reporte',
            'entidad' => 'reporte',
            'entidad_id' => $reporte->id,
            'datos_anteriores' => [],
            'datos_nuevos' => $datos,
            'ip_cliente' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
        
        return $reporte;
    }

    public static function crearParaUsuario(int $usuarioId, string $tipoUsuario, array $datos): Reporte
    {
        $datos['usuario_id'] = $usuarioId;
        $datos['tipo_usuario'] = $tipoUsuario;
        
        return static::crear($datos);
    }

    public static function obtenerEstadisticasGenerales(): array
    {
        $totalReportes = static::count();
        $reportesHoy = static::whereDate('fecha_creacion', today())->count();
        $reportesSemana = static::where('fecha_creacion', '>=', now()->subWeek())->count();
        $reportesMes = static::where('fecha_creacion', '>=', now()->subMonth())->count();
        
        $estados = static::select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->get();
        
        $tiposMasComunes = static::select('tipo_reporte', DB::raw('count(*) as total'))
            ->groupBy('tipo_reporte')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();
        
        $formatosMasComunes = static::select('formato_salida', DB::raw('count(*) as total'))
            ->groupBy('formato_salida')
            ->orderBy('total', 'desc')
            ->get();
        
        return [
            'total_reportes' => $totalReportes,
            'reportes_hoy' => $reportesHoy,
            'reportes_semana' => $reportesSemana,
            'reportes_mes' => $reportesMes,
            'estados' => $estados,
            'tipos_mas_comunes' => $tiposMasComunes,
            'formatos_mas_comunes' => $formatosMasComunes
        ];
    }

    // Boot method para eventos del modelo
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reporte) {
            if (empty($reporte->codigo_reporte)) {
                $reporte->codigo_reporte = static::generarCodigoReporte();
            }
            
            if (empty($reporte->fecha_creacion)) {
                $reporte->fecha_creacion = now();
            }
            
            if (empty($reporte->estado)) {
                $reporte->estado = 'pendiente';
            }
            
            if (empty($reporte->nivel_prioridad)) {
                $reporte->nivel_prioridad = 2; // Media por defecto
            }
            
            if (empty($reporte->version_reporte)) {
                $reporte->version_reporte = 1;
            }
            
            if (empty($reporte->formato_salida)) {
                $reporte->formato_salida = 'pdf';
            }
        });

        static::updating(function ($reporte) {
            // Log de cambios importantes
            if ($reporte->isDirty('estado')) {
                LogAuditoria::crear([
                    'usuario_id' => auth()->id() ?? $reporte->usuario_id,
                    'tipo_usuario' => auth()->user() ? (auth()->user() instanceof Operador ? 'operador' : 'cliente') : $reporte->tipo_usuario,
                    'accion' => 'cambiar_estado_reporte',
                    'entidad' => 'reporte',
                    'entidad_id' => $reporte->id,
                    'datos_anteriores' => ['estado' => $reporte->getOriginal('estado')],
                    'datos_nuevos' => ['estado' => $reporte->estado],
                    'ip_cliente' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]);
            }
        });
    }

    // Método estático para generar código único
    protected static function generarCodigoReporte(): string
    {
        do {
            $codigo = 'REP-' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (static::where('codigo_reporte', $codigo)->exists());

        return $codigo;
    }
}
