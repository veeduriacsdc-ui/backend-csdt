<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Administrador extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'operadores'; // Usa la misma tabla que operadores

    protected $fillable = [
        'nombres', 'apellidos', 'correo', 'contrasena', 'telefono',
        'documento_identidad', 'tipo_documento', 'fecha_nacimiento', 'direccion',
        'ciudad', 'departamento', 'codigo_postal', 'genero', 'profesion',
        'especializacion', 'numero_matricula', 'entidad_matricula', 'anos_experiencia',
        'perfil_profesional', 'areas_expertise', 'linkedin', 'sitio_web',
        'acepto_terminos', 'acepto_politicas', 'correo_verificado', 'correo_verificado_en',
        'perfil_verificado', 'perfil_verificado_en', 'ultimo_acceso', 'estado', 'notas', 'rol',
        'nivel_administracion',
    ];

    protected $hidden = [
        'contrasena',
    ];

    protected $casts = [
        'correo_verificado_en' => 'datetime',
        'perfil_verificado_en' => 'datetime',
        'ultimo_acceso' => 'datetime',
        'fecha_nacimiento' => 'date',
        'acepto_terminos' => 'boolean',
        'acepto_politicas' => 'boolean',
        'correo_verificado' => 'boolean',
        'perfil_verificado' => 'boolean',
        'areas_expertise' => 'array',
    ];

    // Scope para filtrar solo administradores
    public function scopeAdministradores($query)
    {
        return $query->where('rol', 'administrador');
    }

    // Scope para filtrar por nivel de administración
    public function scopePorNivelAdmin($query, $nivel)
    {
        return $query->where('rol', 'administrador')
            ->where('nivel_administracion', $nivel);
    }

    // Relaciones específicas para administradores
    public function operadoresSupervisados()
    {
        return $this->hasMany(Operador::class, 'administrador_supervisor_id', 'id');
    }

    public function casosAsignados()
    {
        return $this->hasMany(PQRSFD::class, 'administrador_asignado_id', 'id');
    }

    public function actividadesAdministrativas()
    {
        return $this->hasMany(ActividadCaso::class, 'administrador_responsable_id', 'id');
    }

    public function reportesGenerados()
    {
        if (class_exists('\App\Models\ReporteSistema')) {
            return $this->hasMany(\App\Models\ReporteSistema::class, 'administrador_generador_id', 'id');
        }
        return $this->hasMany(\App\Models\Reporte::class, 'administrador_generador_id', 'id');
    }

    public function logsSistema()
    {
        if (class_exists('\App\Models\LogSistema')) {
            return $this->hasMany(\App\Models\LogSistema::class, 'administrador_responsable_id', 'id');
        }
        return $this->hasMany(\App\Models\LogUsuario::class, 'administrador_responsable_id', 'id');
    }

    // Métodos específicos para administradores
    public function esAdministrador()
    {
        return $this->rol === 'administrador';
    }

    public function esSuperAdministrador()
    {
        return $this->rol === 'administrador' && $this->nivel_administracion === 'super';
    }

    public function puedeGestionarOperadores()
    {
        return in_array($this->nivel_administracion, ['super', 'gestion']);
    }

    public function puedeGestionarSistema()
    {
        return $this->nivel_administracion === 'super';
    }

    public function actualizarUltimoAcceso()
    {
        $this->update(['ultimo_acceso' => now()]);
    }

    public function verificarPerfil()
    {
        $this->update([
            'perfil_verificado' => true,
            'perfil_verificado_en' => now(),
        ]);
    }

    // Validaciones específicas para administradores
    public static function rules($id = null)
    {
        $uniqueEmail = 'unique:operadores,correo';
        if ($id) {
            $uniqueEmail .= ','.$id.',id';
        }

        return [
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'correo' => 'required|email|'.$uniqueEmail,
            'contrasena' => $id ? 'nullable|min:8' : 'required|min:8',
            'telefono' => 'nullable|string|max:20',
            'documento_identidad' => 'nullable|string|max:20',
            'tipo_documento' => 'nullable|in:cc,ce,ti,pp',
            'fecha_nacimiento' => 'nullable|date|before:today',
            'direccion' => 'nullable|string|max:500',
            'ciudad' => 'nullable|string|max:100',
            'departamento' => 'nullable|string|max:100',
            'genero' => 'nullable|in:masculino,femenino,otro',
            'profesion' => 'required|string|max:255',
            'especializacion' => 'nullable|string|max:255',
            'numero_matricula' => 'nullable|string|max:100',
            'entidad_matricula' => 'nullable|string|max:255',
            'anos_experiencia' => 'nullable|integer|min:0',
            'perfil_profesional' => 'nullable|string',
            'areas_expertise' => 'nullable|array',
            'linkedin' => 'nullable|url',
            'sitio_web' => 'nullable|url',
            'acepto_terminos' => 'required|boolean',
            'acepto_politicas' => 'required|boolean',
            'rol' => 'required|in:administrador',
            'nivel_administracion' => 'nullable|in:super,gestion,operativo',
        ];
    }

    // Métodos de negocio específicos para administradores
    public function asignarOperador($idOperador)
    {
        if (! $this->puedeGestionarOperadores()) {
            throw new \Exception('No tiene permisos para gestionar operadores');
        }

        $operador = Operador::find($idOperador);
        if ($operador) {
            $operador->update(['administrador_supervisor_id' => $this->id]);

            return true;
        }

        return false;
    }

    public function generarReporteSistema($tipo, $datos)
    {
        if (class_exists('\App\Models\ReporteSistema')) {
            return \App\Models\ReporteSistema::create([
                'tipo_reporte' => $tipo,
                'datos_reporte' => json_encode($datos),
                'administrador_generador_id' => $this->id,
                'fecha_generacion' => now(),
                'estado' => 'generado',
            ]);
        }

        return \App\Models\Reporte::create([
            'tipo_reporte' => $tipo,
            'datos_reporte' => json_encode($datos),
            'administrador_generador_id' => $this->id,
            'fecha_generacion' => now(),
            'estado' => 'generado',
        ]);
    }

    public function registrarLogSistema($accion, $detalles)
    {
        if (class_exists('\App\Models\LogSistema')) {
            return \App\Models\LogSistema::create([
                'accion' => $accion,
                'detalles' => $detalles,
                'administrador_responsable_id' => $this->id,
                'fecha_accion' => now(),
                'ip_acceso' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        return \App\Models\LogUsuario::create([
            'accion' => $accion,
            'detalles' => $detalles,
            'administrador_responsable_id' => $this->id,
            'fecha_accion' => now(),
            'ip_acceso' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
