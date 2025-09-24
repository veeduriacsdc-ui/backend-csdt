<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'usuarios';
    
    protected $fillable = [
        'nom', 'ape', 'cor', 'con', 'tel', 'doc', 'tip_doc', 'fec_nac', 
        'dir', 'ciu', 'dep', 'gen', 'rol', 'est', 'cor_ver', 'cor_ver_en', 
        'ult_acc', 'not'
    ];

    protected $hidden = [
        'con',
        'remember_token',
    ];

    protected $casts = [
        'fec_nac' => 'date',
        'cor_ver_en' => 'datetime',
        'ult_acc' => 'datetime',
        'cor_ver' => 'boolean',
    ];

    // Relaciones
    public function veedurias()
    {
        return $this->hasMany(Veeduria::class, 'usu_id');
    }

    public function donaciones()
    {
        return $this->hasMany(Donacion::class, 'usu_id');
    }

    public function archivos()
    {
        return $this->hasMany(Archivo::class, 'usu_id');
    }

    public function tareasAsignadas()
    {
        return $this->hasMany(Tarea::class, 'asig_a');
    }

    public function tareasCreadas()
    {
        return $this->hasMany(Tarea::class, 'asig_por');
    }

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'usuarios_roles', 'usu_id', 'rol_id')
            ->withPivot('act', 'asig_en', 'asig_por', 'not')
            ->withTimestamps();
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

    public function scopeVerificados($query)
    {
        return $query->where('cor_ver', true);
    }

    // Métodos de negocio
    public function getNombreCompletoAttribute()
    {
        return $this->nom . ' ' . $this->ape;
    }

    public function getEstadoColorAttribute()
    {
        return match ($this->est) {
            'act' => 'success',
            'ina' => 'warning',
            'sus' => 'danger',
            'pen' => 'info',
            default => 'secondary'
        };
    }

    public function esAdministrador()
    {
        return $this->rol === 'adm';
    }

    public function esOperador()
    {
        return $this->rol === 'ope';
    }

    public function esCliente()
    {
        return $this->rol === 'cli';
    }

    public function actualizarUltimoAcceso()
    {
        $this->update(['ult_acc' => now()]);
    }

    public function verificarCorreo()
    {
        $this->update([
            'cor_ver' => true,
            'cor_ver_en' => now(),
        ]);
    }

    public function activarCuenta()
    {
        $this->update(['est' => 'act']);
    }

    public function suspenderCuenta()
    {
        $this->update(['est' => 'sus']);
    }

    public function cambiarEstado($nuevoEstado, $motivo = null)
    {
        $estadoAnterior = $this->est;
        $this->update(['est' => $nuevoEstado]);
        
        // Registrar el cambio
        $this->logs()->create([
            'acc' => 'cambiar_estado',
            'tab' => 'usuarios',
            'reg_id' => $this->id,
            'des' => "Estado cambiado de {$estadoAnterior} a {$nuevoEstado}",
            'dat_ant' => ['est' => $estadoAnterior],
            'dat_nue' => ['est' => $nuevoEstado],
            'ip' => request()->ip(),
            'age_usu' => request()->userAgent()
        ]);
    }

    // Mutators
    public function setConAttribute($value)
    {
        if ($value) {
            $this->attributes['con'] = Hash::make($value);
        }
    }

    public function setNomAttribute($value)
    {
        $this->attributes['nom'] = ucwords(strtolower($value));
    }

    public function setApeAttribute($value)
    {
        $this->attributes['ape'] = ucwords(strtolower($value));
    }

    public function setCorAttribute($value)
    {
        $this->attributes['cor'] = strtolower($value);
    }

    // Validaciones
    public static function reglas($id = null)
    {
        $uniqueEmail = 'unique:usuarios,cor';
        if ($id) {
            $uniqueEmail .= ','.$id.',id';
        }

        $uniqueDocumento = 'unique:usuarios,doc';
        if ($id) {
            $uniqueDocumento .= ','.$id.',id';
        }

        return [
            'nom' => 'required|string|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'ape' => 'required|string|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'cor' => 'required|email|max:150|'.$uniqueEmail,
            'con' => $id ? 'nullable|string|min:8|max:255' : 'required|string|min:8|max:255',
            'tel' => 'nullable|string|max:20|regex:/^[\+]?[0-9\-\s\(\)]{7,20}$/',
            'doc' => 'nullable|string|max:20|'.$uniqueDocumento,
            'tip_doc' => 'nullable|in:cc,ce,ti,pp,nit',
            'fec_nac' => 'nullable|date|before:today|after:1900-01-01',
            'dir' => 'nullable|string|max:200',
            'ciu' => 'nullable|string|max:100',
            'dep' => 'nullable|string|max:100',
            'gen' => 'nullable|in:m,f,o,n',
            'rol' => 'required|in:cli,ope,adm',
            'est' => 'sometimes|in:act,ina,sus,pen',
        ];
    }

    public static function mensajes()
    {
        return [
            'nom.required' => 'El nombre es obligatorio.',
            'nom.regex' => 'El nombre solo puede contener letras y espacios.',
            'ape.required' => 'Los apellidos son obligatorios.',
            'ape.regex' => 'Los apellidos solo pueden contener letras y espacios.',
            'cor.required' => 'El correo electrónico es obligatorio.',
            'cor.email' => 'El formato del correo electrónico no es válido.',
            'cor.unique' => 'Este correo electrónico ya está registrado.',
            'con.required' => 'La contraseña es obligatoria.',
            'con.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'tel.regex' => 'El formato del teléfono no es válido.',
            'doc.unique' => 'Este documento de identidad ya está registrado.',
            'fec_nac.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'fec_nac.after' => 'La fecha de nacimiento no puede ser anterior a 1900.',
            'rol.required' => 'El rol es obligatorio.',
            'rol.in' => 'El rol debe ser cliente, operador o administrador.',
        ];
    }
}
