<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermisoMejorado extends Model
{
    use HasFactory;

    protected $table = 'perm';
    protected $primaryKey = 'id';

    protected $fillable = [
        'nom', 'des', 'mod', 'est'
    ];

    protected $casts = [
        'est' => 'string',
    ];

    // Constantes para módulos
    const MODULO_USUARIOS = 'usuarios';
    const MODULO_VEEDURIAS = 'veedurias';
    const MODULO_DONACIONES = 'donaciones';
    const MODULO_TAREAS = 'tareas';
    const MODULO_ARCHIVOS = 'archivos';
    const MODULO_ROLES = 'roles';
    const MODULO_PERMISOS = 'permisos';
    const MODULO_CONFIGURACIONES = 'configuraciones';
    const MODULO_LOGS = 'logs';
    const MODULO_ESTADISTICAS = 'estadisticas';
    const MODULO_IA = 'ia';

    // Constantes para acciones
    const ACCION_CREAR = 'crear';
    const ACCION_LEER = 'leer';
    const ACCION_ACTUALIZAR = 'actualizar';
    const ACCION_ELIMINAR = 'eliminar';
    const ACCION_ASIGNAR = 'asignar';
    const ACCION_QUITAR = 'quitar';
    const ACCION_ACTIVAR = 'activar';
    const ACCION_DESACTIVAR = 'desactivar';
    const ACCION_VERIFICAR = 'verificar';
    const ACCION_RADICAR = 'radicar';
    const ACCION_CERRAR = 'cerrar';
    const ACCION_CANCELAR = 'cancelar';
    const ACCION_ANALIZAR = 'analizar';
    const ACCION_GENERAR = 'generar';

    // Constantes para estados
    const ESTADO_ACTIVO = 'act';
    const ESTADO_INACTIVO = 'ina';

    // Relaciones
    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'rol_perm', 'perm_id', 'rol_id')
                    ->withTimestamps();
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('est', self::ESTADO_ACTIVO);
    }

    public function scopePorModulo($query, $modulo)
    {
        return $query->where('mod', $modulo);
    }

    public function scopePorAccion($query, $accion)
    {
        return $query->where('nom', 'like', "%{$accion}%");
    }

    // Métodos estáticos para crear permisos del sistema
    public static function crearPermisosSistema()
    {
        $permisos = [
            // Módulo de Usuarios
            ['nom' => 'usuarios_crear', 'des' => 'Crear usuarios', 'mod' => self::MODULO_USUARIOS],
            ['nom' => 'usuarios_leer', 'des' => 'Ver usuarios', 'mod' => self::MODULO_USUARIOS],
            ['nom' => 'usuarios_actualizar', 'des' => 'Actualizar usuarios', 'mod' => self::MODULO_USUARIOS],
            ['nom' => 'usuarios_eliminar', 'des' => 'Eliminar usuarios', 'mod' => self::MODULO_USUARIOS],
            ['nom' => 'usuarios_asignar_rol', 'des' => 'Asignar roles a usuarios', 'mod' => self::MODULO_USUARIOS],
            ['nom' => 'usuarios_quitar_rol', 'des' => 'Quitar roles a usuarios', 'mod' => self::MODULO_USUARIOS],
            ['nom' => 'usuarios_activar', 'des' => 'Activar usuarios', 'mod' => self::MODULO_USUARIOS],
            ['nom' => 'usuarios_desactivar', 'des' => 'Desactivar usuarios', 'mod' => self::MODULO_USUARIOS],
            ['nom' => 'usuarios_verificar', 'des' => 'Verificar usuarios', 'mod' => self::MODULO_USUARIOS],

            // Módulo de Veedurías
            ['nom' => 'veedurias_crear', 'des' => 'Crear veedurías', 'mod' => self::MODULO_VEEDURIAS],
            ['nom' => 'veedurias_leer', 'des' => 'Ver veedurías', 'mod' => self::MODULO_VEEDURIAS],
            ['nom' => 'veedurias_actualizar', 'des' => 'Actualizar veedurías', 'mod' => self::MODULO_VEEDURIAS],
            ['nom' => 'veedurias_eliminar', 'des' => 'Eliminar veedurías', 'mod' => self::MODULO_VEEDURIAS],
            ['nom' => 'veedurias_asignar_operador', 'des' => 'Asignar operador a veedurías', 'mod' => self::MODULO_VEEDURIAS],
            ['nom' => 'veedurias_radicar', 'des' => 'Radicar veedurías', 'mod' => self::MODULO_VEEDURIAS],
            ['nom' => 'veedurias_cerrar', 'des' => 'Cerrar veedurías', 'mod' => self::MODULO_VEEDURIAS],
            ['nom' => 'veedurias_cancelar', 'des' => 'Cancelar veedurías', 'mod' => self::MODULO_VEEDURIAS],

            // Módulo de Donaciones
            ['nom' => 'donaciones_crear', 'des' => 'Crear donaciones', 'mod' => self::MODULO_DONACIONES],
            ['nom' => 'donaciones_leer', 'des' => 'Ver donaciones', 'mod' => self::MODULO_DONACIONES],
            ['nom' => 'donaciones_actualizar', 'des' => 'Actualizar donaciones', 'mod' => self::MODULO_DONACIONES],
            ['nom' => 'donaciones_eliminar', 'des' => 'Eliminar donaciones', 'mod' => self::MODULO_DONACIONES],
            ['nom' => 'donaciones_confirmar', 'des' => 'Confirmar donaciones', 'mod' => self::MODULO_DONACIONES],
            ['nom' => 'donaciones_rechazar', 'des' => 'Rechazar donaciones', 'mod' => self::MODULO_DONACIONES],

            // Módulo de Tareas
            ['nom' => 'tareas_crear', 'des' => 'Crear tareas', 'mod' => self::MODULO_TAREAS],
            ['nom' => 'tareas_leer', 'des' => 'Ver tareas', 'mod' => self::MODULO_TAREAS],
            ['nom' => 'tareas_actualizar', 'des' => 'Actualizar tareas', 'mod' => self::MODULO_TAREAS],
            ['nom' => 'tareas_eliminar', 'des' => 'Eliminar tareas', 'mod' => self::MODULO_TAREAS],
            ['nom' => 'tareas_asignar', 'des' => 'Asignar tareas', 'mod' => self::MODULO_TAREAS],
            ['nom' => 'tareas_completar', 'des' => 'Completar tareas', 'mod' => self::MODULO_TAREAS],
            ['nom' => 'tareas_cancelar', 'des' => 'Cancelar tareas', 'mod' => self::MODULO_TAREAS],

            // Módulo de Archivos
            ['nom' => 'archivos_crear', 'des' => 'Subir archivos', 'mod' => self::MODULO_ARCHIVOS],
            ['nom' => 'archivos_leer', 'des' => 'Ver archivos', 'mod' => self::MODULO_ARCHIVOS],
            ['nom' => 'archivos_actualizar', 'des' => 'Actualizar archivos', 'mod' => self::MODULO_ARCHIVOS],
            ['nom' => 'archivos_eliminar', 'des' => 'Eliminar archivos', 'mod' => self::MODULO_ARCHIVOS],
            ['nom' => 'archivos_descargar', 'des' => 'Descargar archivos', 'mod' => self::MODULO_ARCHIVOS],

            // Módulo de Roles
            ['nom' => 'roles_crear', 'des' => 'Crear roles', 'mod' => self::MODULO_ROLES],
            ['nom' => 'roles_leer', 'des' => 'Ver roles', 'mod' => self::MODULO_ROLES],
            ['nom' => 'roles_actualizar', 'des' => 'Actualizar roles', 'mod' => self::MODULO_ROLES],
            ['nom' => 'roles_eliminar', 'des' => 'Eliminar roles', 'mod' => self::MODULO_ROLES],
            ['nom' => 'roles_asignar_permisos', 'des' => 'Asignar permisos a roles', 'mod' => self::MODULO_ROLES],

            // Módulo de Permisos
            ['nom' => 'permisos_crear', 'des' => 'Crear permisos', 'mod' => self::MODULO_PERMISOS],
            ['nom' => 'permisos_leer', 'des' => 'Ver permisos', 'mod' => self::MODULO_PERMISOS],
            ['nom' => 'permisos_actualizar', 'des' => 'Actualizar permisos', 'mod' => self::MODULO_PERMISOS],
            ['nom' => 'permisos_eliminar', 'des' => 'Eliminar permisos', 'mod' => self::MODULO_PERMISOS],

            // Módulo de Configuraciones
            ['nom' => 'configuraciones_crear', 'des' => 'Crear configuraciones', 'mod' => self::MODULO_CONFIGURACIONES],
            ['nom' => 'configuraciones_leer', 'des' => 'Ver configuraciones', 'mod' => self::MODULO_CONFIGURACIONES],
            ['nom' => 'configuraciones_actualizar', 'des' => 'Actualizar configuraciones', 'mod' => self::MODULO_CONFIGURACIONES],
            ['nom' => 'configuraciones_eliminar', 'des' => 'Eliminar configuraciones', 'mod' => self::MODULO_CONFIGURACIONES],

            // Módulo de Logs
            ['nom' => 'logs_leer', 'des' => 'Ver logs', 'mod' => self::MODULO_LOGS],
            ['nom' => 'logs_exportar', 'des' => 'Exportar logs', 'mod' => self::MODULO_LOGS],

            // Módulo de Estadísticas
            ['nom' => 'estadisticas_ver', 'des' => 'Ver estadísticas', 'mod' => self::MODULO_ESTADISTICAS],
            ['nom' => 'estadisticas_exportar', 'des' => 'Exportar estadísticas', 'mod' => self::MODULO_ESTADISTICAS],

            // Módulo de IA
            ['nom' => 'ia_analizar', 'des' => 'Analizar con IA', 'mod' => self::MODULO_IA],
            ['nom' => 'ia_generar', 'des' => 'Generar contenido con IA', 'mod' => self::MODULO_IA],
            ['nom' => 'ia_recomendar', 'des' => 'Obtener recomendaciones de IA', 'mod' => self::MODULO_IA],
        ];

        foreach ($permisos as $permiso) {
            self::firstOrCreate(
                ['nom' => $permiso['nom']],
                array_merge($permiso, ['est' => self::ESTADO_ACTIVO])
            );
        }
    }

    // Métodos de utilidad
    public function activar()
    {
        $this->update(['est' => self::ESTADO_ACTIVO]);
    }

    public function desactivar()
    {
        $this->update(['est' => self::ESTADO_INACTIVO]);
    }

    public function estaActivo()
    {
        return $this->est === self::ESTADO_ACTIVO;
    }

    // Reglas de validación
    public static function reglas()
    {
        return [
            'nom' => 'required|string|max:100|unique:perm,nom',
            'des' => 'nullable|string|max:255',
            'mod' => 'required|string|max:50',
            'est' => 'nullable|string|in:act,ina',
        ];
    }

    // Mensajes de validación
    public static function mensajes()
    {
        return [
            'nom.required' => 'El nombre del permiso es obligatorio',
            'nom.unique' => 'Este nombre de permiso ya existe',
            'nom.max' => 'El nombre no puede tener más de 100 caracteres',
            'des.max' => 'La descripción no puede tener más de 255 caracteres',
            'mod.required' => 'El módulo es obligatorio',
            'mod.max' => 'El módulo no puede tener más de 50 caracteres',
            'est.in' => 'El estado debe ser activo o inactivo',
        ];
    }
}
