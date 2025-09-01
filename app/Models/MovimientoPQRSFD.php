<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovimientoPQRSFD extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'MovimientosPQRSFD';
    protected $primaryKey = 'IdMovimiento';

    protected $fillable = [
        'IdPQRSFD', 'IdOperador', 'TipoMovimiento', 'Descripcion',
        'EstadoAnterior', 'EstadoNuevo', 'Comentarios', 'FechaMovimiento'
    ];

    protected $casts = [
        'FechaMovimiento' => 'datetime',
    ];

    // Relaciones
    public function pqrsfd()
    {
        return $this->belongsTo(PQRSFD::class, 'IdPQRSFD', 'IdPQRSFD');
    }

    public function operador()
    {
        return $this->belongsTo(Operador::class, 'IdOperador', 'IdOperador');
    }

    // Métodos de negocio
    public function getTipoMovimientoColorAttribute()
    {
        $colores = [
            'Creacion' => 'success',
            'Asignacion' => 'info',
            'CambioEstado' => 'warning',
            'Comentario' => 'primary',
            'Radicacion' => 'purple',
            'Cierre' => 'dark'
        ];
        return $colores[$this->TipoMovimiento] ?? 'secondary';
    }

    public function getIconoTipoAttribute()
    {
        $iconos = [
            'Creacion' => 'fas fa-plus-circle',
            'Asignacion' => 'fas fa-user-plus',
            'CambioEstado' => 'fas fa-exchange-alt',
            'Comentario' => 'fas fa-comment',
            'Radicacion' => 'fas fa-file-alt',
            'Cierre' => 'fas fa-check-circle'
        ];
        return $iconos[$this->TipoMovimiento] ?? 'fas fa-info-circle';
    }

    public function getResumenAttribute()
    {
        $resumen = $this->TipoMovimiento;
        
        if ($this->EstadoAnterior && $this->EstadoNuevo) {
            $resumen .= ': ' . $this->EstadoAnterior . ' → ' . $this->EstadoNuevo;
        }
        
        return $resumen;
    }

    // Scopes para consultas
    public function scopePorPQRSFD($query, $idPQRSFD)
    {
        return $query->where('IdPQRSFD', $idPQRSFD);
    }

    public function scopePorOperador($query, $idOperador)
    {
        return $query->where('IdOperador', $idOperador);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('TipoMovimiento', $tipo);
    }

    public function scopePorFecha($query, $fechaInicio, $fechaFin = null)
    {
        if ($fechaFin) {
            return $query->whereBetween('FechaMovimiento', [$fechaInicio, $fechaFin]);
        }
        return $query->whereDate('FechaMovimiento', $fechaInicio);
    }

    public function scopeRecientes($query, $limite = 10)
    {
        return $query->orderBy('FechaMovimiento', 'desc')->limit($limite);
    }

    // Métodos estáticos para crear movimientos
    public static function crearMovimiento($idPQRSFD, $idOperador, $tipo, $descripcion, $estadoAnterior = null, $estadoNuevo = null, $comentarios = null)
    {
        return self::create([
            'IdPQRSFD' => $idPQRSFD,
            'IdOperador' => $idOperador,
            'TipoMovimiento' => $tipo,
            'Descripcion' => $descripcion,
            'EstadoAnterior' => $estadoAnterior,
            'EstadoNuevo' => $estadoNuevo,
            'Comentarios' => $comentarios,
            'FechaMovimiento' => now()
        ]);
    }

    public static function crearCambioEstado($idPQRSFD, $idOperador, $estadoAnterior, $estadoNuevo, $comentarios = null)
    {
        return self::crearMovimiento(
            $idPQRSFD,
            $idOperador,
            'CambioEstado',
            "Cambio de estado de {$estadoAnterior} a {$estadoNuevo}",
            $estadoAnterior,
            $estadoNuevo,
            $comentarios
        );
    }

    public static function crearAsignacion($idPQRSFD, $idOperador, $idOperadorAsignado, $comentarios = null)
    {
        return self::crearMovimiento(
            $idPQRSFD,
            $idOperador,
            'Asignacion',
            "Asignado al operador ID: {$idOperadorAsignado}",
            null,
            null,
            $comentarios
        );
    }

    public static function crearComentario($idPQRSFD, $idOperador, $comentarios)
    {
        return self::crearMovimiento(
            $idPQRSFD,
            $idOperador,
            'Comentario',
            "Nuevo comentario agregado",
            null,
            null,
            $comentarios
        );
    }

    // Validaciones
    public static function rules($id = null)
    {
        return [
            'IdPQRSFD' => 'required|exists:PQRSFD,IdPQRSFD',
            'IdOperador' => 'required|exists:Operadores,IdOperador',
            'TipoMovimiento' => 'required|in:Creacion,Asignacion,CambioEstado,Comentario,Radicacion,Cierre',
            'Descripcion' => 'required|string|max:500',
            'EstadoAnterior' => 'nullable|string|max:50',
            'EstadoNuevo' => 'nullable|string|max:50',
            'Comentarios' => 'nullable|string|max:1000',
            'FechaMovimiento' => 'required|date',
        ];
    }

    // Eventos del modelo
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($movimiento) {
            if (empty($movimiento->FechaMovimiento)) {
                $movimiento->FechaMovimiento = now();
            }
        });
    }
}
