<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Administrador extends Authenticatable
{
    use HasFactory, SoftDeletes, HasApiTokens, Notifiable;

    protected $table = 'Operadores'; // Usa la misma tabla que Operadores
    protected $primaryKey = 'IdOperador';

    protected $fillable = [
        'Nombres', 'Apellidos', 'Correo', 'Contrasena', 'Telefono',
        'DocumentoIdentidad', 'TipoDocumento', 'FechaNacimiento', 'Direccion',
        'Ciudad', 'Departamento', 'CodigoPostal', 'Genero', 'Profesion',
        'Especializacion', 'NumeroMatricula', 'EntidadMatricula', 'AnosExperiencia',
        'PerfilProfesional', 'AreasExpertise', 'Linkedin', 'SitioWeb',
        'AceptoTerminos', 'AceptoPoliticas', 'CorreoVerificado', 'CorreoVerificadoEn',
        'PerfilVerificado', 'PerfilVerificadoEn', 'UltimoAcceso', 'Estado', 'Notas', 'Rol'
    ];

    protected $hidden = [
        'Contrasena',
    ];

    protected $casts = [
        'CorreoVerificadoEn' => 'datetime',
        'PerfilVerificadoEn' => 'datetime',
        'UltimoAcceso' => 'datetime',
        'FechaNacimiento' => 'date',
        'AceptoTerminos' => 'boolean',
        'AceptoPoliticas' => 'boolean',
        'CorreoVerificado' => 'boolean',
        'PerfilVerificado' => 'boolean',
        'AreasExpertise' => 'array',
    ];

    // Scope para filtrar solo administradores
    public function scopeAdministradores($query)
    {
        return $query->where('Rol', 'Administrador');
    }

    // Scope para filtrar por nivel de administración
    public function scopePorNivelAdmin($query, $nivel)
    {
        return $query->where('Rol', 'Administrador')
                    ->where('NivelAdministracion', $nivel);
    }

    // Relaciones específicas para administradores
    public function operadoresSupervisados()
    {
        return $this->hasMany(Operador::class, 'IdAdministradorSupervisor', 'IdOperador');
    }

    public function casosAsignados()
    {
        return $this->hasMany(PQRSFD::class, 'IdAdministradorAsignado', 'IdOperador');
    }

    public function actividadesAdministrativas()
    {
        return $this->hasMany(ActividadCaso::class, 'IdAdministradorResponsable', 'IdOperador');
    }

    public function reportesGenerados()
    {
        return $this->hasMany(\App\Models\ReporteSistema::class, 'IdAdministradorGenerador', 'IdOperador');
    }

    public function logsSistema()
    {
        return $this->hasMany(\App\Models\LogSistema::class, 'IdAdministradorResponsable', 'IdOperador');
    }

    // Métodos específicos para administradores
    public function esAdministrador()
    {
        return $this->Rol === 'Administrador';
    }

    public function esSuperAdministrador()
    {
        return $this->Rol === 'Administrador' && $this->NivelAdministracion === 'Super';
    }

    public function puedeGestionarOperadores()
    {
        return in_array($this->NivelAdministracion, ['Super', 'Gestion']);
    }

    public function puedeGestionarSistema()
    {
        return $this->NivelAdministracion === 'Super';
    }

    public function actualizarUltimoAcceso()
    {
        $this->update(['UltimoAcceso' => now()]);
    }

    public function verificarPerfil()
    {
        $this->update([
            'PerfilVerificado' => true,
            'PerfilVerificadoEn' => now()
        ]);
    }

    // Validaciones específicas para administradores
    public static function rules($id = null)
    {
        $uniqueEmail = 'unique:Operadores,Correo';
        if ($id) {
            $uniqueEmail .= ',' . $id . ',IdOperador';
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
            'Profesion' => 'required|string|max:255',
            'Especializacion' => 'nullable|string|max:255',
            'NumeroMatricula' => 'nullable|string|max:100',
            'EntidadMatricula' => 'nullable|string|max:255',
            'AnosExperiencia' => 'nullable|integer|min:0',
            'PerfilProfesional' => 'nullable|text',
            'AreasExpertise' => 'nullable|array',
            'Linkedin' => 'nullable|url',
            'SitioWeb' => 'nullable|url',
            'AceptoTerminos' => 'required|boolean',
            'AceptoPoliticas' => 'required|boolean',
            'Rol' => 'required|in:Administrador',
            'NivelAdministracion' => 'nullable|in:Super,Gestion,Operativo'
        ];
    }

    // Métodos de negocio específicos para administradores
    public function asignarOperador($idOperador)
    {
        if (!$this->puedeGestionarOperadores()) {
            throw new \Exception('No tiene permisos para gestionar operadores');
        }

        $operador = Operador::find($idOperador);
        if ($operador) {
            $operador->update(['IdAdministradorSupervisor' => $this->IdOperador]);
            return true;
        }
        return false;
    }

    public function generarReporteSistema($tipo, $datos)
    {
        return \App\Models\ReporteSistema::create([
            'TipoReporte' => $tipo,
            'DatosReporte' => json_encode($datos),
            'IdAdministradorGenerador' => $this->IdOperador,
            'FechaGeneracion' => now(),
            'Estado' => 'Generado'
        ]);
    }

    public function registrarLogSistema($accion, $detalles)
    {
        return \App\Models\LogSistema::create([
            'Accion' => $accion,
            'Detalles' => $detalles,
            'IdAdministradorResponsable' => $this->IdOperador,
            'FechaAccion' => now(),
            'IpAcceso' => request()->ip(),
            'UserAgent' => request()->userAgent()
        ]);
    }
}
