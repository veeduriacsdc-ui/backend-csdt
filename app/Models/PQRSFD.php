<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PQRSFD extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'PQRSFD';
    protected $primaryKey = 'IdPQRSFD';

    protected $fillable = [
        'IdCliente', 'IdOperadorAsignado', 'TipoPQRSFD', 'Asunto',
        'NarracionCliente', 'NarracionMejoradaIA', 'Estado',
        'FechaRegistro', 'FechaUltimaActualizacion', 'FechaRadicacion',
        'NumeroRadicacion', 'NotasOperador', 'RecomendacionesIA',
        'PresupuestoEstimado', 'AnalisisPrecioUnitario'
    ];

    protected $casts = [
        'FechaRegistro' => 'datetime',
        'FechaUltimaActualizacion' => 'datetime',
        'FechaRadicacion' => 'datetime',
        'RecomendacionesIA' => 'array',
        'AnalisisPrecioUnitario' => 'array',
        'PresupuestoEstimado' => 'decimal:2',
    ];

    // Relaciones
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'IdCliente', 'IdCliente');
    }

    public function operadorAsignado()
    {
        return $this->belongsTo(Operador::class, 'IdOperadorAsignado', 'IdOperador');
    }

    public function documentos()
    {
        return $this->hasMany(DocumentoAdjunto::class, 'IdPQRSFD', 'IdPQRSFD');
    }

    public function actividades()
    {
        return $this->hasMany(ActividadCaso::class, 'IdPQRSFD', 'IdPQRSFD');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoPQRSFD::class, 'IdPQRSFD', 'IdPQRSFD');
    }

    // Métodos de negocio
    public function getEstadoColorAttribute()
    {
        $colores = [
            'Pendiente' => 'warning',
            'EnProceso' => 'info',
            'Radicado' => 'primary',
            'Cerrado' => 'success',
            'Cancelado' => 'danger'
        ];
        return $colores[$this->Estado] ?? 'secondary';
    }

    public function getTiempoTranscurridoAttribute()
    {
        return $this->FechaRegistro->diffForHumans();
    }

    public function asignarOperador($idOperador)
    {
        $this->update([
            'IdOperadorAsignado' => $idOperador,
            'Estado' => 'EnProceso',
            'FechaUltimaActualizacion' => now()
        ]);
    }

    public function radicar()
    {
        $numeroRadicacion = 'RAD-' . date('Y') . '-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
        
        $this->update([
            'Estado' => 'Radicado',
            'FechaRadicacion' => now(),
            'NumeroRadicacion' => $numeroRadicacion,
            'FechaUltimaActualizacion' => now()
        ]);
    }

    public function cerrar()
    {
        $this->update([
            'Estado' => 'Cerrado',
            'FechaUltimaActualizacion' => now()
        ]);
    }

    public function cancelar()
    {
        $this->update([
            'Estado' => 'Cancelado',
            'FechaUltimaActualizacion' => now()
        ]);
    }

    // Scopes para consultas
    public function scopePorEstado($query, $estado)
    {
        return $query->where('Estado', $estado);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('TipoPQRSFD', $tipo);
    }

    public function scopePorOperador($query, $idOperador)
    {
        return $query->where('IdOperadorAsignado', $idOperador);
    }

    public function scopePorCliente($query, $idCliente)
    {
        return $query->where('IdCliente', $idCliente);
    }

    public function scopePendientes($query)
    {
        return $query->whereIn('Estado', ['Pendiente', 'EnProceso']);
    }

    public function scopePorFecha($query, $fechaInicio, $fechaFin = null)
    {
        if ($fechaFin) {
            return $query->whereBetween('FechaRegistro', [$fechaInicio, $fechaFin]);
        }
        return $query->whereDate('FechaRegistro', $fechaInicio);
    }

    // Validaciones
    public static function rules($id = null)
    {
        return [
            'IdCliente' => 'required|exists:Clientes,IdCliente',
            'IdOperadorAsignado' => 'nullable|exists:Operadores,IdOperador',
            'TipoPQRSFD' => 'required|in:Peticion,Queja,Reclamo,Sugerencia,Felicitacion,Denuncia',
            'Asunto' => 'required|string|max:500',
            'NarracionCliente' => 'required|string|min:10',
            'Estado' => 'required|in:Pendiente,EnProceso,Radicado,Cerrado,Cancelado',
            'PresupuestoEstimado' => 'nullable|numeric|min:0',
        ];
    }

    // Eventos del modelo
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($pqrsfd) {
            $pqrsfd->FechaUltimaActualizacion = now();
        });
    }
}
