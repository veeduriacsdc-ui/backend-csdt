<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Configuracion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'configuraciones';

    protected $fillable = [
        'codigo_configuracion',
        'clave',
        'valor',
        'tipo_valor',
        'categoria',
        'descripcion',
        'estado',
        'es_publica',
        'es_critica',
        'es_cacheable',
        'tiempo_expiracion_cache',
        'grupo_configuracion',
        'orden_visualizacion',
        'validacion_regex',
        'valores_permitidos',
        'valor_por_defecto',
        'valor_minimo',
        'valor_maximo',
        'unidad_medida',
        'metadatos',
        'tags',
        'version_configuracion',
        'fecha_ultima_modificacion',
        'usuario_modificador_id',
        'tipo_usuario_modificador',
        'notas_internas',
        'documentacion',
        'ejemplos_uso',
        'dependencias',
        'impacto_cambio',
        'nivel_seguridad',
        'permisos_lectura',
        'permisos_escritura',
        'logs_cambios',
        'backup_automatico',
        'fecha_backup',
        'hash_verificacion',
        'firma_digital'
    ];

    protected $casts = [
        'valor' => 'json',
        'valores_permitidos' => 'array',
        'metadatos' => 'array',
        'tags' => 'array',
        'dependencias' => 'array',
        'permisos_lectura' => 'array',
        'permisos_escritura' => 'array',
        'es_publica' => 'boolean',
        'es_critica' => 'boolean',
        'es_cacheable' => 'boolean',
        'tiempo_expiracion_cache' => 'integer',
        'orden_visualizacion' => 'integer',
        'version_configuracion' => 'integer',
        'valor_minimo' => 'decimal:8,2',
        'valor_maximo' => 'decimal:8,2',
        'nivel_seguridad' => 'integer',
        'logs_cambios' => 'boolean',
        'backup_automatico' => 'boolean',
        'fecha_ultima_modificacion' => 'datetime',
        'fecha_backup' => 'datetime'
    ];

    // Relaciones
    public function logsAuditoria(): HasMany
    {
        return $this->hasMany(LogAuditoria::class, 'entidad_id')
            ->where('entidad', 'configuracion');
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

    public function scopePublicas(Builder $query): void
    {
        $query->where('es_publica', true);
    }

    public function scopePrivadas(Builder $query): void
    {
        $query->where('es_publica', false);
    }

    public function scopeCriticas(Builder $query): void
    {
        $query->where('es_critica', true);
    }

    public function scopeNoCriticas(Builder $query): void
    {
        $query->where('es_critica', false);
    }

    public function scopeCacheables(Builder $query): void
    {
        $query->where('es_cacheable', true);
    }

    public function scopeNoCacheables(Builder $query): void
    {
        $query->where('es_cacheable', false);
    }

    public function scopePorClave(Builder $query, string $clave): void
    {
        $query->where('clave', $clave);
    }

    public function scopePorCategoria(Builder $query, string $categoria): void
    {
        $query->where('categoria', $categoria);
    }

    public function scopePorGrupo(Builder $query, string $grupo): void
    {
        $query->where('grupo_configuracion', $grupo);
    }

    public function scopePorTipoValor(Builder $query, string $tipo): void
    {
        $query->where('tipo_valor', $tipo);
    }

    public function scopePorEstado(Builder $query, string $estado): void
    {
        $query->where('estado', $estado);
    }

    public function scopePorNivelSeguridad(Builder $query, int $nivel): void
    {
        $query->where('nivel_seguridad', $nivel);
    }

    public function scopeConTags(Builder $query, array $tags): void
    {
        foreach ($tags as $tag) {
            $query->whereJsonContains('tags', $tag);
        }
    }

    public function scopePorVersion(Builder $query, int $version): void
    {
        $query->where('version_configuracion', $version);
    }

    public function scopeRecientes(Builder $query, int $dias = 7): void
    {
        $query->where('fecha_ultima_modificacion', '>=', now()->subDays($dias));
    }

    public function scopeAntiguas(Builder $query, int $dias = 30): void
    {
        $query->where('fecha_ultima_modificacion', '<', now()->subDays($dias));
    }

    public function scopeOrdenadas(Builder $query): void
    {
        $query->orderBy('orden_visualizacion', 'asc');
    }

    public function scopePorUsuarioModificador(Builder $query, int $usuarioId): void
    {
        $query->where('usuario_modificador_id', $usuarioId);
    }

    public function scopeConBackup(Builder $query): void
    {
        $query->where('backup_automatico', true);
    }

    public function scopeSinBackup(Builder $query): void
    {
        $query->where('backup_automatico', false);
    }

    // Accessors
    public function getClaveFormateadaAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->clave));
    }

    public function getValorFormateadoAttribute(): mixed
    {
        if (is_array($this->valor)) {
            return json_encode($this->valor, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        
        return $this->valor;
    }

    public function getTipoValorFormateadoAttribute(): string
    {
        return ucfirst($this->tipo_valor);
    }

    public function getCategoriaFormateadaAttribute(): string
    {
        return ucfirst($this->categoria);
    }

    public function getEstadoFormateadoAttribute(): string
    {
        return ucfirst($this->estado);
    }

    public function getGrupoFormateadoAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->grupo_configuracion));
    }

    public function getDescripcionFormateadaAttribute(): string
    {
        return ucfirst($this->descripcion);
    }

    public function getFechaModificacionFormateadaAttribute(): string
    {
        return $this->fecha_ultima_modificacion ? $this->fecha_ultima_modificacion->format('d/m/Y H:i') : 'N/A';
    }

    public function getFechaBackupFormateadaAttribute(): string
    {
        return $this->fecha_backup ? $this->fecha_backup->format('d/m/Y H:i') : 'N/A';
    }

    public function getDiasDesdeModificacionAttribute(): int
    {
        return $this->fecha_ultima_modificacion ? $this->fecha_ultima_modificacion->diffInDays(now()) : 0;
    }

    public function getDiasDesdeBackupAttribute(): int
    {
        if (!$this->fecha_backup) {
            return -1;
        }
        
        return $this->fecha_backup->diffInDays(now());
    }

    public function getNivelSeguridadFormateadoAttribute(): string
    {
        $niveles = [
            1 => 'Bajo',
            2 => 'Medio',
            3 => 'Alto',
            4 => 'Muy Alto',
            5 => 'Crítico'
        ];
        
        return $niveles[$this->nivel_seguridad] ?? 'No especificado';
    }

    public function getVersionFormateadaAttribute(): string
    {
        return 'v' . $this->version_configuracion;
    }

    public function getTiempoExpiracionFormateadoAttribute(): string
    {
        if (!$this->tiempo_expiracion_cache) {
            return 'Sin expiración';
        }
        
        if ($this->tiempo_expiracion_cache < 60) {
            return $this->tiempo_expiracion_cache . ' segundos';
        } elseif ($this->tiempo_expiracion_cache < 3600) {
            return round($this->tiempo_expiracion_cache / 60, 1) . ' minutos';
        } elseif ($this->tiempo_expiracion_cache < 86400) {
            return round($this->tiempo_expiracion_cache / 3600, 1) . ' horas';
        } else {
            return round($this->tiempo_expiracion_cache / 86400, 1) . ' días';
        }
    }

    public function getValorMinimoFormateadoAttribute(): string
    {
        if (!$this->valor_minimo) {
            return 'N/A';
        }
        
        $valor = $this->valor_minimo;
        if ($this->unidad_medida) {
            $valor .= ' ' . $this->unidad_medida;
        }
        
        return $valor;
    }

    public function getValorMaximoFormateadoAttribute(): string
    {
        if (!$this->valor_maximo) {
            return 'N/A';
        }
        
        $valor = $this->valor_maximo;
        if ($this->unidad_medida) {
            $valor .= ' ' . $this->unidad_medida;
        }
        
        return $valor;
    }

    public function getEstaActivaAttribute(): bool
    {
        return $this->estado === 'activa';
    }

    public function getEstaInactivaAttribute(): bool
    {
        return $this->estado === 'inactiva';
    }

    public function getEsPublicaAttribute(): bool
    {
        return $this->es_publica;
    }

    public function getEsCriticaAttribute(): bool
    {
        return $this->es_critica;
    }

    public function getEsCacheableAttribute(): bool
    {
        return $this->es_cacheable;
    }

    public function getTieneBackupAttribute(): bool
    {
        return $this->backup_automatico;
    }

    public function getPuedeModificarAttribute(): bool
    {
        // Verificar permisos del usuario actual
        if (!auth()->check()) {
            return false;
        }
        
        $usuario = auth()->user();
        $permisosEscritura = $this->permisos_escritura ?? [];
        
        if (empty($permisosEscritura)) {
            return true; // Sin restricciones específicas
        }
        
        if ($usuario instanceof Operador) {
            return in_array('operador', $permisosEscritura) || in_array('administrador', $permisosEscritura);
        }
        
        if ($usuario instanceof Cliente) {
            return in_array('cliente', $permisosEscritura);
        }
        
        return false;
    }

    public function getPuedeLeerAttribute(): bool
    {
        // Verificar permisos del usuario actual
        if (!auth()->check()) {
            return $this->es_publica;
        }
        
        $usuario = auth()->user();
        $permisosLectura = $this->permisos_lectura ?? [];
        
        if (empty($permisosLectura)) {
            return true; // Sin restricciones específicas
        }
        
        if ($usuario instanceof Operador) {
            return in_array('operador', $permisosLectura) || in_array('administrador', $permisosLectura);
        }
        
        if ($usuario instanceof Cliente) {
            return in_array('cliente', $permisosLectura);
        }
        
        return false;
    }

    public function getValorCacheadoAttribute(): mixed
    {
        if (!$this->es_cacheable) {
            return $this->valor;
        }
        
        $claveCache = 'configuracion_' . $this->clave;
        return Cache::remember($claveCache, $this->tiempo_expiracion_cache ?? 3600, function () {
            return $this->valor;
        });
    }

    public function getTagsFormateadosAttribute(): array
    {
        return $this->tags ?? [];
    }

    public function getMetadatosFormateadosAttribute(): array
    {
        return $this->metadatos ?? [];
    }

    public function getDependenciasFormateadasAttribute(): array
    {
        return $this->dependencias ?? [];
    }

    public function getPermisosLecturaFormateadosAttribute(): array
    {
        return $this->permisos_lectura ?? [];
    }

    public function getPermisosEscrituraFormateadosAttribute(): array
    {
        return $this->permisos_escritura ?? [];
    }

    public function getValoresPermitidosFormateadosAttribute(): array
    {
        return $this->valores_permitidos ?? [];
    }

    // Mutators
    public function setClaveAttribute($value): void
    {
        $this->attributes['clave'] = strtolower($value);
    }

    public function setCategoriaAttribute($value): void
    {
        $this->attributes['categoria'] = strtolower($value);
    }

    public function setGrupoConfiguracionAttribute($value): void
    {
        $this->attributes['grupo_configuracion'] = strtolower($value);
    }

    public function setTipoValorAttribute($value): void
    {
        $this->attributes['tipo_valor'] = strtolower($value);
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

    public function setPermisosLecturaAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['permisos_lectura'] = array_map('strtolower', $value);
        } else {
            $this->attributes['permisos_lectura'] = [];
        }
    }

    public function setPermisosEscrituraAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['permisos_escritura'] = array_map('strtolower', $value);
        } else {
            $this->attributes['permisos_escritura'] = [];
        }
    }

    // Métodos
    public function cambiarEstado(string $nuevoEstado): bool
    {
        $estadosValidos = ['activa', 'inactiva', 'deprecada', 'eliminada'];
        
        if (!in_array($nuevoEstado, $estadosValidos)) {
            return false;
        }

        $estadoAnterior = $this->estado;
        $this->update([
            'estado' => $nuevoEstado,
            'fecha_ultima_modificacion' => now()
        ]);

        // Log de auditoría si está habilitado
        if ($this->logs_cambios) {
            LogAuditoria::crear([
                'usuario_id' => auth()->id() ?? $this->usuario_modificador_id,
                'tipo_usuario' => auth()->user() ? (auth()->user() instanceof Operador ? 'operador' : 'cliente') : $this->tipo_usuario_modificador,
                'accion' => 'cambiar_estado_configuracion',
                'entidad' => 'configuracion',
                'entidad_id' => $this->id,
                'datos_anteriores' => ['estado' => $estadoAnterior],
                'datos_nuevos' => ['estado' => $nuevoEstado],
                'ip_cliente' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        }

        // Limpiar cache si es cacheable
        if ($this->es_cacheable) {
            $this->limpiarCache();
        }

        return true;
    }

    public function actualizarValor($nuevoValor): bool
    {
        // Validar valor según tipo
        if (!$this->validarValor($nuevoValor)) {
            return false;
        }

        $valorAnterior = $this->valor;
        $this->update([
            'valor' => $nuevoValor,
            'fecha_ultima_modificacion' => now(),
            'usuario_modificador_id' => auth()->id(),
            'tipo_usuario_modificador' => auth()->user() ? (auth()->user() instanceof Operador ? 'operador' : 'cliente') : 'sistema',
            'version_configuracion' => $this->version_configuracion + 1
        ]);

        // Log de auditoría si está habilitado
        if ($this->logs_cambios) {
            LogAuditoria::crear([
                'usuario_id' => auth()->id() ?? $this->usuario_modificador_id,
                'tipo_usuario' => auth()->user() ? (auth()->user() instanceof Operador ? 'operador' : 'cliente') : $this->tipo_usuario_modificador,
                'accion' => 'actualizar_valor_configuracion',
                'entidad' => 'configuracion',
                'entidad_id' => $this->id,
                'datos_anteriores' => ['valor' => $valorAnterior],
                'datos_nuevos' => ['valor' => $nuevoValor],
                'ip_cliente' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        }

        // Limpiar cache si es cacheable
        if ($this->es_cacheable) {
            $this->limpiarCache();
        }

        return true;
    }

    public function validarValor($valor): bool
    {
        // Validar según tipo de valor
        switch ($this->tipo_valor) {
            case 'string':
                if (!is_string($valor)) {
                    return false;
                }
                break;
                
            case 'integer':
                if (!is_numeric($valor) || !is_int($valor + 0)) {
                    return false;
                }
                break;
                
            case 'decimal':
                if (!is_numeric($valor)) {
                    return false;
                }
                break;
                
            case 'boolean':
                if (!is_bool($valor) && !in_array($valor, [0, 1, '0', '1', 'true', 'false'])) {
                    return false;
                }
                break;
                
            case 'array':
                if (!is_array($valor)) {
                    return false;
                }
                break;
                
            case 'json':
                if (!is_string($valor) && !is_array($valor)) {
                    return false;
                }
                break;
        }

        // Validar valores permitidos si están definidos
        if (!empty($this->valores_permitidos) && !in_array($valor, $this->valores_permitidos)) {
            return false;
        }

        // Validar rango si está definido
        if (is_numeric($valor)) {
            if ($this->valor_minimo !== null && $valor < $this->valor_minimo) {
                return false;
            }
            
            if ($this->valor_maximo !== null && $valor > $this->valor_maximo) {
                return false;
            }
        }

        // Validar regex si está definido
        if (!empty($this->validacion_regex) && is_string($valor)) {
            if (!preg_match($this->validacion_regex, $valor)) {
                return false;
            }
        }

        return true;
    }

    public function limpiarCache(): bool
    {
        if (!$this->es_cacheable) {
            return false;
        }
        
        $claveCache = 'configuracion_' . $this->clave;
        Cache::forget($claveCache);
        
        return true;
    }

    public function crearBackup(): bool
    {
        if (!$this->backup_automatico) {
            return false;
        }
        
        // Aquí se implementaría la lógica de backup
        // Por ahora solo actualizamos la fecha
        $this->update([
            'fecha_backup' => now()
        ]);
        
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

    public function actualizarMetadatos(array $nuevosMetadatos): bool
    {
        $metadatosActuales = $this->metadatos ?? [];
        $metadatosActualizados = array_merge($metadatosActuales, $nuevosMetadatos);
        
        $this->update(['metadatos' => $metadatosActualizados]);
        return true;
    }

    public function generarHashVerificacion(): string
    {
        $datos = [
            'id' => $this->id,
            'clave' => $this->clave,
            'valor' => $this->valor,
            'tipo_valor' => $this->tipo_valor,
            'version_configuracion' => $this->version_configuracion,
            'fecha_ultima_modificacion' => $this->fecha_ultima_modificacion->toISOString()
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
            'clave' => $this->clave_formateada,
            'valor' => $this->valor_formateado,
            'tipo_valor' => $this->tipo_valor_formateado,
            'categoria' => $this->categoria_formateada,
            'estado' => $this->estado_formateado,
            'grupo' => $this->grupo_formateado,
            'descripcion' => $this->descripcion_formateada,
            'es_publica' => $this->es_publica,
            'es_critica' => $this->es_critica,
            'es_cacheable' => $this->es_cacheable,
            'nivel_seguridad' => $this->nivel_seguridad_formateado,
            'version' => $this->version_formateada,
            'tiempo_expiracion_cache' => $this->tiempo_expiracion_formateado,
            'valor_minimo' => $this->valor_minimo_formateado,
            'valor_maximo' => $this->valor_maximo_formateado,
            'unidad_medida' => $this->unidad_medida,
            'fecha_ultima_modificacion' => $this->fecha_modificacion_formateada,
            'fecha_backup' => $this->fecha_backup_formateada,
            'dias_desde_modificacion' => $this->dias_desde_modificacion,
            'dias_desde_backup' => $this->dias_desde_backup,
            'esta_activa' => $this->esta_activa,
            'esta_inactiva' => $this->esta_inactiva,
            'tiene_backup' => $this->tiene_backup,
            'puede_modificar' => $this->puede_modificar,
            'puede_leer' => $this->puede_leer,
            'total_tags' => count($this->tags ?? []),
            'total_metadatos' => count($this->metadatos ?? []),
            'total_dependencias' => count($this->dependencias ?? []),
            'total_valores_permitidos' => count($this->valores_permitidos ?? [])
        ];
    }

    // Métodos estáticos
    public static function obtenerPorClave(string $clave): ?Configuracion
    {
        $configuracion = static::where('clave', $clave)->first();
        
        if (!$configuracion) {
            return null;
        }
        
        // Verificar permisos de lectura
        if (!$configuracion->puede_leer) {
            return null;
        }
        
        return $configuracion;
    }

    public static function obtenerValor(string $clave, $valorPorDefecto = null): mixed
    {
        $configuracion = static::obtenerPorClave($clave);
        
        if (!$configuracion) {
            return $valorPorDefecto;
        }
        
        return $configuracion->valor_cacheado;
    }

    public static function establecerValor(string $clave, $valor): bool
    {
        $configuracion = static::where('clave', $clave)->first();
        
        if (!$configuracion) {
            return false;
        }
        
        return $configuracion->actualizarValor($valor);
    }

    public static function crear(array $datos): Configuracion
    {
        $configuracion = static::create($datos);
        
        // Log de auditoría si está habilitado
        if ($configuracion->logs_cambios) {
            LogAuditoria::crear([
                'usuario_id' => $configuracion->usuario_modificador_id,
                'tipo_usuario' => $configuracion->tipo_usuario_modificador,
                'accion' => 'crear_configuracion',
                'entidad' => 'configuracion',
                'entidad_id' => $configuracion->id,
                'datos_anteriores' => [],
                'datos_nuevos' => $datos,
                'ip_cliente' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        }
        
        return $configuracion;
    }

    public static function obtenerEstadisticasGenerales(): array
    {
        $totalConfiguraciones = static::count();
        $configuracionesActivas = static::activas()->count();
        $configuracionesInactivas = static::inactivas()->count();
        $configuracionesCriticas = static::criticas()->count();
        $configuracionesPublicas = static::publicas()->count();
        $configuracionesCacheables = static::cacheables()->count();
        
        $categorias = static::select('categoria', DB::raw('count(*) as total'))
            ->groupBy('categoria')
            ->orderBy('total', 'desc')
            ->get();
        
        $grupos = static::select('grupo_configuracion', DB::raw('count(*) as total'))
            ->groupBy('grupo_configuracion')
            ->orderBy('total', 'desc')
            ->get();
        
        $tiposValor = static::select('tipo_valor', DB::raw('count(*) as total'))
            ->groupBy('tipo_valor')
            ->orderBy('total', 'desc')
            ->get();
        
        return [
            'total_configuraciones' => $totalConfiguraciones,
            'configuraciones_activas' => $configuracionesActivas,
            'configuraciones_inactivas' => $configuracionesInactivas,
            'configuraciones_criticas' => $configuracionesCriticas,
            'configuraciones_publicas' => $configuracionesPublicas,
            'configuraciones_cacheables' => $configuracionesCacheables,
            'categorias' => $categorias,
            'grupos' => $grupos,
            'tipos_valor' => $tiposValor
        ];
    }

    public static function limpiarCacheGlobal(): bool
    {
        $configuracionesCacheables = static::cacheables()->get();
        
        foreach ($configuracionesCacheables as $configuracion) {
            $configuracion->limpiarCache();
        }
        
        return true;
    }

    // Boot method para eventos del modelo
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($configuracion) {
            if (empty($configuracion->codigo_configuracion)) {
                $configuracion->codigo_configuracion = static::generarCodigoConfiguracion();
            }
            
            if (empty($configuracion->estado)) {
                $configuracion->estado = 'activa';
            }
            
            if (empty($configuracion->version_configuracion)) {
                $configuracion->version_configuracion = 1;
            }
            
            if (empty($configuracion->nivel_seguridad)) {
                $configuracion->nivel_seguridad = 2; // Medio por defecto
            }
            
            if (empty($configuracion->orden_visualizacion)) {
                $configuracion->orden_visualizacion = 0;
            }
            
            if (empty($configuracion->fecha_ultima_modificacion)) {
                $configuracion->fecha_ultima_modificacion = now();
            }
            
            if (empty($configuracion->usuario_modificador_id)) {
                $configuracion->usuario_modificador_id = auth()->id();
            }
            
            if (empty($configuracion->tipo_usuario_modificador)) {
                $configuracion->tipo_usuario_modificador = auth()->user() ? (auth()->user() instanceof Operador ? 'operador' : 'cliente') : 'sistema';
            }
        });

        static::updating(function ($configuracion) {
            // Log de cambios importantes si está habilitado
            if ($configuracion->logs_cambios && $configuracion->isDirty('valor')) {
                LogAuditoria::crear([
                    'usuario_id' => auth()->id() ?? $configuracion->usuario_modificador_id,
                    'tipo_usuario' => auth()->user() ? (auth()->user() instanceof Operador ? 'operador' : 'cliente') : $configuracion->tipo_usuario_modificador,
                    'accion' => 'actualizar_valor_configuracion',
                    'entidad' => 'configuracion',
                    'entidad_id' => $configuracion->id,
                    'datos_anteriores' => ['valor' => $configuracion->getOriginal('valor')],
                    'datos_nuevos' => ['valor' => $configuracion->valor],
                    'ip_cliente' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]);
            }
        });
    }

    // Método estático para generar código único
    protected static function generarCodigoConfiguracion(): string
    {
        do {
            $codigo = 'CONF-' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (static::where('codigo_configuracion', $codigo)->exists());

        return $codigo;
    }
}
