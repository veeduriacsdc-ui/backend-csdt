<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class Operador extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'operadores';

    protected $fillable = [
        'codigo_operador',
        'nombre_completo',
        'correo_electronico',
        'contrasena',
        'telefono',
        'departamento',
        'cargo',
        'estado',
        'fecha_registro',
        'ultimo_acceso',
        'permisos_especiales',
        'nivel_acceso',
        'supervisor_id',
        'zona_asignada',
        'especialidad',
        'experiencia_anos',
        'formacion_academica',
        'certificaciones',
        'notas_adicionales'
    ];

    protected $hidden = [
        'contrasena',
        'permisos_especiales'
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
        'ultimo_acceso' => 'datetime',
        'permisos_especiales' => 'array',
        'experiencia_anos' => 'integer',
        'estado' => 'string'
    ];

    // Relaciones
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Operador::class, 'supervisor_id');
    }

    public function supervisados(): HasMany
    {
        return $this->hasMany(Operador::class, 'supervisor_id');
    }

    public function veedurias(): HasMany
    {
        return $this->hasMany(Veeduria::class, 'operador_asignado_id');
    }

    public function tareas(): HasMany
    {
        return $this->hasMany(Tarea::class, 'operador_asignado_id');
    }

    public function donacionesValidadas(): HasMany
    {
        return $this->hasMany(Donacion::class, 'operador_validador_id');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'usuario_id')
            ->where('tipo_usuario', 'operador');
    }

    public function logsAuditoria(): HasMany
    {
        return $this->hasMany(LogAuditoria::class, 'usuario_id')
            ->where('tipo_usuario', 'operador');
    }

    // Scopes
    public function scopeActivos(Builder $query): void
    {
        $query->where('estado', 'activo');
    }

    public function scopeInactivos(Builder $query): void
    {
        $query->where('estado', 'inactivo');
    }

    public function scopePorDepartamento(Builder $query, string $departamento): void
    {
        $query->where('departamento', $departamento);
    }

    public function scopePorZona(Builder $query, string $zona): void
    {
        $query->where('zona_asignada', $zona);
    }

    public function scopePorEspecialidad(Builder $query, string $especialidad): void
    {
        $query->where('especialidad', $especialidad);
    }

    public function scopeConExperiencia(Builder $query, int $anosMinimos): void
    {
        $query->where('experiencia_anos', '>=', $anosMinimos);
    }

    public function scopeConPermisos(Builder $query, array $permisos): void
    {
        $query->whereJsonContains('permisos_especiales', $permisos);
    }

    public function scopePorNivelAcceso(Builder $query, string $nivel): void
    {
        $query->where('nivel_acceso', $nivel);
    }

    // Accessors
    public function getNombreCompletoFormateadoAttribute(): string
    {
        return ucwords(strtolower($this->nombre_completo));
    }

    public function getEstadoFormateadoAttribute(): string
    {
        return ucfirst($this->estado);
    }

    public function getExperienciaFormateadaAttribute(): string
    {
        return $this->experiencia_anos . ' año(s)';
    }

    public function getUltimoAccesoFormateadoAttribute(): string
    {
        return $this->ultimo_acceso ? $this->ultimo_acceso->diffForHumans() : 'Nunca';
    }

    public function getPermisosFormateadosAttribute(): array
    {
        return $this->permisos_especiales ?? [];
    }

    // Mutators
    public function setContrasenaAttribute($value): void
    {
        if ($value) {
            $this->attributes['contrasena'] = Hash::make($value);
        }
    }

    public function setNombreCompletoAttribute($value): void
    {
        $this->attributes['nombre_completo'] = ucwords(strtolower($value));
    }

    public function setCorreoElectronicoAttribute($value): void
    {
        $this->attributes['correo_electronico'] = strtolower($value);
    }

    // Métodos
    public function tienePermiso(string $permiso): bool
    {
        return in_array($permiso, $this->permisos_especiales ?? []);
    }

    public function tienePermisos(array $permisos): bool
    {
        return !empty(array_intersect($permisos, $this->permisos_especiales ?? []));
    }

    public function esSupervisor(): bool
    {
        return $this->supervisados()->exists();
    }

    public function esSupervisado(): bool
    {
        return !is_null($this->supervisor_id);
    }

    public function puedeGestionarVeedurias(): bool
    {
        return $this->tienePermiso('gestionar_veedurias') || $this->nivel_acceso === 'supervisor';
    }

    public function puedeValidarDonaciones(): bool
    {
        return $this->tienePermiso('validar_donaciones') || $this->nivel_acceso === 'supervisor';
    }

    public function puedeGestionarTareas(): bool
    {
        return $this->tienePermiso('gestionar_tareas') || $this->nivel_acceso === 'supervisor';
    }

    public function actualizarUltimoAcceso(): void
    {
        $this->update(['ultimo_acceso' => now()]);
    }

    public function cambiarEstado(string $nuevoEstado): bool
    {
        $estadosValidos = ['activo', 'inactivo', 'suspendido', 'retirado'];
        
        if (!in_array($nuevoEstado, $estadosValidos)) {
            return false;
        }

        return $this->update(['estado' => $nuevoEstado]);
    }

    public function asignarSupervisor(int $supervisorId): bool
    {
        if ($supervisorId === $this->id) {
            return false; // No puede ser su propio supervisor
        }

        $supervisor = Operador::find($supervisorId);
        if (!$supervisor || $supervisor->estado !== 'activo') {
            return false;
        }

        return $this->update(['supervisor_id' => $supervisorId]);
    }

    public function obtenerEstadisticas(): array
    {
        return [
            'veedurias_asignadas' => $this->veedurias()->count(),
            'veedurias_completadas' => $this->veedurias()->where('estado', 'completada')->count(),
            'veedurias_pendientes' => $this->veedurias()->where('estado', 'pendiente')->count(),
            'tareas_asignadas' => $this->tareas()->count(),
            'tareas_completadas' => $this->tareas()->where('estado', 'completada')->count(),
            'tareas_pendientes' => $this->tareas()->where('estado', 'pendiente')->count(),
            'donaciones_validadas' => $this->donacionesValidadas()->count(),
            'notificaciones_no_leidas' => $this->notificaciones()->where('leida', false)->count()
        ];
    }

    public function obtenerRendimiento(): array
    {
        $veeduriasCompletadas = $this->veedurias()->where('estado', 'completada')->count();
        $veeduriasTotales = $this->veedurias()->count();
        $tareasCompletadas = $this->tareas()->where('estado', 'completada')->count();
        $tareasTotales = $this->tareas()->count();

        return [
            'eficiencia_veedurias' => $veeduriasTotales > 0 ? round(($veeduriasCompletadas / $veeduriasTotales) * 100, 2) : 0,
            'eficiencia_tareas' => $tareasTotales > 0 ? round(($tareasCompletadas / $tareasTotales) * 100, 2) : 0,
            'promedio_veedurias_mes' => $this->veedurias()
                ->where('fecha_creacion', '>=', now()->subMonth())
                ->count(),
            'promedio_tareas_mes' => $this->tareas()
                ->where('fecha_creacion', '>=', now()->subMonth())
                ->count()
        ];
    }

    // Boot method para eventos del modelo
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($operador) {
            if (empty($operador->codigo_operador)) {
                $operador->codigo_operador = static::generarCodigoOperador();
            }
            
            if (empty($operador->fecha_registro)) {
                $operador->fecha_registro = now();
            }
        });

        static::updating(function ($operador) {
            // Log de cambios importantes
            if ($operador->isDirty('estado')) {
                LogAuditoria::crear([
                    'usuario_id' => $operador->id,
                    'tipo_usuario' => 'operador',
                    'accion' => 'cambiar_estado',
                    'entidad' => 'operador',
                    'entidad_id' => $operador->id,
                    'datos_anteriores' => ['estado' => $operador->getOriginal('estado')],
                    'datos_nuevos' => ['estado' => $operador->estado],
                    'ip_cliente' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]);
            }
        });
    }

    // Método estático para generar código único
    protected static function generarCodigoOperador(): string
    {
        do {
            $codigo = 'OP-' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (static::where('codigo_operador', $codigo)->exists());

        return $codigo;
    }
}
