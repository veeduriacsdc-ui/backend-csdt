<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Sesion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sesiones';

    protected $fillable = [
        'codigo_sesion',
        'usuario_id',
        'tipo_usuario',
        'rol_asignado',
        'estado_sesion',
        'fecha_inicio',
        'fecha_ultima_actividad',
        'fecha_expiracion',
        'ip_cliente',
        'user_agent',
        'token_acceso',
        'token_refresh',
        'hash_verificacion',
        'metadatos',
        'permisos_activos',
        'nivel_acceso',
        'es_administrador',
        'es_operador',
        'es_cliente',
        'puede_gestionar_usuarios',
        'puede_asignar_roles',
        'puede_validar_operaciones',
        'puede_gestionar_donaciones',
        'puede_gestionar_tareas',
        'puede_acceder_panel_admin',
        'notas_internas',
        'version_sesion'
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_ultima_actividad' => 'datetime',
        'fecha_expiracion' => 'datetime',
        'metadatos' => 'array',
        'permisos_activos' => 'array',
        'es_administrador' => 'boolean',
        'es_operador' => 'boolean',
        'es_cliente' => 'boolean',
        'puede_gestionar_usuarios' => 'boolean',
        'puede_asignar_roles' => 'boolean',
        'puede_validar_operaciones' => 'boolean',
        'puede_gestionar_donaciones' => 'boolean',
        'puede_gestionar_tareas' => 'boolean',
        'puede_acceder_panel_admin' => 'boolean',
        'nivel_acceso' => 'integer',
        'version_sesion' => 'integer'
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

    public function administrador(): BelongsTo
    {
        return $this->belongsTo(Operador::class, 'usuario_id')
            ->where('tipo_usuario', 'operador')
            ->where('rol_asignado', 'administrador');
    }

    // Scopes
    public function scopeActivas(Builder $query): void
    {
        $query->where('estado_sesion', 'activa');
    }

    public function scopeExpiradas(Builder $query): void
    {
        $query->where('fecha_expiracion', '<', now());
    }

    public function scopePorTipoUsuario(Builder $query, string $tipo): void
    {
        $query->where('tipo_usuario', $tipo);
    }

    public function scopePorRol(Builder $query, string $rol): void
    {
        $query->where('rol_asignado', $rol);
    }

    public function scopeAdministradores(Builder $query): void
    {
        $query->where('es_administrador', true);
    }

    public function scopeOperadores(Builder $query): void
    {
        $query->where('es_operador', true);
    }

    public function scopeClientes(Builder $query): void
    {
        $query->where('es_cliente', true);
    }

    public function scopePorNivelAcceso(Builder $query, int $nivel): void
    {
        $query->where('nivel_acceso', $nivel);
    }

    public function scopeConPermisos(Builder $query, array $permisos): void
    {
        foreach ($permisos as $permiso) {
            $query->where("puede_{$permiso}", true);
        }
    }

    public function scopeRecientes(Builder $query, int $dias = 7): void
    {
        $query->where('fecha_inicio', '>=', now()->subDays($dias));
    }

    public function scopePorIp(Builder $query, string $ip): void
    {
        $query->where('ip_cliente', $ip);
    }

    // Accessors
    public function getEstadoFormateadoAttribute(): string
    {
        return ucfirst($this->estado_sesion);
    }

    public function getTipoUsuarioFormateadoAttribute(): string
    {
        return ucfirst($this->tipo_usuario);
    }

    public function getRolFormateadoAttribute(): string
    {
        return ucfirst($this->rol_asignado);
    }

    public function getFechaInicioFormateadaAttribute(): string
    {
        return $this->fecha_inicio ? $this->fecha_inicio->format('d/m/Y H:i') : 'N/A';
    }

    public function getFechaUltimaActividadFormateadaAttribute(): string
    {
        return $this->fecha_ultima_actividad ? $this->fecha_ultima_actividad->format('d/m/Y H:i') : 'N/A';
    }

    public function getFechaExpiracionFormateadaAttribute(): string
    {
        return $this->fecha_expiracion ? $this->fecha_expiracion->format('d/m/Y H:i') : 'N/A';
    }

    public function getDiasTranscurridosAttribute(): int
    {
        return $this->fecha_inicio ? $this->fecha_inicio->diffInDays(now()) : 0;
    }

    public function getMinutosInactividadAttribute(): int
    {
        if (!$this->fecha_ultima_actividad) {
            return 0;
        }
        
        return $this->fecha_ultima_actividad->diffInMinutes(now());
    }

    public function getEstaActivaAttribute(): bool
    {
        return $this->estado_sesion === 'activa';
    }

    public function getEstaExpiradaAttribute(): bool
    {
        return $this->fecha_expiracion && $this->fecha_expiracion < now();
    }

    public function getTieneTokenValidoAttribute(): bool
    {
        return !empty($this->token_acceso) && !$this->esta_expirada;
    }

    public function getNivelAccesoFormateadoAttribute(): string
    {
        $niveles = [
            1 => 'Básico',
            2 => 'Intermedio',
            3 => 'Avanzado',
            4 => 'Administrativo',
            5 => 'Súper Administrador'
        ];
        
        return $niveles[$this->nivel_acceso] ?? 'No especificado';
    }

    public function getPermisosFormateadosAttribute(): array
    {
        $permisos = [];
        
        if ($this->puede_gestionar_usuarios) $permisos[] = 'Gestionar Usuarios';
        if ($this->puede_asignar_roles) $permisos[] = 'Asignar Roles';
        if ($this->puede_validar_veedurias) $permisos[] = 'Validar Veedurías';
        if ($this->puede_gestionar_donaciones) $permisos[] = 'Gestionar Donaciones';
        if ($this->puede_gestionar_tareas) $permisos[] = 'Gestionar Tareas';
        if ($this->puede_acceder_panel_admin) $permisos[] = 'Acceder Panel Admin';
        
        return $permisos;
    }

    public function getMetadatosFormateadosAttribute(): array
    {
        return $this->metadatos ?? [];
    }

    // Mutators
    public function setTipoUsuarioAttribute($value): void
    {
        $this->attributes['tipo_usuario'] = strtolower($value);
    }

    public function setRolAsignadoAttribute($value): void
    {
        $this->attributes['rol_asignado'] = strtolower($value);
    }

    public function setEstadoSesionAttribute($value): void
    {
        $this->attributes['estado_sesion'] = strtolower($value);
    }

    public function setPermisosActivosAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['permisos_activos'] = array_map('strtolower', $value);
        } else {
            $this->attributes['permisos_activos'] = [];
        }
    }

    // Métodos
    public function iniciarSesion(): bool
    {
        $this->update([
            'estado_sesion' => 'activa',
            'fecha_inicio' => now(),
            'fecha_ultima_actividad' => now(),
            'fecha_expiracion' => now()->addHours(24), // 24 horas por defecto
            'ip_cliente' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return true;
    }

    public function cerrarSesion(): bool
    {
        $this->update([
            'estado_sesion' => 'cerrada',
            'fecha_expiracion' => now()
        ]);

        return true;
    }

    public function renovarSesion(int $horasAdicionales = 24): bool
    {
        $this->update([
            'fecha_ultima_actividad' => now(),
            'fecha_expiracion' => now()->addHours($horasAdicionales)
        ]);

        return true;
    }

    public function actualizarActividad(): bool
    {
        $this->update(['fecha_ultima_actividad' => now()]);
        return true;
    }

    public function cambiarRol(string $nuevoRol): bool
    {
        $rolesValidos = ['cliente', 'operador', 'administrador'];
        
        if (!in_array($nuevoRol, $rolesValidos)) {
            return false;
        }

        $rolAnterior = $this->rol_asignado;
        $this->update(['rol_asignado' => $nuevoRol]);

        // Actualizar permisos según el nuevo rol
        $this->actualizarPermisosPorRol($nuevoRol);

        // Log de auditoría
        LogAuditoria::crear([
            'usuario_id' => $this->usuario_id,
            'tipo_usuario' => $this->tipo_usuario,
            'accion' => 'cambiar_rol_usuario',
            'entidad' => 'sesion',
            'entidad_id' => $this->id,
            'datos_anteriores' => ['rol' => $rolAnterior],
            'datos_nuevos' => ['rol' => $nuevoRol],
            'ip_cliente' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return true;
    }

    public function actualizarPermisosPorRol(string $rol): bool
    {
        $permisos = $this->obtenerPermisosPorRol($rol);
        
        $this->update([
            'es_administrador' => $rol === 'administrador',
            'es_operador' => in_array($rol, ['operador', 'administrador']),
            'es_cliente' => $rol === 'cliente',
            'puede_gestionar_usuarios' => $rol === 'administrador',
            'puede_asignar_roles' => $rol === 'administrador',
            'puede_validar_veedurias' => in_array($rol, ['operador', 'administrador']),
            'puede_gestionar_donaciones' => in_array($rol, ['operador', 'administrador']),
            'puede_gestionar_tareas' => in_array($rol, ['operador', 'administrador']),
            'puede_acceder_panel_admin' => $rol === 'administrador',
            'nivel_acceso' => $this->obtenerNivelAccesoPorRol($rol)
        ]);

        return true;
    }

    public function tienePermiso(string $permiso): bool
    {
        $permisos = [
            'gestionar_usuarios' => $this->puede_gestionar_usuarios,
            'asignar_roles' => $this->puede_asignar_roles,
            'validar_veedurias' => $this->puede_validar_veedurias,
            'gestionar_donaciones' => $this->puede_gestionar_donaciones,
            'gestionar_tareas' => $this->puede_gestionar_tareas,
            'acceder_panel_admin' => $this->puede_acceder_panel_admin
        ];

        return $permisos[$permiso] ?? false;
    }

    public function tieneRol(string $rol): bool
    {
        return $this->rol_asignado === $rol;
    }

    public function esAdministrador(): bool
    {
        return $this->es_administrador;
    }

    public function esOperador(): bool
    {
        return $this->es_operador;
    }

    public function esCliente(): bool
    {
        return $this->es_cliente;
    }

    public function puedeAccederPanelAdmin(): bool
    {
        return $this->puede_acceder_panel_admin;
    }

    public function generarTokens(): array
    {
        $tokenAcceso = hash('sha256', uniqid() . time() . $this->id);
        $tokenRefresh = hash('sha256', uniqid() . time() . $this->id . 'refresh');
        
        $this->update([
            'token_acceso' => $tokenAcceso,
            'token_refresh' => $tokenRefresh
        ]);

        return [
            'access_token' => $tokenAcceso,
            'refresh_token' => $tokenRefresh
        ];
    }

    public function validarToken(string $token): bool
    {
        return $this->token_acceso === $token && $this->tiene_token_valido;
    }

    public function generarHashVerificacion(): string
    {
        $datos = [
            'id' => $this->id,
            'usuario_id' => $this->usuario_id,
            'tipo_usuario' => $this->tipo_usuario,
            'rol_asignado' => $this->rol_asignado,
            'fecha_inicio' => $this->fecha_inicio->toISOString()
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
            'estado' => $this->estado_formateado,
            'tipo_usuario' => $this->tipo_usuario_formateado,
            'rol' => $this->rol_formateado,
            'nivel_acceso' => $this->nivel_acceso_formateado,
            'fecha_inicio' => $this->fecha_inicio_formateada,
            'fecha_ultima_actividad' => $this->fecha_ultima_actividad_formateada,
            'fecha_expiracion' => $this->fecha_expiracion_formateada,
            'dias_transcurridos' => $this->dias_transcurridos,
            'minutos_inactividad' => $this->minutos_inactividad,
            'esta_activa' => $this->esta_activa,
            'esta_expirada' => $this->esta_expirada,
            'tiene_token_valido' => $this->tiene_token_valido,
            'es_administrador' => $this->es_administrador,
            'es_operador' => $this->es_operador,
            'es_cliente' => $this->es_cliente,
            'permisos' => $this->permisos_formateados,
            'total_permisos' => count($this->permisos_formateados)
        ];
    }

    // Métodos estáticos
    public static function autenticarUsuario(string $email, string $password): ?Sesion
    {
        // Buscar en clientes
        $cliente = Cliente::where('correo_electronico', $email)->first();
        if ($cliente && Hash::check($password, $cliente->contrasena)) {
            return static::crearSesionParaUsuario($cliente->id, 'cliente', 'cliente');
        }

        // Buscar en operadores
        $operador = Operador::where('correo_electronico', $email)->first();
        if ($operador && Hash::check($password, $operador->contrasena)) {
            $rol = $operador->rol ?? 'operador';
            return static::crearSesionParaUsuario($operador->id, 'operador', $rol);
        }

        return null;
    }

    public static function crearSesionParaUsuario(int $usuarioId, string $tipoUsuario, string $rol): Sesion
    {
        // Verificar si ya existe una sesión activa
        $sesionExistente = static::where('usuario_id', $usuarioId)
            ->where('tipo_usuario', $tipoUsuario)
            ->where('estado_sesion', 'activa')
            ->first();

        if ($sesionExistente) {
            $sesionExistente->cerrarSesion();
        }

        // Crear nueva sesión
        $sesion = static::create([
            'usuario_id' => $usuarioId,
            'tipo_usuario' => $tipoUsuario,
            'rol_asignado' => $rol,
            'estado_sesion' => 'activa',
            'fecha_inicio' => now(),
            'fecha_ultima_actividad' => now(),
            'fecha_expiracion' => now()->addHours(24),
            'ip_cliente' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        // Configurar permisos según el rol
        $sesion->actualizarPermisosPorRol($rol);

        // Generar tokens
        $sesion->generarTokens();

        return $sesion;
    }

    public static function registrarUsuario(array $datos, string $tipoUsuario = 'cliente'): array
    {
        try {
            DB::beginTransaction();

            if ($tipoUsuario === 'cliente') {
                $usuario = Cliente::create([
                    'nombre_completo' => $datos['nombre_completo'],
                    'correo_electronico' => $datos['correo_electronico'],
                    'contrasena' => Hash::make($datos['contrasena']),
                    'telefono' => $datos['telefono'] ?? null,
                    'direccion' => $datos['direccion'] ?? null,
                    'estado' => 'activo'
                ]);
            } else {
                $usuario = Operador::create([
                    'nombre_completo' => $datos['nombre_completo'],
                    'correo_electronico' => $datos['correo_electronico'],
                    'contrasena' => Hash::make($datos['contrasena']),
                    'telefono' => $datos['telefono'] ?? null,
                    'direccion' => $datos['direccion'] ?? null,
                    'rol' => 'operador',
                    'estado' => 'activo'
                ]);
            }

            // Crear sesión automáticamente
            $sesion = static::crearSesionParaUsuario($usuario->id, $tipoUsuario, $tipoUsuario);

            DB::commit();

            return [
                'success' => true,
                'usuario' => $usuario,
                'sesion' => $sesion,
                'tokens' => $sesion->generarTokens()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public static function recuperarContrasena(string $email): array
    {
        // Buscar en clientes
        $cliente = Cliente::where('correo_electronico', $email)->first();
        if ($cliente) {
            return [
                'success' => true,
                'tipo_usuario' => 'cliente',
                'usuario_id' => $cliente->id
            ];
        }

        // Buscar en operadores
        $operador = Operador::where('correo_electronico', $email)->first();
        if ($operador) {
            return [
                'success' => true,
                'tipo_usuario' => 'operador',
                'usuario_id' => $operador->id
            ];
        }

        return [
            'success' => false,
            'error' => 'Usuario no encontrado'
        ];
    }

    public static function cambiarContrasena(int $usuarioId, string $tipoUsuario, string $nuevaContrasena): bool
    {
        try {
            if ($tipoUsuario === 'cliente') {
                $usuario = Cliente::find($usuarioId);
            } else {
                $usuario = Operador::find($usuarioId);
            }

            if (!$usuario) {
                return false;
            }

            $usuario->update(['contrasena' => Hash::make($nuevaContrasena)]);
            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    public static function obtenerEstadisticasGenerales(): array
    {
        $totalSesiones = static::count();
        $sesionesActivas = static::activas()->count();
        $sesionesExpiradas = static::expiradas()->count();
        
        $porTipo = static::select('tipo_usuario', DB::raw('count(*) as total'))
            ->groupBy('tipo_usuario')
            ->get();
        
        $porRol = static::select('rol_asignado', DB::raw('count(*) as total'))
            ->groupBy('rol_asignado')
            ->get();
        
        $porNivel = static::select('nivel_acceso', DB::raw('count(*) as total'))
            ->groupBy('nivel_acceso')
            ->get();
        
        return [
            'total_sesiones' => $totalSesiones,
            'sesiones_activas' => $sesionesActivas,
            'sesiones_expiradas' => $sesionesExpiradas,
            'por_tipo_usuario' => $porTipo,
            'por_rol' => $porRol,
            'por_nivel_acceso' => $porNivel
        ];
    }

    // Métodos privados
    private function obtenerPermisosPorRol(string $rol): array
    {
        $permisos = [
            'cliente' => [],
            'operador' => ['validar_veedurias', 'gestionar_donaciones', 'gestionar_tareas'],
            'administrador' => ['gestionar_usuarios', 'asignar_roles', 'validar_veedurias', 'gestionar_donaciones', 'gestionar_tareas', 'acceder_panel_admin']
        ];

        return $permisos[$rol] ?? [];
    }

    private function obtenerNivelAccesoPorRol(string $rol): int
    {
        $niveles = [
            'cliente' => 1,
            'operador' => 3,
            'administrador' => 5
        ];

        return $niveles[$rol] ?? 1;
    }

    // Boot method para eventos del modelo
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sesion) {
            if (empty($sesion->codigo_sesion)) {
                $sesion->codigo_sesion = static::generarCodigoSesion();
            }
            
            if (empty($sesion->estado_sesion)) {
                $sesion->estado_sesion = 'activa';
            }
            
            if (empty($sesion->fecha_inicio)) {
                $sesion->fecha_inicio = now();
            }
            
            if (empty($sesion->fecha_ultima_actividad)) {
                $sesion->fecha_ultima_actividad = now();
            }
            
            if (empty($sesion->version_sesion)) {
                $sesion->version_sesion = 1;
            }
        });

        static::updating(function ($sesion) {
            // Log de cambios importantes
            if ($sesion->isDirty('rol_asignado')) {
                LogAuditoria::crear([
                    'usuario_id' => $sesion->usuario_id,
                    'tipo_usuario' => $sesion->tipo_usuario,
                    'accion' => 'cambiar_rol_sesion',
                    'entidad' => 'sesion',
                    'entidad_id' => $sesion->id,
                    'datos_anteriores' => ['rol' => $sesion->getOriginal('rol_asignado')],
                    'datos_nuevos' => ['rol' => $sesion->rol_asignado],
                    'ip_cliente' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]);
            }
        });
    }

    // Método estático para generar código único
    protected static function generarCodigoSesion(): string
    {
        do {
            $codigo = 'SES-' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (static::where('codigo_sesion', $codigo)->exists());

        return $codigo;
    }
}
