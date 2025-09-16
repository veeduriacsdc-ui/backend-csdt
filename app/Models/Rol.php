<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rol extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'roles';

    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'tipo',
        'activo',
        'editable',
        'nivel_acceso',
        'es_activo',
        'es_sistema',
        'permisos_especiales',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'editable' => 'boolean',
        'nivel_acceso' => 'integer',
        'es_activo' => 'boolean',
        'es_sistema' => 'boolean',
        'permisos_especiales' => 'array',
    ];

    // Relaciones
    public function permisos()
    {
        return $this->belongsToMany(Permiso::class, 'rol_permisos', 'rol_id', 'permiso_id')
            ->withPivot('otorgado', 'asignado_en', 'asignado_por')
            ->withTimestamps();
    }

    public function usuarios()
    {
        return $this->hasMany(UsuarioRol::class, 'rol_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeSistema($query)
    {
        return $query->where('tipo', 'Sistema');
    }

    public function scopePersonalizados($query)
    {
        return $query->where('tipo', 'Personalizado');
    }

    public function scopePorNivelAcceso($query, $nivel)
    {
        return $query->where('nivel_acceso', $nivel);
    }

    // Métodos
    public function tienePermiso($permiso)
    {
        return $this->permisos()
            ->where('permiso_id', $permiso)
            ->wherePivot('otorgado', true)
            ->exists();
    }

    public function asignarPermiso($permiso, $otorgado = true, $asignadoPor = null)
    {
        $this->permisos()->syncWithoutDetaching([
            $permiso => [
                'otorgado' => $otorgado,
                'asignado_en' => now(),
                'asignado_por' => $asignadoPor,
            ],
        ]);
    }

    public function revocarPermiso($permiso)
    {
        $this->permisos()->detach($permiso);
    }

    public function obtenerPermisosActivos()
    {
        return $this->permisos()
            ->wherePivot('otorgado', true)
            ->activos()
            ->get();
    }

    public static function obtenerRolesPorNivel($nivel)
    {
        return self::activos()
            ->porNivelAcceso($nivel)
            ->orderBy('nombre')
            ->get();
    }

    public static function crearRol($datos)
    {
        return self::create([
            'nombre' => $datos['nombre'],
            'slug' => $datos['slug'],
            'descripcion' => $datos['descripcion'] ?? null,
            'tipo' => $datos['tipo'] ?? 'Personalizado',
            'activo' => $datos['activo'] ?? true,
            'editable' => $datos['editable'] ?? true,
            'nivel_acceso' => $datos['nivel_acceso'] ?? 1,
            'es_activo' => $datos['es_activo'] ?? true,
            'es_sistema' => $datos['es_sistema'] ?? false,
            'permisos_especiales' => $datos['permisos_especiales'] ?? null,
        ]);
    }

    // Constantes para niveles de acceso
    const NIVEL_CLIENTE = 1;

    const NIVEL_OPERADOR = 2;

    const NIVEL_ADMINISTRADOR = 3;

    const NIVEL_SUPER_ADMIN = 4;
}
