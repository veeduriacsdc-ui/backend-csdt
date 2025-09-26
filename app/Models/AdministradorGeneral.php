<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class AdministradorGeneral extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'usu';
    protected $primaryKey = 'id';

    protected $fillable = [
        'nom', 'ape', 'cor', 'con', 'tel', 'doc', 'tip_doc',
        'fec_nac', 'dir', 'ciu', 'dep', 'gen', 'rol', 'est',
        'cor_ver', 'cor_ver_en', 'ult_acc', 'not'
    ];

    protected $hidden = [
        'con', 'remember_token',
    ];

    protected $casts = [
        'cor_ver' => 'boolean',
        'cor_ver_en' => 'datetime',
        'ult_acc' => 'datetime',
        'fec_nac' => 'date',
    ];

    // Constantes para roles
    const ROL_ADMINISTRADOR_GENERAL = 'adm_gen';
    const ROL_ADMINISTRADOR = 'adm';
    const ROL_OPERADOR = 'ope';
    const ROL_CLIENTE = 'cli';

    // Constantes para estados
    const ESTADO_ACTIVO = 'act';
    const ESTADO_INACTIVO = 'ina';
    const ESTADO_SUSPENDIDO = 'sus';
    const ESTADO_PENDIENTE = 'pen';

    // Relaciones
    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'usu_rol', 'usu_id', 'rol_id')
                    ->withPivot(['act', 'asig_por', 'asig_en', 'not'])
                    ->withTimestamps();
    }

    public function permisos()
    {
        return $this->hasManyThrough(
            Permiso::class,
            UsuRol::class,
            'usu_id',
            'id',
            'id',
            'rol_id'
        );
    }

    public function usuariosCreados()
    {
        return $this->hasMany(Usuario::class, 'creado_por');
    }

    public function rolesCreados()
    {
        return $this->hasMany(Rol::class, 'creado_por');
    }

    public function logs()
    {
        return $this->hasMany(Log::class, 'usu_id');
    }

    // Scopes
    public function scopeAdministradoresGenerales($query)
    {
        return $query->where('rol', self::ROL_ADMINISTRADOR_GENERAL);
    }

    public function scopeAdministradores($query)
    {
        return $query->where('rol', self::ROL_ADMINISTRADOR);
    }

    public function scopeOperadores($query)
    {
        return $query->where('rol', self::ROL_OPERADOR);
    }

    public function scopeClientes($query)
    {
        return $query->where('rol', self::ROL_CLIENTE);
    }

    public function scopeActivos($query)
    {
        return $query->where('est', self::ESTADO_ACTIVO);
    }

    // Métodos de verificación de permisos
    public function esAdministradorGeneral()
    {
        return $this->rol === self::ROL_ADMINISTRADOR_GENERAL;
    }

    public function esAdministrador()
    {
        return $this->rol === self::ROL_ADMINISTRADOR;
    }

    public function esOperador()
    {
        return $this->rol === self::ROL_OPERADOR;
    }

    public function esCliente()
    {
        return $this->rol === self::ROL_CLIENTE;
    }

    public function tienePermiso($permiso)
    {
        // El administrador general tiene todos los permisos
        if ($this->esAdministradorGeneral()) {
            return true;
        }

        // Verificar permisos a través de roles
        return $this->roles()->whereHas('permisos', function($query) use ($permiso) {
            $query->where('nom', $permiso);
        })->exists();
    }

    public function puedeGestionarUsuarios()
    {
        return $this->esAdministradorGeneral() || 
               $this->tienePermiso('gestionar_usuarios');
    }

    public function puedeGestionarRoles()
    {
        return $this->esAdministradorGeneral() || 
               $this->tienePermiso('gestionar_roles');
    }

    public function puedeGestionarPermisos()
    {
        return $this->esAdministradorGeneral() || 
               $this->tienePermiso('gestionar_permisos');
    }

    public function puedeGestionarVeedurias()
    {
        return $this->esAdministradorGeneral() || 
               $this->tienePermiso('gestionar_veedurias');
    }

    public function puedeGestionarDonaciones()
    {
        return $this->esAdministradorGeneral() || 
               $this->tienePermiso('gestionar_donaciones');
    }

    public function puedeGestionarTareas()
    {
        return $this->esAdministradorGeneral() || 
               $this->tienePermiso('gestionar_tareas');
    }

    public function puedeVerLogs()
    {
        return $this->esAdministradorGeneral() || 
               $this->tienePermiso('ver_logs');
    }

    public function puedeVerEstadisticas()
    {
        return $this->esAdministradorGeneral() || 
               $this->tienePermiso('ver_estadisticas');
    }

    // Métodos de gestión de usuarios
    public function crearUsuario($datos)
    {
        if (!$this->puedeGestionarUsuarios()) {
            throw new \Exception('No tiene permisos para crear usuarios');
        }

        $usuario = Usuario::create(array_merge($datos, [
            'creado_por' => $this->id
        ]));

        // Log de creación
        Log::crear('crear_usuario', 'usuarios', $usuario->id, 
                  "Usuario creado por {$this->nombre_completo}");

        return $usuario;
    }

    public function asignarRol($usuarioId, $rolId)
    {
        if (!$this->puedeGestionarRoles()) {
            throw new \Exception('No tiene permisos para asignar roles');
        }

        $usuario = Usuario::findOrFail($usuarioId);
        $rol = Rol::findOrFail($rolId);

        $usuario->roles()->attach($rolId, [
            'asig_por' => $this->id,
            'asig_en' => now(),
            'act' => true
        ]);

        // Log de asignación
        Log::crear('asignar_rol', 'usuarios', $usuarioId, 
                  "Rol '{$rol->nom}' asignado por {$this->nombre_completo}");

        return true;
    }

    public function quitarRol($usuarioId, $rolId)
    {
        if (!$this->puedeGestionarRoles()) {
            throw new \Exception('No tiene permisos para quitar roles');
        }

        $usuario = Usuario::findOrFail($usuarioId);
        $rol = Rol::findOrFail($rolId);

        $usuario->roles()->detach($rolId);

        // Log de quitar rol
        Log::crear('quitar_rol', 'usuarios', $usuarioId, 
                  "Rol '{$rol->nom}' quitado por {$this->nombre_completo}");

        return true;
    }

    public function cambiarEstadoUsuario($usuarioId, $nuevoEstado)
    {
        if (!$this->puedeGestionarUsuarios()) {
            throw new \Exception('No tiene permisos para cambiar estado de usuarios');
        }

        $usuario = Usuario::findOrFail($usuarioId);
        $estadoAnterior = $usuario->est;
        
        $usuario->update(['est' => $nuevoEstado]);

        // Log de cambio de estado
        Log::crear('cambiar_estado_usuario', 'usuarios', $usuarioId, 
                  "Estado cambiado de '{$estadoAnterior}' a '{$nuevoEstado}' por {$this->nombre_completo}");

        return true;
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
        $this->attributes['con'] = \Hash::make($value);
    }

    // Métodos de utilidad
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
        $this->update(['est' => self::ESTADO_ACTIVO]);
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
            'dir' => 'nullable|string|max:200',
            'ciu' => 'nullable|string|max:100',
            'dep' => 'nullable|string|max:100',
            'gen' => 'nullable|string|in:m,f,o,n',
            'rol' => 'required|string|in:adm_gen,adm,ope,cli',
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
}
