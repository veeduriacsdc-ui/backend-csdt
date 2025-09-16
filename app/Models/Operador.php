<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Operador extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'operadores';

    protected $fillable = [
        'nombres',
        'apellidos',
        'correo',
        'usuario',
        'contrasena',
        'telefono',
        'documento_identidad',
        'tipo_documento',
        'departamento',
        'ciudad',
        'direccion',
        'fecha_nacimiento',
        'estado',
        'rol',
        'profesion',
        'especializacion',
        'acepto_terminos',
        'acepto_politicas',
        'ultimo_acceso',
        'supervisor_id',
        'especialidad',
        'nivel_experiencia',
        'certificaciones',
        'observaciones',
        'activo'
    ];

    protected $hidden = [
        'contrasena',
        'remember_token',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'ultimo_acceso' => 'datetime',
        'certificaciones' => 'array',
        'activo' => 'boolean',
        'acepto_terminos' => 'boolean',
        'acepto_politicas' => 'boolean',
    ];

    protected $dates = ['deleted_at'];

    /**
     * Relación con UsuarioSistema
     */
    public function usuarioSistema()
    {
        return $this->belongsTo(UsuarioSistema::class, 'usuario_sistema_id');
    }

    /**
     * Relación con Supervisor (otro operador)
     */
    public function supervisor()
    {
        return $this->belongsTo(Operador::class, 'supervisor_id');
    }

    /**
     * Relación con subordinados
     */
    public function subordinados()
    {
        return $this->hasMany(Operador::class, 'supervisor_id');
    }

    /**
     * Relación con PQRSFD asignadas
     */
    public function pqrsfdAsignadas()
    {
        return $this->hasMany(PQRSFD::class, 'operador_id');
    }

    /**
     * Relación con actividades de caso
     */
    public function actividadesCaso()
    {
        return $this->hasMany(ActividadCaso::class, 'operador_id');
    }

    /**
     * Relación con tareas
     */
    public function tareas()
    {
        return $this->hasMany(Tarea::class, 'operador_id');
    }

    /**
     * Relación con clientes asignados
     */
    public function clientesAsignados()
    {
        return $this->hasMany(Cliente::class, 'operador_asignado_id');
    }

    /**
     * Relación con reportes generados
     */
    public function reportes()
    {
        return $this->hasMany(Reporte::class, 'operador_id');
    }

    /**
     * Relación con logs de auditoría
     */
    public function logsAuditoria()
    {
        return $this->hasMany(LogAuditoria::class, 'operador_id');
    }

    /**
     * Scope para operadores activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para operadores por departamento
     */
    public function scopePorDepartamento($query, $departamento)
    {
        return $query->where('departamento', $departamento);
    }

    /**
     * Scope para operadores por especialidad
     */
    public function scopePorEspecialidad($query, $especialidad)
    {
        return $query->where('especialidad', $especialidad);
    }

    /**
     * Accessor para nombre completo
     */
    public function getNombreCompletoAttribute()
    {
        return $this->nombres . ' ' . $this->apellidos;
    }

    /**
     * Accessor para años de experiencia
     */
    public function getAniosExperienciaAttribute()
    {
        if ($this->fecha_ingreso) {
            return $this->fecha_ingreso->diffInYears(now());
        }
        return 0;
    }

    /**
     * Método para obtener estadísticas del operador
     */
    public function obtenerEstadisticas()
    {
        return [
            'pqrsfd_asignadas' => $this->pqrsfdAsignadas()->count(),
            'pqrsfd_completadas' => $this->pqrsfdAsignadas()->where('estado', 'completada')->count(),
            'tareas_asignadas' => $this->tareas()->count(),
            'tareas_completadas' => $this->tareas()->where('estado', 'completada')->count(),
            'clientes_asignados' => $this->clientesAsignados()->count(),
            'reportes_generados' => $this->reportes()->count(),
            'eficiencia' => $this->calcularEficiencia()
        ];
    }

    /**
     * Calcular eficiencia del operador
     */
    public function calcularEficiencia()
    {
        $tareasCompletadas = $this->tareas()->where('estado', 'completada')->count();
        $tareasTotales = $this->tareas()->count();
        
        if ($tareasTotales > 0) {
            return round(($tareasCompletadas / $tareasTotales) * 100, 2);
        }
        
        return 0;
    }

    /**
     * Método para verificar si el operador puede acceder a una funcionalidad
     */
    public function puedeAcceder($funcionalidad)
    {
        // Lógica para verificar permisos específicos del operador
        $permisosEspeciales = [
            'analisis_forense' => in_array($this->especialidad, ['forense', 'auditoria']),
            'geoanálisis' => in_array($this->especialidad, ['geografico', 'cartografico']),
            'justicia_transicional' => $this->nivel_experiencia >= 3
        ];

        return $permisosEspeciales[$funcionalidad] ?? false;
    }

    /**
     * Método para obtener carga de trabajo actual
     */
    public function obtenerCargaTrabajo()
    {
        $tareasPendientes = $this->tareas()->where('estado', 'pendiente')->count();
        $tareasEnProgreso = $this->tareas()->where('estado', 'en_progreso')->count();
        $pqrsfdPendientes = $this->pqrsfdAsignadas()->where('estado', 'pendiente')->count();

        return [
            'tareas_pendientes' => $tareasPendientes,
            'tareas_en_progreso' => $tareasEnProgreso,
            'pqrsfd_pendientes' => $pqrsfdPendientes,
            'carga_total' => $tareasPendientes + $tareasEnProgreso + $pqrsfdPendientes,
            'nivel_carga' => $this->calcularNivelCarga($tareasPendientes + $tareasEnProgreso + $pqrsfdPendientes)
        ];
    }

    /**
     * Calcular nivel de carga de trabajo
     */
    private function calcularNivelCarga($cargaTotal)
    {
        if ($cargaTotal <= 5) return 'baja';
        if ($cargaTotal <= 10) return 'media';
        if ($cargaTotal <= 15) return 'alta';
        return 'critica';
    }
}