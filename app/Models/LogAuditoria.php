<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LogAuditoria extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'logs_auditoria';

    protected $fillable = [
        'usuario_id',
        'tipo_usuario',
        'accion',
        'entidad',
        'entidad_id',
        'datos_anteriores',
        'datos_nuevos',
        'ip_cliente',
        'user_agent',
        'fecha_accion',
        'estado_accion',
        'detalles_adicionales',
        'nivel_severidad',
        'categoria_accion'
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
        'detalles_adicionales' => 'array',
        'fecha_accion' => 'datetime',
        'nivel_severidad' => 'integer'
    ];

    protected $dates = [
        'fecha_accion',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // Relaciones
    public function usuario()
    {
        return $this->morphTo('usuario', 'tipo_usuario', 'usuario_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'usuario_id')->where('tipo_usuario', 'cliente');
    }

    public function operador()
    {
        return $this->belongsTo(Operador::class, 'usuario_id')->where('tipo_usuario', 'operador');
    }

    public function sesion()
    {
        return $this->belongsTo(Sesion::class, 'usuario_id')->where('tipo_usuario', 'sesion');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado_accion', 'exitoso');
    }

    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopePorTipoUsuario($query, $tipoUsuario)
    {
        return $query->where('tipo_usuario', $tipoUsuario);
    }

    public function scopePorAccion($query, $accion)
    {
        return $query->where('accion', $accion);
    }

    public function scopePorEntidad($query, $entidad)
    {
        return $query->where('entidad', $entidad);
    }

    public function scopePorFecha($query, $fechaInicio, $fechaFin = null)
    {
        if ($fechaFin) {
            return $query->whereBetween('fecha_accion', [$fechaInicio, $fechaFin]);
        }
        return $query->whereDate('fecha_accion', $fechaInicio);
    }

    public function scopePorSeveridad($query, $nivelSeveridad)
    {
        return $query->where('nivel_severidad', $nivelSeveridad);
    }

    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria_accion', $categoria);
    }

    public function scopeConErrores($query)
    {
        return $query->where('estado_accion', 'error');
    }

    public function scopeRecientes($query, $dias = 30)
    {
        return $query->where('fecha_accion', '>=', now()->subDays($dias));
    }

    // Accessors
    public function getAccionFormateadaAttribute(): string
    {
        $acciones = [
            'crear' => 'Creación',
            'actualizar' => 'Actualización',
            'eliminar' => 'Eliminación',
            'iniciar_sesion' => 'Inicio de Sesión',
            'cerrar_sesion' => 'Cierre de Sesión',
            'cambiar_rol' => 'Cambio de Rol',
            'cambiar_estado' => 'Cambio de Estado',
            'cambiar_contrasena' => 'Cambio de Contraseña',
            'recuperar_contrasena' => 'Recuperación de Contraseña',
            'subir_archivo' => 'Subida de Archivo',
            'descargar_archivo' => 'Descarga de Archivo',
            'generar_reporte' => 'Generación de Reporte',
            'exportar_datos' => 'Exportación de Datos',
            'validar_donacion' => 'Validación de Donación',
            'asignar_tarea' => 'Asignación de Tarea',
            'completar_tarea' => 'Completar Tarea',
            'cambiar_rol_usuario' => 'Cambio de Rol de Usuario',
            'cambiar_estado_usuario' => 'Cambio de Estado de Usuario',
            'eliminar_usuario' => 'Eliminación de Usuario',
            'restaurar_usuario' => 'Restauración de Usuario'
        ];

        return $acciones[$this->accion] ?? ucfirst(str_replace('_', ' ', $this->accion));
    }

    public function getEntidadFormateadaAttribute(): string
    {
        $entidades = [
            'cliente' => 'Cliente',
            'operador' => 'Operador',
            'administrador' => 'Administrador',
            'veeduria' => 'Veeduría',
            'tarea' => 'Tarea',
            'donacion' => 'Donación',
            'archivo' => 'Archivo',
            'notificacion' => 'Notificación',
            'reporte' => 'Reporte',
            'configuracion' => 'Configuración',
            'sesion' => 'Sesión'
        ];

        return $entidades[$this->entidad] ?? ucfirst($this->entidad);
    }

    public function getEstadoFormateadoAttribute(): string
    {
        $estados = [
            'exitoso' => 'Exitoso',
            'error' => 'Error',
            'pendiente' => 'Pendiente',
            'cancelado' => 'Cancelado'
        ];

        return $estados[$this->estado_accion] ?? ucfirst($this->estado_accion);
    }

    public function getSeveridadFormateadaAttribute(): string
    {
        $severidades = [
            1 => 'Baja',
            2 => 'Media',
            3 => 'Alta',
            4 => 'Crítica',
            5 => 'Urgente'
        ];

        return $severidades[$this->nivel_severidad] ?? 'Desconocida';
    }

    public function getCategoriaFormateadaAttribute(): string
    {
        $categorias = [
            'autenticacion' => 'Autenticación',
            'usuarios' => 'Gestión de Usuarios',
            'veedurias' => 'Veedurías',
            'donaciones' => 'Donaciones',
            'archivos' => 'Archivos',
            'reportes' => 'Reportes',
            'configuracion' => 'Configuración',
            'sistema' => 'Sistema'
        ];

        return $categorias[$this->categoria_accion] ?? ucfirst($this->categoria_accion);
    }

    public function getTiempoTranscurridoAttribute(): string
    {
        $diferencia = now()->diff($this->fecha_accion);
        
        if ($diferencia->y > 0) {
            return $diferencia->y . ' año(s)';
        } elseif ($diferencia->m > 0) {
            return $diferencia->m . ' mes(es)';
        } elseif ($diferencia->d > 0) {
            return $diferencia->d . ' día(s)';
        } elseif ($diferencia->h > 0) {
            return $diferencia->h . ' hora(s)';
        } elseif ($diferencia->i > 0) {
            return $diferencia->i . ' minuto(s)';
        } else {
            return 'Hace un momento';
        }
    }

    public function getResumenAccionAttribute(): string
    {
        $resumen = $this->accion_formateada . ' de ' . $this->entidad_formateada;
        
        if ($this->entidad_id) {
            $resumen .= ' (ID: ' . $this->entidad_id . ')';
        }
        
        return $resumen;
    }

    // Mutators
    public function setAccionAttribute($value)
    {
        $this->attributes['accion'] = strtolower($value);
    }

    public function setEntidadAttribute($value)
    {
        $this->attributes['entidad'] = strtolower($value);
    }

    public function setCategoriaAccionAttribute($value)
    {
        $this->attributes['categoria_accion'] = strtolower($value);
    }

    public function setDatosAnterioresAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['datos_anteriores'] = json_encode($value);
        } else {
            $this->attributes['datos_anteriores'] = $value;
        }
    }

    public function setDatosNuevosAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['datos_nuevos'] = json_encode($value);
        } else {
            $this->attributes['datos_nuevos'] = $value;
        }
    }

    public function setDetallesAdicionalesAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['detalles_adicionales'] = json_encode($value);
        } else {
            $this->attributes['detalles_adicionales'] = $value;
        }
    }

    // Métodos estáticos para crear logs
    public static function crear(array $datos): self
    {
        try {
            $datos['fecha_accion'] = $datos['fecha_accion'] ?? now();
            $datos['estado_accion'] = $datos['estado_accion'] ?? 'exitoso';
            $datos['nivel_severidad'] = $datos['nivel_severidad'] ?? 2;
            $datos['categoria_accion'] = $datos['categoria_accion'] ?? self::determinarCategoria($datos['accion']);

            $log = self::create($datos);

            // Log adicional para debugging
            Log::info('Log de auditoría creado', [
                'log_id' => $log->id,
                'accion' => $datos['accion'],
                'usuario_id' => $datos['usuario_id'] ?? null
            ]);

            return $log;

        } catch (\Exception $e) {
            Log::error('Error al crear log de auditoría: ' . $e->getMessage(), $datos);
            throw $e;
        }
    }

    public static function logAccion(
        int $usuarioId,
        string $tipoUsuario,
        string $accion,
        string $entidad,
        ?int $entidadId = null,
        array $datosAnteriores = [],
        array $datosNuevos = [],
        string $estado = 'exitoso',
        int $nivelSeveridad = 2,
        array $detallesAdicionales = []
    ): self {
        return self::crear([
            'usuario_id' => $usuarioId,
            'tipo_usuario' => $tipoUsuario,
            'accion' => $accion,
            'entidad' => $entidad,
            'entidad_id' => $entidadId,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => $datosNuevos,
            'estado_accion' => $estado,
            'nivel_severidad' => $nivelSeveridad,
            'detalles_adicionales' => $detallesAdicionales,
            'ip_cliente' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    public static function logError(
        int $usuarioId,
        string $tipoUsuario,
        string $accion,
        string $entidad,
        ?int $entidadId = null,
        string $mensajeError = '',
        array $datosAdicionales = []
    ): self {
        return self::crear([
            'usuario_id' => $usuarioId,
            'tipo_usuario' => $tipoUsuario,
            'accion' => $accion,
            'entidad' => $entidad,
            'entidad_id' => $entidadId,
            'estado_accion' => 'error',
            'nivel_severidad' => 4,
            'detalles_adicionales' => array_merge($datosAdicionales, ['error' => $mensajeError]),
            'ip_cliente' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    // Métodos de instancia
    public function marcarComoError(string $mensajeError, array $detallesAdicionales = []): void
    {
        $this->update([
            'estado_accion' => 'error',
            'nivel_severidad' => 4,
            'detalles_adicionales' => array_merge($this->detalles_adicionales ?? [], $detallesAdicionales, ['error' => $mensajeError])
        ]);
    }

    public function agregarDetalle(string $clave, $valor): void
    {
        $detalles = $this->detalles_adicionales ?? [];
        $detalles[$clave] = $valor;
        
        $this->update(['detalles_adicionales' => $detalles]);
    }

    public function obtenerDiferencia(): array
    {
        $anteriores = $this->datos_anteriores ?? [];
        $nuevos = $this->datos_nuevos ?? [];
        
        $diferencia = [];
        
        foreach ($nuevos as $clave => $valor) {
            if (!array_key_exists($clave, $anteriores) || $anteriores[$clave] !== $valor) {
                $diferencia[$clave] = [
                    'anterior' => $anteriores[$clave] ?? null,
                    'nuevo' => $valor
                ];
            }
        }
        
        return $diferencia;
    }

    // Métodos de utilidad
    private static function determinarCategoria(string $accion): string
    {
        $categorias = [
            'autenticacion' => ['iniciar_sesion', 'cerrar_sesion', 'cambiar_contrasena', 'recuperar_contrasena'],
            'usuarios' => ['crear', 'actualizar', 'eliminar', 'cambiar_rol', 'cambiar_estado'],
            'veedurias' => ['crear', 'actualizar', 'eliminar', 'asignar', 'completar'],
            'donaciones' => ['crear', 'actualizar', 'eliminar', 'validar', 'aprobar'],
            'archivos' => ['subir', 'descargar', 'eliminar', 'compartir'],
            'reportes' => ['generar', 'exportar', 'enviar'],
            'configuracion' => ['crear', 'actualizar', 'eliminar', 'cambiar'],
            'sistema' => ['backup', 'restore', 'maintenance', 'update']
        ];

        foreach ($categorias as $categoria => $acciones) {
            if (in_array($accion, $acciones)) {
                return $categoria;
            }
        }

        return 'sistema';
    }

    // Métodos de estadísticas
    public static function obtenerEstadisticas(string $periodo = '30_dias'): array
    {
        $fechaInicio = match($periodo) {
            '7_dias' => now()->subDays(7),
            '30_dias' => now()->subDays(30),
            '90_dias' => now()->subDays(90),
            '1_ano' => now()->subYear(),
            default => now()->subDays(30)
        };

        $logs = self::where('fecha_accion', '>=', $fechaInicio);

        return [
            'total_acciones' => $logs->count(),
            'acciones_exitosas' => $logs->where('estado_accion', 'exitoso')->count(),
            'acciones_con_error' => $logs->where('estado_accion', 'error')->count(),
            'acciones_por_categoria' => $logs->selectRaw('categoria_accion, COUNT(*) as total')
                                           ->groupBy('categoria_accion')
                                           ->pluck('total', 'categoria_accion')
                                           ->toArray(),
            'acciones_por_usuario' => $logs->selectRaw('usuario_id, COUNT(*) as total')
                                         ->groupBy('usuario_id')
                                         ->orderBy('total', 'desc')
                                         ->limit(10)
                                         ->pluck('total', 'usuario_id')
                                         ->toArray(),
            'nivel_severidad_promedio' => round($logs->avg('nivel_severidad'), 2)
        ];
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        // Auto-generar código de log si no se proporciona
        static::creating(function ($log) {
            if (empty($log->codigo_log)) {
                $log->codigo_log = self::generarCodigoLog();
            }
        });

        // Log cuando se crea un log de auditoría
        static::created(function ($log) {
            Log::info('Nuevo log de auditoría creado', [
                'log_id' => $log->id,
                'accion' => $log->accion,
                'entidad' => $log->entidad,
                'usuario_id' => $log->usuario_id
            ]);
        });
    }

    private static function generarCodigoLog(): string
    {
        $prefijo = 'LOG';
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(substr(md5(uniqid()), 0, 6));
        
        return "{$prefijo}-{$timestamp}-{$random}";
    }
}
