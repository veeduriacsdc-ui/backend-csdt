<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PQRSFD extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pqrsfd';

    protected $fillable = [
        'cliente_id', 'operador_asignado_id', 'tipo_pqrsfd', 'asunto',
        'narracion_cliente', 'narracion_mejorada_ia', 'estado', 'prioridad',
        'es_prioritario', 'fecha_radicacion', 'fecha_cierre',
        'numero_radicacion', 'notas_operador', 'motivo_cierre',
        'categoria', 'ubicacion_geografica', 'coordenadas_lat', 'coordenadas_lng',
        'archivos_adjuntos', 'recomendaciones_ia', 'presupuesto_estimado',
        'tags', 'notas_internas',
    ];

    protected $casts = [
        'fecha_radicacion' => 'datetime',
        'fecha_cierre' => 'datetime',
        'coordenadas_lat' => 'decimal:8',
        'coordenadas_lng' => 'decimal:8',
        'archivos_adjuntos' => 'array',
        'recomendaciones_ia' => 'array',
        'presupuesto_estimado' => 'decimal:2',
        'tags' => 'array',
        'es_prioritario' => 'boolean',
    ];

    // Relaciones
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function operador_asignado()
    {
        return $this->belongsTo(Operador::class, 'operador_asignado_id');
    }

    public function documentos()
    {
        return $this->hasMany(DocumentoAdjunto::class, 'pqrsfd_id');
    }

    public function actividades_caso()
    {
        return $this->hasMany(ActividadCaso::class, 'pqrsfd_id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoPQRSFD::class, 'pqrsfd_id');
    }

    public function donaciones_asociadas()
    {
        return $this->hasMany(Donacion::class, 'pqrsfd_asociado_id');
    }

    // Métodos de negocio
    public function getEstadoColorAttribute()
    {
        return match ($this->estado) {
            'pendiente' => 'warning',
            'en_proceso' => 'info',
            'radicado' => 'primary',
            'cerrado' => 'success',
            'cancelado' => 'danger',
            'en_revision' => 'secondary',
            'pendiente_aprobacion' => 'info',
            default => 'secondary'
        };
    }

    public function getPrioridadColorAttribute()
    {
        return match ($this->prioridad) {
            'baja' => 'success',
            'media' => 'warning',
            'alta' => 'danger',
            'urgente' => 'danger',
            default => 'secondary'
        };
    }

    public function getTiempoTranscurridoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getDiasEnProcesoAttribute()
    {
        if ($this->fecha_cierre) {
            return $this->created_at->diffInDays($this->fecha_cierre);
        }

        return $this->created_at->diffInDays(now());
    }

    public function asignarOperador($idOperador)
    {
        $this->update([
            'operador_asignado_id' => $idOperador,
            'estado' => 'en_proceso',
        ]);
    }

    public function radicar()
    {
        $numeroRadicacion = 'RAD-' . date('Y') . '-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);

        $this->update([
            'estado' => 'radicado',
            'fecha_radicacion' => now(),
            'numero_radicacion' => $numeroRadicacion,
        ]);
    }

    public function cerrar()
    {
        $this->update([
            'estado' => 'cerrado',
            'fecha_cierre' => now(),
        ]);
    }

    public function cancelar()
    {
        $this->update([
            'estado' => 'cancelado',
        ]);
    }

    public function marcarComoPrioritario()
    {
        $this->update(['es_prioritario' => true]);
    }

    public function quitarPrioridad()
    {
        $this->update(['es_prioritario' => false]);
    }

    // Scopes para consultas
    public function scopeActivos($query)
    {
        return $query->where('estado', '!=', 'cancelado');
    }

    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_pqrsfd', $tipo);
    }

    public function scopePorPrioridad($query, $prioridad)
    {
        return $query->where('prioridad', $prioridad);
    }

    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function scopePorOperador($query, $idOperador)
    {
        return $query->where('operador_asignado_id', $idOperador);
    }

    public function scopePorCliente($query, $idCliente)
    {
        return $query->where('cliente_id', $idCliente);
    }

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['pendiente', 'en_proceso', 'en_revision']);
    }

    public function scopeCerrados($query)
    {
        return $query->where('estado', 'cerrado');
    }

    public function scopePrioritarios($query)
    {
        return $query->where('es_prioritario', true);
    }

    public function scopeConPresupuesto($query)
    {
        return $query->whereNotNull('presupuesto_estimado');
    }

    public function scopePorFecha($query, $fechaInicio, $fechaFin = null)
    {
        if ($fechaFin) {
            return $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
        }

        return $query->whereDate('created_at', $fechaInicio);
    }

    public function scopePorRangoFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
    }

    // Validaciones personalizadas
    public static function rules($id = null)
    {
        return [
            'cliente_id' => 'required|exists:clientes,id',
            'operador_asignado_id' => 'nullable|exists:operadores,id',
            'tipo_pqrsfd' => 'required|in:peticion,queja,reclamo,sugerencia,felicitacion,denuncia,solicitud_informacion',
            'asunto' => 'required|string|max:200',
            'narracion_cliente' => 'required|string|min:10|max:5000',
            'narracion_mejorada_ia' => 'nullable|string|max:10000',
            'estado' => 'required|in:pendiente,en_proceso,radicado,cerrado,cancelado,en_revision,pendiente_aprobacion',
            'prioridad' => 'required|in:baja,media,alta,urgente',
            'categoria' => 'nullable|in:infraestructura,servicios_publicos,seguridad,educacion,salud,transporte,medio_ambiente,otros',
            'ubicacion_geografica' => 'nullable|string|max:200',
            'presupuesto_estimado' => 'nullable|numeric|min:0|max:999999999.99',
            'archivos_adjuntos' => 'nullable|array',
            'archivos_adjuntos.*' => 'string|max:500',
        ];
    }

    public static function messages()
    {
        return [
            'cliente_id.required' => 'El cliente es obligatorio.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'operador_asignado_id.exists' => 'El operador seleccionado no existe.',
            'tipo_pqrsfd.required' => 'El tipo de PQRSFD es obligatorio.',
            'tipo_pqrsfd.in' => 'El tipo de PQRSFD seleccionado no es válido.',
            'asunto.required' => 'El asunto es obligatorio.',
            'asunto.max' => 'El asunto no puede exceder 200 caracteres.',
            'narracion_cliente.required' => 'La narración del cliente es obligatoria.',
            'narracion_cliente.min' => 'La narración debe tener al menos 10 caracteres.',
            'narracion_cliente.max' => 'La narración no puede exceder 5000 caracteres.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'prioridad.required' => 'La prioridad es obligatoria.',
            'prioridad.in' => 'La prioridad seleccionada no es válida.',
            'categoria.in' => 'La categoría seleccionada no es válida.',
            'ubicacion_geografica.max' => 'La ubicación geográfica no puede exceder 200 caracteres.',
            'presupuesto_estimado.numeric' => 'El presupuesto estimado debe ser un número válido.',
            'presupuesto_estimado.min' => 'El presupuesto estimado debe ser mayor o igual a cero.',
            'presupuesto_estimado.max' => 'El presupuesto estimado no puede exceder 999,999,999.99.',
            'archivos_adjuntos.array' => 'Los archivos adjuntos deben ser un arreglo.',
            'archivos_adjuntos.*.max' => 'Cada archivo adjunto no puede exceder 500 caracteres.',
        ];
    }

    // Eventos del modelo
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pqrsfd) {
            if (!$pqrsfd->fecha_registro) {
                $pqrsfd->fecha_registro = now();
            }

            if (!$pqrsfd->fecha_ultima_actualizacion) {
                $pqrsfd->fecha_ultima_actualizacion = now();
            }
        });

        static::updating(function ($pqrsfd) {
            $pqrsfd->fecha_ultima_actualizacion = now();
        });
    }
}
