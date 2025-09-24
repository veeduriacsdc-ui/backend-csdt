<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    use HasFactory;

    protected $table = 'roles';
    
    protected $fillable = [
        'nom', 'des', 'est', 'per'
    ];

    protected $casts = [
        'per' => 'array',
    ];

    // Relaciones
    public function usuarios()
    {
        return $this->belongsToMany(Usuario::class, 'usuarios_roles', 'rol_id', 'usu_id')
            ->withPivot('act', 'asig_en', 'asig_por', 'not')
            ->withTimestamps();
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('est', 'act');
    }

    public function scopeInactivos($query)
    {
        return $query->where('est', 'ina');
    }

    // Métodos de negocio
    public function getEstadoColorAttribute()
    {
        return match ($this->est) {
            'act' => 'success',
            'ina' => 'danger',
            default => 'secondary'
        };
    }

    public function getEstadoTextoAttribute()
    {
        return match ($this->est) {
            'act' => 'Activo',
            'ina' => 'Inactivo',
            default => 'Desconocido'
        };
    }

    public function activar()
    {
        $this->update(['est' => 'act']);
    }

    public function desactivar()
    {
        $this->update(['est' => 'ina']);
    }

    public function tienePermiso($permiso)
    {
        $permisos = $this->per ?? [];
        return in_array($permiso, $permisos);
    }

    public function agregarPermiso($permiso)
    {
        $permisos = $this->per ?? [];
        if (!in_array($permiso, $permisos)) {
            $permisos[] = $permiso;
            $this->update(['per' => $permisos]);
        }
    }

    public function quitarPermiso($permiso)
    {
        $permisos = $this->per ?? [];
        $permisos = array_filter($permisos, function($p) use ($permiso) {
            return $p !== $permiso;
        });
        $this->update(['per' => array_values($permisos)]);
    }

    // Validaciones
    public static function reglas($id = null)
    {
        $uniqueNombre = 'unique:roles,nom';
        if ($id) {
            $uniqueNombre .= ','.$id.',id';
        }

        return [
            'nom' => 'required|string|max:100|'.$uniqueNombre,
            'des' => 'nullable|string|max:255',
            'est' => 'sometimes|in:act,ina',
            'per' => 'nullable|array',
        ];
    }

    public static function mensajes()
    {
        return [
            'nom.required' => 'El nombre del rol es obligatorio.',
            'nom.max' => 'El nombre del rol no puede exceder 100 caracteres.',
            'nom.unique' => 'Este nombre de rol ya existe.',
            'des.max' => 'La descripción no puede exceder 255 caracteres.',
            'est.in' => 'El estado debe ser activo o inactivo.',
            'per.array' => 'Los permisos deben ser un arreglo.',
        ];
    }
}