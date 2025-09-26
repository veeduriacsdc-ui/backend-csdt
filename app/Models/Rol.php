<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rol extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rol';
    protected $primaryKey = 'id';

    protected $fillable = [
        'nom',
        'des',
        'est',
        'perm'
    ];

    protected $casts = [
        'perm' => 'array',
    ];

    // Relaciones
    public function usuarios()
    {
        return $this->belongsToMany(Usuario::class, 'usu_rol', 'rol_id', 'usu_id')
                    ->withPivot(['act', 'asig_por', 'asig_en', 'not'])
                    ->withTimestamps();
    }

    public function permisos()
    {
        return $this->belongsToMany(Permiso::class, 'rol_perm', 'rol_id', 'perm_id')
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

    // Métodos de utilidad
    public function tienePermiso($permiso)
    {
        if (is_array($this->perm)) {
            return in_array($permiso, $this->perm);
        }
        return false;
    }

    public function agregarPermiso($permiso)
    {
        $permisos = $this->perm ?? [];
        if (!in_array($permiso, $permisos)) {
            $permisos[] = $permiso;
            $this->update(['perm' => $permisos]);
        }
    }

    public function quitarPermiso($permiso)
    {
        $permisos = $this->perm ?? [];
        $permisos = array_filter($permisos, function($p) use ($permiso) {
            return $p !== $permiso;
        });
        $this->update(['perm' => array_values($permisos)]);
    }

    public function activar()
    {
        $this->update(['est' => 'act']);
    }

    public function desactivar()
    {
        $this->update(['est' => 'ina']);
    }
}