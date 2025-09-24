<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $table = 'logs';
    
    protected $fillable = [
        'usu_id', 'acc', 'tab', 'reg_id', 'des', 'dat_ant', 'dat_nue', 
        'ip', 'age_usu', 'fec'
    ];

    protected $casts = [
        'dat_ant' => 'array',
        'dat_nue' => 'array',
        'fec' => 'datetime',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usu_id');
    }

    // Scopes
    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usu_id', $usuarioId);
    }

    public function scopePorAccion($query, $accion)
    {
        return $query->where('acc', $accion);
    }

    public function scopePorTabla($query, $tabla)
    {
        return $query->where('tab', $tabla);
    }

    public function scopePorRegistro($query, $tabla, $registroId)
    {
        return $query->where('tab', $tabla)->where('reg_id', $registroId);
    }

    public function scopePorFecha($query, $fechaInicio, $fechaFin = null)
    {
        if ($fechaFin) {
            return $query->whereBetween('fec', [$fechaInicio, $fechaFin]);
        }
        return $query->whereDate('fec', $fechaInicio);
    }

    public function scopeRecientes($query, $dias = 7)
    {
        return $query->where('fec', '>=', now()->subDays($dias));
    }

    public function scopePorIP($query, $ip)
    {
        return $query->where('ip', $ip);
    }

    // Métodos de negocio
    public function getAccionTextoAttribute()
    {
        return match ($this->acc) {
            'crear' => 'Crear',
            'actualizar' => 'Actualizar',
            'eliminar' => 'Eliminar',
            'restaurar' => 'Restaurar',
            'cambiar_estado' => 'Cambiar Estado',
            'asignar' => 'Asignar',
            'desasignar' => 'Desasignar',
            'iniciar' => 'Iniciar',
            'completar' => 'Completar',
            'cancelar' => 'Cancelar',
            'suspender' => 'Suspendir',
            'reanudar' => 'Reanudar',
            'radicar' => 'Radicar',
            'cerrar' => 'Cerrar',
            'confirmar' => 'Confirmar',
            'rechazar' => 'Rechazar',
            'verificar' => 'Verificar',
            'activar' => 'Activar',
            'desactivar' => 'Desactivar',
            'login' => 'Iniciar Sesión',
            'logout' => 'Cerrar Sesión',
            'cambiar_contrasena' => 'Cambiar Contraseña',
            'recuperar_contrasena' => 'Recuperar Contraseña',
            'verificar_correo' => 'Verificar Correo',
            'subir_archivo' => 'Subir Archivo',
            'descargar_archivo' => 'Descargar Archivo',
            'eliminar_archivo' => 'Eliminar Archivo',
            default => ucfirst(str_replace('_', ' ', $this->acc))
        };
    }

    public function getTipoColorAttribute()
    {
        return match ($this->acc) {
            'crear' => 'success',
            'actualizar' => 'info',
            'eliminar' => 'danger',
            'restaurar' => 'warning',
            'cambiar_estado' => 'primary',
            'asignar' => 'success',
            'desasignar' => 'warning',
            'iniciar' => 'info',
            'completar' => 'success',
            'cancelar' => 'danger',
            'suspender' => 'warning',
            'reanudar' => 'info',
            'radicar' => 'primary',
            'cerrar' => 'success',
            'confirmar' => 'success',
            'rechazar' => 'danger',
            'verificar' => 'info',
            'activar' => 'success',
            'desactivar' => 'danger',
            'login' => 'success',
            'logout' => 'info',
            'cambiar_contrasena' => 'warning',
            'recuperar_contrasena' => 'info',
            'verificar_correo' => 'info',
            'subir_archivo' => 'success',
            'descargar_archivo' => 'info',
            'eliminar_archivo' => 'danger',
            default => 'secondary'
        };
    }

    // Métodos estáticos para crear logs
    public static function crear($accion, $tabla = null, $registroId = null, $descripcion = null, $datosAnteriores = null, $datosNuevos = null, $usuarioId = null)
    {
        return static::create([
            'usu_id' => $usuarioId ?: auth()->id(),
            'acc' => $accion,
            'tab' => $tabla,
            'reg_id' => $registroId,
            'des' => $descripcion,
            'dat_ant' => $datosAnteriores,
            'dat_nue' => $datosNuevos,
            'ip' => request()->ip(),
            'age_usu' => request()->userAgent(),
            'fec' => now()
        ]);
    }

    public static function logCreacion($modelo, $registroId, $datosNuevos = null, $usuarioId = null)
    {
        return static::crear(
            'crear',
            $modelo,
            $registroId,
            "Se creó un nuevo registro en {$modelo}",
            null,
            $datosNuevos,
            $usuarioId
        );
    }

    public static function logActualizacion($modelo, $registroId, $datosAnteriores = null, $datosNuevos = null, $usuarioId = null)
    {
        return static::crear(
            'actualizar',
            $modelo,
            $registroId,
            "Se actualizó un registro en {$modelo}",
            $datosAnteriores,
            $datosNuevos,
            $usuarioId
        );
    }

    public static function logEliminacion($modelo, $registroId, $datosAnteriores = null, $usuarioId = null)
    {
        return static::crear(
            'eliminar',
            $modelo,
            $registroId,
            "Se eliminó un registro en {$modelo}",
            $datosAnteriores,
            null,
            $usuarioId
        );
    }

    public static function logRestauracion($modelo, $registroId, $usuarioId = null)
    {
        return static::crear(
            'restaurar',
            $modelo,
            $registroId,
            "Se restauró un registro en {$modelo}",
            null,
            null,
            $usuarioId
        );
    }

    public static function logCambioEstado($modelo, $registroId, $estadoAnterior, $estadoNuevo, $usuarioId = null)
    {
        return static::crear(
            'cambiar_estado',
            $modelo,
            $registroId,
            "Estado cambiado de {$estadoAnterior} a {$estadoNuevo}",
            ['estado' => $estadoAnterior],
            ['estado' => $estadoNuevo],
            $usuarioId
        );
    }

    // Validaciones
    public static function reglas($id = null)
    {
        return [
            'usu_id' => 'nullable|exists:usuarios,id',
            'acc' => 'required|string|max:100',
            'tab' => 'nullable|string|max:100',
            'reg_id' => 'nullable|string|max:50',
            'des' => 'nullable|string',
            'dat_ant' => 'nullable|array',
            'dat_nue' => 'nullable|array',
            'ip' => 'nullable|ip',
            'age_usu' => 'nullable|string',
            'fec' => 'nullable|date',
        ];
    }

    public static function mensajes()
    {
        return [
            'usu_id.exists' => 'El usuario seleccionado no existe.',
            'acc.required' => 'La acción es obligatoria.',
            'acc.max' => 'La acción no puede exceder 100 caracteres.',
            'tab.max' => 'La tabla no puede exceder 100 caracteres.',
            'reg_id.max' => 'El ID del registro no puede exceder 50 caracteres.',
            'dat_ant.array' => 'Los datos anteriores deben ser un arreglo.',
            'dat_nue.array' => 'Los datos nuevos deben ser un arreglo.',
            'ip.ip' => 'La IP debe ser una dirección IP válida.',
            'fec.date' => 'La fecha debe ser una fecha válida.',
        ];
    }
}
