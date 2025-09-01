<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Cliente extends Authenticatable
{
    use HasFactory, SoftDeletes, HasApiTokens, Notifiable;

    protected $table = 'Clientes';
    protected $primaryKey = 'IdCliente';

    protected $fillable = [
        'Nombres', 'Apellidos', 'Correo', 'Contrasena', 'Telefono',
        'DocumentoIdentidad', 'TipoDocumento', 'FechaNacimiento', 'Direccion',
        'Ciudad', 'Departamento', 'CodigoPostal', 'Genero', 'AceptoTerminos',
        'AceptoPoliticas', 'CorreoVerificado', 'CorreoVerificadoEn',
        'UltimoAcceso', 'Estado', 'Notas'
    ];

    protected $hidden = [
        'Contrasena',
        'remember_token',
    ];

    protected $casts = [
        'CorreoVerificadoEn' => 'datetime',
        'UltimoAcceso' => 'datetime',
        'FechaNacimiento' => 'date',
        'AceptoTerminos' => 'boolean',
        'AceptoPoliticas' => 'boolean',
        'CorreoVerificado' => 'boolean',
    ];

    // Relaciones
    public function pqrsfds()
    {
        return $this->hasMany(PQRSFD::class, 'IdCliente', 'IdCliente');
    }

    public function donaciones()
    {
        return $this->hasMany(Donacion::class, 'IdCliente', 'IdCliente');
    }

    public function documentos()
    {
        return $this->hasMany(DocumentoAdjunto::class, 'IdCliente', 'IdCliente');
    }

    // Métodos de negocio
    public function getNombreCompletoAttribute()
    {
        return $this->Nombres . ' ' . $this->Apellidos;
    }

    public function getEstadoColorAttribute()
    {
        return $this->Estado === 'Activo' ? 'success' : 'danger';
    }

    public function actualizarUltimoAcceso()
    {
        $this->update(['UltimoAcceso' => now()]);
    }

    public function verificarCorreo()
    {
        $this->update([
            'CorreoVerificado' => true,
            'CorreoVerificadoEn' => now()
        ]);
    }

    // Scopes para consultas
    public function scopeActivos($query)
    {
        return $query->where('Estado', 'Activo');
    }

    public function scopePorCiudad($query, $ciudad)
    {
        return $query->where('Ciudad', $ciudad);
    }

    public function scopePorDepartamento($query, $departamento)
    {
        return $query->where('Departamento', $departamento);
    }

    // Validaciones personalizadas
    public static function rules($id = null)
    {
        $uniqueEmail = 'unique:Clientes,Correo';
        if ($id) {
            $uniqueEmail .= ',' . $id . ',IdCliente';
        }

        return [
            'Nombres' => 'required|string|max:255',
            'Apellidos' => 'required|string|max:255',
            'Correo' => 'required|email|' . $uniqueEmail,
            'Contrasena' => $id ? 'nullable|min:8' : 'required|min:8',
            'Telefono' => 'nullable|string|max:20',
            'DocumentoIdentidad' => 'nullable|string|max:20',
            'TipoDocumento' => 'nullable|in:CC,CE,TI,PP',
            'FechaNacimiento' => 'nullable|date|before:today',
            'Direccion' => 'nullable|string|max:500',
            'Ciudad' => 'nullable|string|max:100',
            'Departamento' => 'nullable|string|max:100',
            'Genero' => 'nullable|in:Masculino,Femenino,Otro',
            'AceptoTerminos' => 'required|boolean',
            'AceptoPoliticas' => 'required|boolean',
        ];
    }
}
