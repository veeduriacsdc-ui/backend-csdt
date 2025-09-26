<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'usu';
    protected $primaryKey = 'id';

    protected $fillable = [
        'nom',
        'ape', 
        'cor',
        'con',
        'tel',
        'doc',
        'tip_doc',
        'fec_nac',
        'dir',
        'ciu',
        'dep',
        'gen',
        'rol',
        'est',
        'cor_ver',
        'cor_ver_en',
        'ult_acc',
        'not'
    ];

    protected $hidden = [
        'con',
        'remember_token',
    ];

    protected $casts = [
        'cor_ver' => 'boolean',
        'cor_ver_en' => 'datetime',
        'ult_acc' => 'datetime',
        'fec_nac' => 'date',
    ];

    // Relaciones
    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'usu_rol', 'usu_id', 'rol_id')
                    ->withPivot(['act', 'asig_por', 'asig_en', 'not'])
                    ->withTimestamps();
    }

    public function veedurias()
    {
        return $this->hasMany(Veeduria::class, 'usu_id');
    }

    public function veeduriasAsignadas()
    {
        return $this->hasMany(Veeduria::class, 'ope_id');
    }

    public function donaciones()
    {
        return $this->hasMany(Donacion::class, 'usu_id');
    }

    public function tareasAsignadas()
    {
        return $this->hasMany(Tarea::class, 'asig_a');
    }

    public function tareasCreadas()
    {
        return $this->hasMany(Tarea::class, 'asig_por');
    }

    public function archivos()
    {
        return $this->hasMany(Archivo::class, 'usu_id');
    }

    public function analisisIA()
    {
        return $this->hasMany(AnalisisIA::class, 'usu_id');
    }

    public function narracionesIA()
    {
        return $this->hasMany(NarracionIA::class, 'usu_id');
    }

    public function logs()
    {
        return $this->hasMany(Log::class, 'usu_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('est', 'act');
    }

    public function scopePorRol($query, $rol)
    {
        return $query->where('rol', $rol);
    }

    public function scopeClientes($query)
    {
        return $query->where('rol', 'cli');
    }

    public function scopeOperadores($query)
    {
        return $query->where('rol', 'ope');
    }

    public function scopeAdministradores($query)
    {
        return $query->where('rol', 'adm');
    }

    // Accessors
    public function getNombreCompletoAttribute()
    {
        return trim($this->nom . ' ' . $this->ape);
    }

    public function getInicialesAttribute()
    {
        $iniciales = '';
        if ($this->nom) $iniciales .= strtoupper(substr($this->nom, 0, 1));
        if ($this->ape) $iniciales .= strtoupper(substr($this->ape, 0, 1));
        return $iniciales;
    }

    // Mutators
    public function setCorAttribute($value)
    {
        $this->attributes['cor'] = strtolower(trim($value));
    }

    public function setNomAttribute($value)
    {
        $this->attributes['nom'] = ucwords(strtolower(trim($value)));
    }

    public function setApeAttribute($value)
    {
        $this->attributes['ape'] = ucwords(strtolower(trim($value)));
    }

    public function setConAttribute($value)
    {
        // Solo hashear si no está ya hasheado
        if (!password_get_info($value)['algo']) {
            $this->attributes['con'] = Hash::make($value);
        } else {
            $this->attributes['con'] = $value;
        }
    }

    // Métodos de utilidad


    public function esCliente()
    {
        return $this->rol === 'cli';
    }

    public function esOperador()
    {
        return $this->rol === 'ope';
    }

    public function esAdministrador()
    {
        return $this->rol === 'adm';
    }

    public function esAdministradorGeneral()
    {
        return $this->rol === 'adm_gen';
    }

    public function puedeGestionarUsuarios()
    {
        return $this->esAdministradorGeneral() || 
               $this->tienePermiso('usuarios_crear');
    }

    public function puedeGestionarRoles()
    {
        return $this->esAdministradorGeneral() || 
               $this->tienePermiso('roles_crear');
    }

    public function puedeGestionarPermisos()
    {
        return $this->esAdministradorGeneral() || 
               $this->tienePermiso('permisos_crear');
    }

    public function puedeGestionarVeedurias()
    {
        return $this->esAdministradorGeneral() || 
               $this->tienePermiso('veedurias_crear');
    }

    public function puedeGestionarDonaciones()
    {
        return $this->esAdministradorGeneral() || 
               $this->tienePermiso('donaciones_crear');
    }

    public function puedeGestionarTareas()
    {
        return $this->esAdministradorGeneral() || 
               $this->tienePermiso('tareas_crear');
    }

    public function puedeVerLogs()
    {
        return $this->esAdministradorGeneral() || 
               $this->tienePermiso('logs_leer');
    }

    public function puedeVerEstadisticas()
    {
        return $this->esAdministradorGeneral() || 
               $this->tienePermiso('estadisticas_ver');
    }

    public function actualizarUltimoAcceso()
    {
        $this->update(['ult_acc' => now()]);
    }

    public function verificarCorreo()
    {
        $this->update([
            'cor_ver' => true,
            'cor_ver_en' => now()
        ]);
    }

    public function activarCuenta()
    {
        $this->update(['est' => 'act']);
    }

    // Reglas de validación
    public static function reglas()
    {
        return [
            'nom' => 'required|string|max:100',
            'ape' => 'required|string|max:100',
            'cor' => 'required|email|unique:usu,cor',
            'con' => 'required|string|min:8',
            'con_confirmation' => 'required_with:con|same:con',
            'tel' => 'nullable|string|max:20',
            'doc' => 'required|string|max:20|unique:usu,doc',
            'tip_doc' => 'required|string|in:cc,ce,ti,pp,nit',
            'fec_nac' => 'nullable|date|before:today',
            'dir' => 'nullable|string|max:255',
            'ciu' => 'nullable|string|max:100',
            'dep' => 'nullable|string|max:100',
            'gen' => 'nullable|string|in:m,f,o,n',
            'rol' => 'required|string|in:cli,ope,adm',
            'est' => 'nullable|string|in:act,ina,sus,pen',
        ];
    }

    // Mensajes de validación
    public static function mensajes()
    {
        return [
            'nom.required' => 'El nombre es obligatorio',
            'nom.max' => 'El nombre no puede tener más de 100 caracteres',
            'ape.required' => 'El apellido es obligatorio',
            'ape.max' => 'El apellido no puede tener más de 100 caracteres',
            'cor.required' => 'El correo electrónico es obligatorio',
            'cor.email' => 'El correo electrónico debe tener un formato válido',
            'cor.unique' => 'Este correo electrónico ya está registrado',
            'con.required' => 'La contraseña es obligatoria',
            'con.min' => 'La contraseña debe tener al menos 8 caracteres',
            'con_confirmation.required_with' => 'La confirmación de contraseña es obligatoria',
            'con_confirmation.same' => 'Las contraseñas no coinciden',
            'doc.required' => 'El documento es obligatorio',
            'doc.unique' => 'Este documento ya está registrado',
            'tip_doc.required' => 'El tipo de documento es obligatorio',
            'tip_doc.in' => 'El tipo de documento no es válido',
            'rol.required' => 'El rol es obligatorio',
            'rol.in' => 'El rol no es válido',
        ];
    }

    /**
     * Obtener todos los permisos del usuario
     */
    public function obtenerPermisos()
    {
        if ($this->esAdministradorGeneral()) {
            // El administrador general tiene todos los permisos
            return App\Models\PermisoMejorado::activos()->get();
        }

        // Obtener permisos a través de los roles
        $permisos = collect();
        
        foreach ($this->roles as $rol) {
            $permisos = $permisos->merge($rol->permisos()->activos()->get());
        }

        return $permisos->unique('id');
    }

    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public function tienePermiso($permiso)
    {
        if ($this->esAdministradorGeneral()) {
            return true;
        }

        return $this->obtenerPermisos()->contains('slug', $permiso);
    }

    /**
     * Verificar si el usuario tiene un rol específico
     */
    public function tieneRol($rol)
    {
        return $this->rol === $rol || $this->esAdministradorGeneral();
    }
}