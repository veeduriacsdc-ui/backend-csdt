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

class Notificacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'notificaciones';

    protected $fillable = [
        'codigo_notificacion',
        'titulo',
        'mensaje',
        'tipo',
        'categoria',
        'estado',
        'usuario_id',
        'tipo_usuario',
        'entidad',
        'entidad_id',
        'leida',
        'fecha_lectura',
        'fecha_creacion',
        'fecha_expiracion',
        'prioridad',
        'nivel_urgencia',
        'canal_envio',
        'intentos_envio',
        'max_intentos',
        'fecha_envio',
        'fecha_entrega',
        'metadatos',
        'tags',
        'acciones_disponibles',
        'url_redireccion',
        'icono',
        'color',
        'sonido',
        'vibracion',
        'notas_internas'
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'fecha_lectura' => 'datetime',
        'fecha_expiracion' => 'datetime',
        'fecha_envio' => 'datetime',
        'fecha_entrega' => 'datetime',
        'metadatos' => 'array',
        'tags' => 'array',
        'acciones_disponibles' => 'array',
        'leida' => 'boolean',
        'intentos_envio' => 'integer',
        'max_intentos' => 'integer',
        'prioridad' => 'integer',
        'nivel_urgencia' => 'integer'
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

    public function veeduria(): BelongsTo
    {
        return $this->belongsTo(Veeduria::class, 'entidad_id')
            ->where('entidad', 'veeduria');
    }

    public function tarea(): BelongsTo
    {
        return $this->belongsTo(Tarea::class, 'entidad_id')
            ->where('entidad', 'tarea');
    }

    public function donacion(): BelongsTo
    {
        return $this->belongsTo(Donacion::class, 'entidad_id')
            ->where('entidad', 'donacion');
    }

    public function narracion(): BelongsTo
    {
        return $this->belongsTo(NarracionConsejoIa::class, 'entidad_id')
            ->where('entidad', 'narracion_consejo_ia');
    }

    public function archivo(): BelongsTo
    {
        return $this->belongsTo(Archivo::class, 'entidad_id')
            ->where('entidad', 'archivo');
    }

    public function logsAuditoria(): HasMany
    {
        return $this->hasMany(LogAuditoria::class, 'entidad_id')
            ->where('entidad', 'notificacion');
    }

    // Scopes
    public function scopeActivas(Builder $query): void
    {
        $query->where('estado', 'activa');
    }

    public function scopeInactivas(Builder $query): void
    {
        $query->where('estado', 'inactiva');
    }

    public function scopeLeidas(Builder $query): void
    {
        $query->where('leida', true);
    }

    public function scopeNoLeidas(Builder $query): void
    {
        $query->where('leida', false);
    }

    public function scopePorTipo(Builder $query, string $tipo): void
    {
        $query->where('tipo', $tipo);
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

    public function scopePorEntidad(Builder $query, string $entidad): void
    {
        $query->where('entidad', $entidad);
    }

    public function scopePorEntidadId(Builder $query, int $entidadId): void
    {
        $query->where('entidad_id', $entidadId);
    }

    public function scopePorPrioridad(Builder $query, int $prioridad): void
    {
        $query->where('prioridad', $prioridad);
    }

    public function scopePorNivelUrgencia(Builder $query, int $nivel): void
    {
        $query->where('nivel_urgencia', $nivel);
    }

    public function scopePorCanal(Builder $query, string $canal): void
    {
        $query->where('canal_envio', $canal);
    }

    public function scopePorRangoFechas(Builder $query, string $fechaInicio, string $fechaFin): void
    {
        $query->whereBetween('fecha_creacion', [$fechaInicio, $fechaFin]);
    }

    public function scopeConFechaLimite(Builder $query): void
    {
        $query->whereNotNull('fecha_expiracion');
    }

    public function scopeExpiradas(Builder $query): void
    {
        $query->where('fecha_expiracion', '<', now());
    }

    public function scopeNoExpiradas(Builder $query): void
    {
        $query->where(function ($q) {
            $q->whereNull('fecha_expiracion')
              ->orWhere('fecha_expiracion', '>=', now());
        });
    }

    public function scopePorEstado(Builder $query, string $estado): void
    {
        $query->where('estado', $estado);
    }

    public function scopeConTags(Builder $query, array $tags): void
    {
        foreach ($tags as $tag) {
            $query->whereJsonContains('tags', $tag);
        }
    }

    public function scopePorIntentos(Builder $query, int $maxIntentos): void
    {
        $query->where('intentos_envio', '>=', $maxIntentos);
    }

    public function scopeSinEntregar(Builder $query): void
    {
        $query->whereNull('fecha_entrega');
    }

    public function scopeEntregadas(Builder $query): void
    {
        $query->whereNotNull('fecha_entrega');
    }

    // Accessors
    public function getTituloFormateadoAttribute(): string
    {
        return ucfirst($this->titulo);
    }

    public function getMensajeFormateadoAttribute(): string
    {
        return ucfirst($this->mensaje);
    }

    public function getEstadoFormateadoAttribute(): string
    {
        return ucfirst($this->estado);
    }

    public function getTipoFormateadoAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->tipo));
    }

    public function getCategoriaFormateadaAttribute(): string
    {
        return ucfirst($this->categoria);
    }

    public function getFechaCreacionFormateadaAttribute(): string
    {
        return $this->fecha_creacion ? $this->fecha_creacion->format('d/m/Y H:i') : 'N/A';
    }

    public function getFechaLecturaFormateadaAttribute(): string
    {
        return $this->fecha_lectura ? $this->fecha_lectura->format('d/m/Y H:i') : 'N/A';
    }

    public function getFechaExpiracionFormateadaAttribute(): string
    {
        return $this->fecha_expiracion ? $this->fecha_expiracion->format('d/m/Y H:i') : 'N/A';
    }

    public function getFechaEnvioFormateadaAttribute(): string
    {
        return $this->fecha_envio ? $this->fecha_envio->format('d/m/Y H:i') : 'N/A';
    }

    public function getFechaEntregaFormateadaAttribute(): string
    {
        return $this->fecha_entrega ? $this->fecha_entrega->format('d/m/Y H:i') : 'N/A';
    }

    public function getDiasTranscurridosAttribute(): int
    {
        return $this->fecha_creacion ? $this->fecha_creacion->diffInDays(now()) : 0;
    }

    public function getDiasRestantesExpiracionAttribute(): int
    {
        if (!$this->fecha_expiracion) {
            return -1; // Sin fecha de expiración
        }
        
        $dias = $this->fecha_expiracion->diffInDays(now(), false);
        return $dias > 0 ? $dias : 0;
    }

    public function getEstaExpiradaAttribute(): bool
    {
        return $this->fecha_expiracion && $this->fecha_expiracion < now();
    }

    public function getEstaLeidaAttribute(): bool
    {
        return $this->leida;
    }

    public function getEstaPendienteAttribute(): bool
    {
        return !$this->leida && !$this->esta_expirada;
    }

    public function getEstaEntregadaAttribute(): bool
    {
        return !is_null($this->fecha_entrega);
    }

    public function getPuedeLeerAttribute(): bool
    {
        return !$this->leida && !$this->esta_expirada;
    }

    public function getPuedeEliminarAttribute(): bool
    {
        return true; // Siempre se puede eliminar
    }

    public function getPrioridadFormateadaAttribute(): string
    {
        $prioridades = [
            1 => 'Baja',
            2 => 'Media',
            3 => 'Alta',
            4 => 'Crítica',
            5 => 'Urgente'
        ];
        
        return $prioridades[$this->prioridad] ?? 'No especificada';
    }

    public function getNivelUrgenciaFormateadoAttribute(): string
    {
        $niveles = [
            1 => 'Bajo',
            2 => 'Normal',
            3 => 'Alto',
            4 => 'Muy Alto',
            5 => 'Crítico'
        ];
        
        return $niveles[$this->nivel_urgencia] ?? 'No especificado';
    }

    public function getCanalFormateadoAttribute(): string
    {
        $canales = [
            'sistema' => 'Sistema',
            'email' => 'Correo Electrónico',
            'sms' => 'SMS',
            'push' => 'Notificación Push',
            'webhook' => 'Webhook',
            'api' => 'API'
        ];
        
        return $canales[$this->canal_envio] ?? 'No especificado';
    }

    public function getEstadoEnvioAttribute(): string
    {
        if ($this->esta_entregada) {
            return 'Entregada';
        }
        
        if ($this->intentos_envio >= $this->max_intentos) {
            return 'Fallida';
        }
        
        if ($this->fecha_envio) {
            return 'Enviada';
        }
        
        return 'Pendiente';
    }

    public function getTagsFormateadosAttribute(): array
    {
        return $this->tags ?? [];
    }

    public function getAccionesDisponiblesFormateadasAttribute(): array
    {
        return $this->acciones_disponibles ?? [];
    }

    public function getMetadatosFormateadosAttribute(): array
    {
        return $this->metadatos ?? [];
    }

    public function getIconoFormateadoAttribute(): string
    {
        $iconos = [
            'info' => 'ℹ️',
            'success' => '✅',
            'warning' => '⚠️',
            'error' => '❌',
            'question' => '❓',
            'star' => '⭐',
            'heart' => '❤️',
            'bell' => '🔔',
            'clock' => '⏰',
            'check' => '✓'
        ];
        
        return $iconos[$this->icono] ?? '📢';
    }

    public function getColorFormateadoAttribute(): string
    {
        $colores = [
            'primary' => '#3B82F6',
            'secondary' => '#6B7280',
            'success' => '#10B981',
            'danger' => '#EF4444',
            'warning' => '#F59E0B',
            'info' => '#06B6D4',
            'light' => '#F3F4F6',
            'dark' => '#1F2937'
        ];
        
        return $colores[$this->color] ?? '#3B82F6';
    }

    // Mutators
    public function setTituloAttribute($value): void
    {
        $this->attributes['titulo'] = ucfirst(strtolower($value));
    }

    public function setMensajeAttribute($value): void
    {
        $this->attributes['mensaje'] = ucfirst(strtolower($value));
    }

    public function setTipoAttribute($value): void
    {
        $this->attributes['tipo'] = strtolower($value);
    }

    public function setCategoriaAttribute($value): void
    {
        $this->attributes['categoria'] = strtolower($value);
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

    public function setCanalEnvioAttribute($value): void
    {
        $this->attributes['canal_envio'] = strtolower($value);
    }

    // Métodos
    public function marcarComoLeida(): bool
    {
        if ($this->leida) {
            return true; // Ya está leída
        }

        $this->update([
            'leida' => true,
            'fecha_lectura' => now()
        ]);

        // Log de auditoría
        LogAuditoria::crear([
            'usuario_id' => $this->usuario_id,
            'tipo_usuario' => $this->tipo_usuario,
            'accion' => 'marcar_notificacion_leida',
            'entidad' => 'notificacion',
            'entidad_id' => $this->id,
            'datos_anteriores' => ['leida' => false],
            'datos_nuevos' => ['leida' => true, 'fecha_lectura' => now()],
            'ip_cliente' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return true;
    }

    public function marcarComoNoLeida(): bool
    {
        if (!$this->leida) {
            return true; // Ya no está leída
        }

        $this->update([
            'leida' => false,
            'fecha_lectura' => null
        ]);

        return true;
    }

    public function cambiarEstado(string $nuevoEstado): bool
    {
        $estadosValidos = ['activa', 'inactiva', 'archivada', 'eliminada'];
        
        if (!in_array($nuevoEstado, $estadosValidos)) {
            return false;
        }

        $estadoAnterior = $this->estado;
        $this->update(['estado' => $nuevoEstado]);

        // Log de auditoría
        LogAuditoria::crear([
            'usuario_id' => auth()->id() ?? $this->usuario_id,
            'tipo_usuario' => auth()->user() ? (auth()->user() instanceof Operador ? 'operador' : 'cliente') : $this->tipo_usuario,
            'accion' => 'cambiar_estado_notificacion',
            'entidad' => 'notificacion',
            'entidad_id' => $this->id,
            'datos_anteriores' => ['estado' => $estadoAnterior],
            'datos_nuevos' => ['estado' => $nuevoEstado],
            'ip_cliente' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return true;
    }

    public function registrarEnvio(): bool
    {
        $this->update([
            'fecha_envio' => now(),
            'intentos_envio' => $this->intentos_envio + 1
        ]);

        return true;
    }

    public function registrarEntrega(): bool
    {
        $this->update(['fecha_entrega' => now()]);
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

    public function establecerFechaExpiracion(Carbon $fecha): bool
    {
        if ($fecha <= now()) {
            return false;
        }
        
        $this->update(['fecha_expiracion' => $fecha]);
        return true;
    }

    public function renovarExpiracion(int $diasAdicionales = 30): bool
    {
        $nuevaFecha = now()->addDays($diasAdicionales);
        return $this->establecerFechaExpiracion($nuevaFecha);
    }

    public function obtenerEstadisticas(): array
    {
        return [
            'dias_transcurridos' => $this->dias_transcurridos,
            'dias_restantes_expiracion' => $this->dias_restantes_expiracion,
            'esta_expirada' => $this->esta_expirada,
            'esta_leida' => $this->esta_leida,
            'esta_pendiente' => $this->esta_pendiente,
            'esta_entregada' => $this->esta_entregada,
            'puede_leer' => $this->puede_leer,
            'puede_eliminar' => $this->puede_eliminar,
            'prioridad' => $this->prioridad_formateada,
            'nivel_urgencia' => $this->nivel_urgencia_formateado,
            'canal' => $this->canal_formateado,
            'estado_envio' => $this->estado_envio,
            'total_tags' => count($this->tags ?? []),
            'total_acciones' => count($this->acciones_disponibles ?? [])
        ];
    }

    // Métodos estáticos
    public static function crear(array $datos): Notificacion
    {
        $notificacion = static::create($datos);
        
        // Log de auditoría
        LogAuditoria::crear([
            'usuario_id' => $notificacion->usuario_id,
            'tipo_usuario' => $notificacion->tipo_usuario,
            'accion' => 'crear_notificacion',
            'entidad' => 'notificacion',
            'entidad_id' => $notificacion->id,
            'datos_anteriores' => [],
            'datos_nuevos' => $datos,
            'ip_cliente' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
        
        return $notificacion;
    }

    public static function crearParaUsuario(int $usuarioId, string $tipoUsuario, array $datos): Notificacion
    {
        $datos['usuario_id'] = $usuarioId;
        $datos['tipo_usuario'] = $tipoUsuario;
        
        return static::crear($datos);
    }

    public static function crearParaEntidad(string $entidad, int $entidadId, array $datos): Notificacion
    {
        $datos['entidad'] = $entidad;
        $datos['entidad_id'] = $entidadId;
        
        return static::create($datos);
    }

    // Boot method para eventos del modelo
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($notificacion) {
            if (empty($notificacion->codigo_notificacion)) {
                $notificacion->codigo_notificacion = static::generarCodigoNotificacion();
            }
            
            if (empty($notificacion->fecha_creacion)) {
                $notificacion->fecha_creacion = now();
            }
            
            if (empty($notificacion->estado)) {
                $notificacion->estado = 'activa';
            }
            
            if (empty($notificacion->leida)) {
                $notificacion->leida = false;
            }
            
            if (empty($notificacion->prioridad)) {
                $notificacion->prioridad = 2; // Media por defecto
            }
            
            if (empty($notificacion->nivel_urgencia)) {
                $notificacion->nivel_urgencia = 2; // Normal por defecto
            }
            
            if (empty($notificacion->canal_envio)) {
                $notificacion->canal_envio = 'sistema';
            }
            
            if (empty($notificacion->max_intentos)) {
                $notificacion->max_intentos = 3;
            }
            
            if (empty($notificacion->intentos_envio)) {
                $notificacion->intentos_envio = 0;
            }
        });

        static::updating(function ($notificacion) {
            // Log de cambios importantes
            if ($notificacion->isDirty('estado')) {
                LogAuditoria::crear([
                    'usuario_id' => auth()->id() ?? $notificacion->usuario_id,
                    'tipo_usuario' => auth()->user() ? (auth()->user() instanceof Operador ? 'operador' : 'cliente') : $notificacion->tipo_usuario,
                    'accion' => 'cambiar_estado_notificacion',
                    'entidad' => 'notificacion',
                    'entidad_id' => $notificacion->id,
                    'datos_anteriores' => ['estado' => $notificacion->getOriginal('estado')],
                    'datos_nuevos' => ['estado' => $notificacion->estado],
                    'ip_cliente' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]);
            }
        });
    }

    // Método estático para generar código único
    protected static function generarCodigoNotificacion(): string
    {
        do {
            $codigo = 'NOT-' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (static::where('codigo_notificacion', $codigo)->exists());

        return $codigo;
    }
}
