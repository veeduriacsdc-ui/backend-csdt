<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permiso extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'permisos';

    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'categoria',
        'modulo',
        'funcion',
        'recurso',
        'accion',
        'es_activo',
        'nivel_requerido',
        'activo',
        'orden',
    ];

    protected $casts = [
        'es_activo' => 'boolean',
        'nivel_requerido' => 'integer',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    // Relaciones
    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'rol_permisos', 'permiso_id', 'rol_id')
            ->withPivot('otorgado', 'asignado_en', 'asignado_por')
            ->withTimestamps();
    }

    public function permisosEspeciales()
    {
        return $this->hasMany(PermisoEspecial::class, 'permiso_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorModulo($query, $modulo)
    {
        return $query->where('modulo', $modulo);
    }

    public function scopePorRecurso($query, $recurso)
    {
        return $query->where('recurso', $recurso);
    }

    // Métodos
    public function tienePermiso($usuario, $recurso, $funcion)
    {
        // Verificar si el usuario tiene este permiso específico
        return $this->where('Recurso', $recurso)
            ->where('Funcion', $funcion)
            ->where('EsActivo', true)
            ->exists();
    }

    public static function obtenerPermisosPorModulo($modulo)
    {
        return self::activos()
            ->porModulo($modulo)
            ->orderBy('Orden')
            ->get();
    }

    public static function crearPermiso($datos)
    {
        return self::create([
            'Nombre' => $datos['nombre'],
            'Slug' => $datos['slug'],
            'Descripcion' => $datos['descripcion'] ?? null,
            'Modulo' => $datos['modulo'],
            'Funcion' => $datos['funcion'],
            'Recurso' => $datos['recurso'],
            'EsActivo' => $datos['es_activo'] ?? true,
            'Orden' => $datos['orden'] ?? 0,
        ]);
    }
}
