<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Donacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'don';
    
    protected $fillable = [
        'usu_id', 'mon', 'tip', 'est', 'ref', 'des', 'fec_don', 'fec_con', 'not'
    ];

    protected $casts = [
        'fec_don' => 'datetime',
        'fec_con' => 'datetime',
        'mon' => 'decimal:2',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usu_id');
    }

    public function veeduria()
    {
        return $this->belongsTo(Veeduria::class, 'vee_id');
    }

    public function validadoPor()
    {
        return $this->belongsTo(Usuario::class, 'val_por');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('est', '!=', 'can');
    }

    public function scopePorEstado($query, $estado)
    {
        return $query->where('est', $estado);
    }

    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usu_id', $usuarioId);
    }

    public function scopePorVeeduria($query, $veeduriaId)
    {
        return $query->where('vee_id', $veeduriaId);
    }

    public function scopePendientes($query)
    {
        return $query->where('est', 'pen');
    }

    public function scopeConfirmadas($query)
    {
        return $query->where('est', 'con');
    }

    public function scopeRechazadas($query)
    {
        return $query->where('est', 'rec');
    }

    public function scopeAnonimas($query)
    {
        return $query->where('anon', true);
    }

    public function scopePorMetodoPago($query, $metodo)
    {
        return $query->where('met_pag', $metodo);
    }

    public function scopePorTipoDonacion($query, $tipo)
    {
        return $query->where('tip_don', $tipo);
    }

    public function scopePorRangoFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fec_don', [$fechaInicio, $fechaFin]);
    }

    // Métodos de negocio
    public function getEstadoColorAttribute()
    {
        return match ($this->est) {
            'pen' => 'warning',
            'con' => 'success',
            'rec' => 'danger',
            'pro' => 'info',
            'can' => 'secondary',
            default => 'secondary'
        };
    }

    public function getMetodoPagoTextoAttribute()
    {
        return match ($this->met_pag) {
            'tar' => 'Tarjeta',
            'tra' => 'Transferencia',
            'efe' => 'Efectivo',
            'bil' => 'Billetera Digital',
            'otr' => 'Otros',
            default => 'Desconocido'
        };
    }

    public function getTipoDonacionTextoAttribute()
    {
        return match ($this->tip_don) {
            'uni' => 'Única',
            'rec' => 'Recurrente',
            'men' => 'Mensual',
            'anu' => 'Anual',
            default => 'Desconocido'
        };
    }

    public function confirmar($validadoPorId = null)
    {
        $this->update([
            'est' => 'con',
            'fec_val' => now(),
            'val_por' => $validadoPorId
        ]);
    }

    public function rechazar($motivo = null)
    {
        $this->update([
            'est' => 'rec',
            'not' => $motivo ? $this->not . "\nRechazada: " . $motivo : $this->not
        ]);
    }

    public function cancelar($motivo = null)
    {
        $this->update([
            'est' => 'can',
            'not' => $motivo ? $this->not . "\nCancelada: " . $motivo : $this->not
        ]);
    }

    public function procesar()
    {
        $this->update(['est' => 'pro']);
    }

    // Validaciones
    public static function reglas($id = null)
    {
        return [
            'usu_id' => 'required|exists:usu,id',
            'mon' => 'required|numeric|min:0.01',
            'tip' => 'required|in:efec,tran,tar,otr',
            'est' => 'sometimes|in:pen,pro,con,rec,can',
            'ref' => 'nullable|string|max:100',
            'des' => 'nullable|string|max:300',
            'fec_don' => 'nullable|date',
            'fec_con' => 'nullable|date|after:fec_don',
            'not' => 'nullable|string',
        ];
    }

    public static function mensajes()
    {
        return [
            'usu_id.required' => 'El usuario es obligatorio.',
            'usu_id.exists' => 'El usuario seleccionado no existe.',
            'vee_id.exists' => 'La veeduría seleccionada no existe.',
            'val_por.exists' => 'El validador seleccionado no existe.',
            'mon.required' => 'El monto es obligatorio.',
            'mon.numeric' => 'El monto debe ser un número.',
            'mon.min' => 'El monto debe ser mayor a 0.',
            'moneda.required' => 'La moneda es obligatoria.',
            'moneda.in' => 'La moneda debe ser válida.',
            'met_pag.required' => 'El método de pago es obligatorio.',
            'met_pag.in' => 'El método de pago debe ser válido.',
            'est.in' => 'El estado debe ser válido.',
            'anon.boolean' => 'El campo anónima debe ser verdadero o falso.',
            'tip_don.required' => 'El tipo de donación es obligatorio.',
            'tip_don.in' => 'El tipo de donación debe ser válido.',
            'mot.max' => 'El motivo no puede exceder 300 caracteres.',
            'ref_pag.max' => 'La referencia de pago no puede exceder 100 caracteres.',
            'cam.max' => 'La campaña no puede exceder 100 caracteres.',
            'cod_pro.max' => 'El código promocional no puede exceder 50 caracteres.',
        ];
    }
}