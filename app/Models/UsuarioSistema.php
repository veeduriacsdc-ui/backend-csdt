<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;

class UsuarioSistema extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuariossistema';
    
    protected $primaryKey = 'IdUsuario';

    protected $fillable = [
        'IdUsuario', 'Nombre', 'Apellidos', 'Correo', 'Contrasena', 'Telefono',
        'DocumentoIdentidad', 'TipoDocumento', 'FechaNacimiento', 'Direccion',
        'Ciudad', 'Departamento', 'Genero', 'TipoUsuario', 'Estado',
        'AceptoTerminos', 'AceptoPoliticas', 'CorreoVerificado', 'CorreoVerificadoEn',
        'PerfilVerificado', 'AreasExpertise', 'remember_token'
    ];

    protected $hidden = [
        'Contrasena',
        'remember_token',
    ];

    protected $casts = [
        'FechaNacimiento' => 'date',
        'CorreoVerificadoEn' => 'datetime',
        'AceptoTerminos' => 'boolean',
        'AceptoPoliticas' => 'boolean',
        'CorreoVerificado' => 'boolean',
        'PerfilVerificado' => 'boolean',
        'AreasExpertise' => 'array',
    ];

    // Relaciones
    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'usuario_roles', 'usuario_id', 'rol_id')
            ->withPivot('asignado_en', 'asignado_por', 'revocado_en', 'revocado_por', 'motivo_revocacion')
            ->withTimestamps();
    }

    public function permisosEspeciales()
    {
        return $this->belongsToMany(Permiso::class, 'usuario_permisos_especiales_pivot', 'usuario_id', 'permiso_id')
            ->withPivot('activo', 'fecha_otorgamiento', 'fecha_expiracion', 'otorgado_por', 'motivo', 'restricciones')
            ->withTimestamps();
    }

    public function supervisados()
    {
        return $this->hasMany(UsuarioSistema::class, 'supervisor_id', 'id')
            ->wherePivot('activo', true);
    }

    public function supervisor()
    {
        return $this->belongsToMany(UsuarioSistema::class, 'supervision_usuarios_pivot', 'supervisado_id', 'supervisor_id')
            ->wherePivot('activo', true)
            ->withPivot('fecha_asignacion', 'fecha_fin', 'notas');
    }

    public function paginasHabilitadas()
    {
        return $this->hasMany(UsuarioPaginaHabilitada::class, 'usuario_id');
    }

    public function logsCambios()
    {
        return $this->hasMany(LogCambioUsuario::class, 'usuario_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_usuario', $tipo);
    }

    public function scopeClientes($query)
    {
        return $query->where('tipo_usuario', 'cliente');
    }

    public function scopeOperadores($query)
    {
        return $query->where('tipo_usuario', 'operador');
    }

    public function scopeAdministradores($query)
    {
        return $query->where('tipo_usuario', 'administrador');
    }

    public function scopePorNivelAdmin($query, $nivel)
    {
        return $query->where('tipo_usuario', 'administrador')
            ->where('nivel_administracion', $nivel);
    }

    public function scopeVerificados($query)
    {
        return $query->where('correo_verificado', true);
    }

    // Métodos de negocio
    public function getNombreCompletoAttribute()
    {
        return $this->nombres . ' ' . $this->apellidos;
    }

    public function getEstadoColorAttribute()
    {
        return match ($this->estado) {
            'activo' => 'success',
            'inactivo' => 'warning',
            'suspendido' => 'danger',
            'pendiente_verificacion' => 'info',
            'en_revision' => 'secondary',
            default => 'secondary'
        };
    }

    public function esAdministradorGeneral()
    {
        return $this->tipo_usuario === 'administrador' && $this->nivel_administracion === 4;
    }

    public function esAdministrador()
    {
        return $this->tipo_usuario === 'administrador';
    }

    public function esOperador()
    {
        return $this->tipo_usuario === 'operador';
    }

    public function esCliente()
    {
        return $this->tipo_usuario === 'cliente';
    }

    public function puedeGestionarUsuario($usuarioObjetivo)
    {
        if ($this->esAdministradorGeneral()) return true;
        
        $nivelActual = $this->nivel_administracion ?? 0;
        $nivelObjetivo = $usuarioObjetivo->nivel_administracion ?? 0;
        
        if ($this->esAdministrador() && $nivelActual > $nivelObjetivo) return true;
        if ($this->esOperador() && $usuarioObjetivo->esCliente()) return true;
        
        return false;
    }

    public function puedeCambiarRol($usuarioObjetivo, $nuevoRol)
    {
        if ($this->esAdministradorGeneral()) return true;
        
        if ($this->esAdministrador()) {
            $nivelActual = $this->nivel_administracion ?? 0;
            $nivelObjetivo = $usuarioObjetivo->nivel_administracion ?? 0;
            
            if ($nivelActual > $nivelObjetivo) return true;
        }
        
        return false;
    }

    public function obtenerRolesActivos()
    {
        return $this->roles()->wherePivot('activo', true)->get();
    }

    public function obtenerPermisosActivos()
    {
        $permisosRoles = $this->roles()
            ->wherePivot('activo', true)
            ->with('permisos')
            ->get()
            ->pluck('permisos')
            ->flatten();

        $permisosEspeciales = $this->permisosEspeciales()
            ->wherePivot('activo', true)
            ->get();

        return $permisosRoles->merge($permisosEspeciales)->unique('id');
    }

    public function tienePermiso($permiso)
    {
        $permisos = $this->obtenerPermisosActivos();
        return $permisos->contains('slug', $permiso);
    }

    public function asignarRol($rolId, $asignadoPor = null, $notas = null, $fechaExpiracion = null)
    {
        return $this->roles()->syncWithoutDetaching([
            $rolId => [
                'activo' => true,
                'fecha_asignacion' => now(),
                'fecha_expiracion' => $fechaExpiracion,
                'asignado_por' => $asignadoPor,
                'notas' => $notas
            ]
        ]);
    }

    public function revocarRol($rolId)
    {
        return $this->roles()->updateExistingPivot($rolId, [
            'activo' => false
        ]);
    }

    public function otorgarPermisoEspecial($permisoId, $otorgadoPor = null, $motivo = null, $restricciones = null, $fechaExpiracion = null)
    {
        return $this->permisosEspeciales()->syncWithoutDetaching([
            $permisoId => [
                'activo' => true,
                'fecha_otorgamiento' => now(),
                'fecha_expiracion' => $fechaExpiracion,
                'otorgado_por' => $otorgadoPor,
                'motivo' => $motivo,
                'restricciones' => $restricciones ? json_encode($restricciones) : null
            ]
        ]);
    }

    public function revocarPermisoEspecial($permisoId)
    {
        return $this->permisosEspeciales()->updateExistingPivot($permisoId, [
            'activo' => false
        ]);
    }

    public function actualizarUltimoAcceso()
    {
        $this->update(['ultimo_acceso' => now()]);
    }

    public function verificarCorreo()
    {
        $this->update([
            'correo_verificado' => true,
            'correo_verificado_en' => now(),
        ]);
    }

    public function verificarPerfil()
    {
        $this->update([
            'perfil_verificado' => true,
            'perfil_verificado_en' => now(),
        ]);
    }

    public function activarCuenta()
    {
        $this->update(['estado' => 'activo']);
    }

    public function suspenderCuenta()
    {
        $this->update(['estado' => 'suspendido']);
    }

    public function cambiarEstado($nuevoEstado, $motivo = null)
    {
        $estadoAnterior = $this->estado;
        $this->update(['estado' => $nuevoEstado]);
        
        // Registrar el cambio
        $this->logsCambios()->create([
            'tipo_cambio' => 'estado',
            'campo_anterior' => 'estado',
            'valor_anterior' => $estadoAnterior,
            'campo_nuevo' => 'estado',
            'valor_nuevo' => $nuevoEstado,
            'cambiado_por' => auth()->id(),
            'motivo' => $motivo
        ]);
    }

    // Mutators
    public function setContrasenaAttribute($value)
    {
        if ($value) {
            $this->attributes['contrasena'] = Hash::make($value);
        }
    }

    public function setNombresAttribute($value)
    {
        $this->attributes['nombres'] = ucwords(strtolower($value));
    }

    public function setApellidosAttribute($value)
    {
        $this->attributes['apellidos'] = ucwords(strtolower($value));
    }

    public function setCorreoAttribute($value)
    {
        $this->attributes['correo'] = strtolower($value);
    }

    // Validaciones
    public static function rules($id = null)
    {
        $uniqueEmail = 'unique:usuarios_sistema,correo';
        if ($id) {
            $uniqueEmail .= ','.$id.',id';
        }

        $uniqueDocumento = 'unique:usuarios_sistema,documento_identidad';
        if ($id) {
            $uniqueDocumento .= ','.$id.',id';
        }

        return [
            'nombres' => 'required|string|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'apellidos' => 'required|string|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'correo' => 'required|email|max:150|'.$uniqueEmail,
            'contrasena' => $id ? 'nullable|string|min:8|max:255' : 'required|string|min:8|max:255',
            'telefono' => 'nullable|string|max:20|regex:/^[\+]?[0-9\-\s\(\)]{7,20}$/',
            'documento_identidad' => 'nullable|string|max:20|'.$uniqueDocumento,
            'tipo_documento' => 'nullable|in:cc,ce,ti,pp,nit',
            'fecha_nacimiento' => 'nullable|date|before:today|after:1900-01-01',
            'direccion' => 'nullable|string|max:200',
            'ciudad' => 'nullable|string|max:100',
            'departamento' => 'nullable|string|max:100',
            'codigo_postal' => 'nullable|string|max:10',
            'genero' => 'nullable|in:masculino,femenino,otro,no_especificado',
            'profesion' => 'nullable|string|max:100',
            'especializacion' => 'nullable|string|max:150',
            'numero_matricula' => 'nullable|string|max:50',
            'entidad_matricula' => 'nullable|string|max:100',
            'anos_experiencia' => 'nullable|integer|min:0|max:50',
            'perfil_profesional' => 'nullable|string',
            'areas_expertise' => 'nullable|array',
            'linkedin' => 'nullable|url|max:200',
            'sitio_web' => 'nullable|url|max:200',
            'acepto_terminos' => 'required|boolean',
            'acepto_politicas' => 'required|boolean',
            'tipo_usuario' => 'required|in:cliente,operador,administrador',
            'nivel_administracion' => 'nullable|integer|min:1|max:4',
            'estado' => 'sometimes|in:activo,inactivo,suspendido,pendiente_verificacion,en_revision',
        ];
    }
}
