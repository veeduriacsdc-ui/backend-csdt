<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Cliente extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'nombres', 'apellidos', 'usuario', 'correo', 'contrasena', 'telefono',
        'documento_identidad', 'tipo_documento', 'fecha_nacimiento', 'direccion',
        'ciudad', 'departamento', 'codigo_postal', 'genero', 'acepto_terminos',
        'acepto_politicas', 'correo_verificado', 'correo_verificado_en',
        'ultimo_acceso', 'estado', 'notas',
    ];

    protected $hidden = [
        'contrasena',
        'remember_token',
    ];

    protected $casts = [
        'correo_verificado_en' => 'datetime',
        'ultimo_acceso' => 'datetime',
        'fecha_nacimiento' => 'date',
        'acepto_terminos' => 'boolean',
        'acepto_politicas' => 'boolean',
        'correo_verificado' => 'boolean',
    ];

    // Relaciones
    public function pqrsfds()
    {
        return $this->hasMany(PQRSFD::class, 'cliente_id');
    }

    public function donaciones()
    {
        return $this->hasMany(Donacion::class, 'cliente_id');
    }

    public function documentos()
    {
        return $this->hasMany(DocumentoAdjunto::class, 'cliente_id');
    }

    public function consultas_previas()
    {
        return $this->hasMany(ConsultaPrevia::class, 'cliente_id');
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
            default => 'secondary'
        };
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

    public function activarCuenta()
    {
        $this->update(['estado' => 'activo']);
    }

    public function suspenderCuenta()
    {
        $this->update(['estado' => 'suspendido']);
    }

    // Scopes para consultas
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopeInactivos($query)
    {
        return $query->where('estado', 'inactivo');
    }

    public function scopeSuspendidos($query)
    {
        return $query->where('estado', 'suspendido');
    }

    public function scopePorCiudad($query, $ciudad)
    {
        return $query->where('ciudad', $ciudad);
    }

    public function scopePorDepartamento($query, $departamento)
    {
        return $query->where('departamento', $departamento);
    }

    public function scopeVerificados($query)
    {
        return $query->where('correo_verificado', true);
    }

    public function scopeMayoresDeEdad($query)
    {
        return $query->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) >= 18');
    }

    // Validaciones personalizadas
    public static function rules($id = null)
    {
        $uniqueEmail = 'unique:clientes,correo';
        if ($id) {
            $uniqueEmail .= ',' . $id;
        }

        $uniqueDocumento = 'unique:clientes,documento_identidad';
        if ($id) {
            $uniqueDocumento .= ',' . $id;
        }

        return [
            'nombres' => 'required|string|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'apellidos' => 'required|string|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'correo' => 'required|email|max:150|' . $uniqueEmail,
            'contrasena' => $id ? 'nullable|string|min:8|max:255' : 'required|string|min:8|max:255',
            'telefono' => 'nullable|string|max:20|regex:/^[\+]?[0-9\-\s\(\)]{7,20}$/',
            'documento_identidad' => 'nullable|string|max:20|' . $uniqueDocumento,
            'tipo_documento' => 'nullable|in:cc,ce,ti,pp,nit',
            'fecha_nacimiento' => 'nullable|date|before:today|after:1900-01-01',
            'direccion' => 'nullable|string|max:200',
            'ciudad' => 'nullable|string|max:100',
            'departamento' => 'nullable|string|max:100',
            'codigo_postal' => 'nullable|string|max:10',
            'genero' => 'nullable|in:masculino,femenino,otro,no_especificado',
            'acepto_terminos' => 'required|boolean',
            'acepto_politicas' => 'required|boolean',
            'estado' => 'sometimes|in:activo,inactivo,suspendido,pendiente_verificacion',
        ];
    }

    public static function messages()
    {
        return [
            'nombres.required' => 'Los nombres son obligatorios.',
            'nombres.regex' => 'Los nombres solo pueden contener letras y espacios.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras y espacios.',
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email' => 'El formato del correo electrónico no es válido.',
            'correo.unique' => 'Este correo electrónico ya está registrado.',
            'correo.max' => 'El correo electrónico no puede exceder 150 caracteres.',
            'contrasena.required' => 'La contraseña es obligatoria.',
            'contrasena.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'telefono.regex' => 'El formato del teléfono no es válido.',
            'documento_identidad.unique' => 'Este documento de identidad ya está registrado.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'fecha_nacimiento.after' => 'La fecha de nacimiento no puede ser anterior a 1900.',
            'acepto_terminos.required' => 'Debe aceptar los términos y condiciones.',
            'acepto_politicas.required' => 'Debe aceptar las políticas de privacidad.',
        ];
    }
}
