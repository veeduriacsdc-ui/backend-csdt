<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Veeduria extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'veedurias';
    
    protected $fillable = [
        'usu_id', 'ope_id', 'tit', 'des', 'tip', 'est', 'pri', 'cat', 'ubi', 
        'pre', 'fec_reg', 'fec_rad', 'fec_cer', 'num_rad', 'not_ope', 
        'rec_ia', 'arc'
    ];

    protected $casts = [
        'fec_reg' => 'datetime',
        'fec_rad' => 'datetime',
        'fec_cer' => 'datetime',
        'rec_ia' => 'array',
        'arc' => 'array',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usu_id');
    }

    public function operador()
    {
        return $this->belongsTo(Usuario::class, 'ope_id');
    }

    public function donaciones()
    {
        return $this->hasMany(Donacion::class, 'vee_id');
    }

    public function tareas()
    {
        return $this->hasMany(Tarea::class, 'vee_id');
    }

    public function archivos()
    {
        return $this->hasMany(Archivo::class, 'vee_id');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('est', '!=', 'cer');
    }

    public function scopePorEstado($query, $estado)
    {
        return $query->where('est', $estado);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tip', $tipo);
    }

    public function scopePorPrioridad($query, $prioridad)
    {
        return $query->where('pri', $prioridad);
    }

    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('cat', $categoria);
    }

    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usu_id', $usuarioId);
    }

    public function scopePorOperador($query, $operadorId)
    {
        return $query->where('ope_id', $operadorId);
    }

    public function scopePendientes($query)
    {
        return $query->where('est', 'pen');
    }

    public function scopeEnProceso($query)
    {
        return $query->where('est', 'pro');
    }

    public function scopeRadicadas($query)
    {
        return $query->where('est', 'rad');
    }

    public function scopeCerradas($query)
    {
        return $query->where('est', 'cer');
    }

    // Métodos de negocio
    public function getEstadoColorAttribute()
    {
        return match ($this->est) {
            'pen' => 'warning',
            'pro' => 'info',
            'rad' => 'primary',
            'cer' => 'success',
            'can' => 'danger',
            default => 'secondary'
        };
    }

    public function getPrioridadColorAttribute()
    {
        return match ($this->pri) {
            'baj' => 'success',
            'med' => 'warning',
            'alt' => 'danger',
            'urg' => 'dark',
            default => 'secondary'
        };
    }

    public function getTipoTextoAttribute()
    {
        return match ($this->tip) {
            'pet' => 'Petición',
            'que' => 'Queja',
            'rec' => 'Reclamo',
            'sug' => 'Sugerencia',
            'fel' => 'Felicitación',
            'den' => 'Denuncia',
            default => 'Desconocido'
        };
    }

    public function radicar()
    {
        $this->update([
            'est' => 'rad',
            'fec_rad' => now(),
            'num_rad' => $this->generarNumeroRadicacion()
        ]);
    }

    public function cerrar()
    {
        $this->update([
            'est' => 'cer',
            'fec_cer' => now()
        ]);
    }

    public function cancelar()
    {
        $this->update(['est' => 'can']);
    }

    public function asignarOperador($operadorId)
    {
        $this->update(['ope_id' => $operadorId]);
    }

    private function generarNumeroRadicacion()
    {
        $prefijo = 'VEE';
        $fecha = now()->format('Ymd');
        $secuencial = str_pad(Veeduria::whereDate('fec_rad', now()->toDateString())->count() + 1, 4, '0', STR_PAD_LEFT);
        
        return $prefijo . $fecha . $secuencial;
    }

    // Validaciones
    public static function reglas($id = null)
    {
        return [
            'usu_id' => 'required|exists:usuarios,id',
            'ope_id' => 'nullable|exists:usuarios,id',
            'tit' => 'required|string|max:200',
            'des' => 'required|string',
            'tip' => 'required|in:pet,que,rec,sug,fel,den',
            'est' => 'sometimes|in:pen,pro,rad,cer,can',
            'pri' => 'sometimes|in:baj,med,alt,urg',
            'cat' => 'nullable|in:inf,ser,seg,edu,sal,tra,amb,otr',
            'ubi' => 'nullable|string|max:200',
            'pre' => 'nullable|numeric|min:0',
        ];
    }

    public static function mensajes()
    {
        return [
            'usu_id.required' => 'El usuario es obligatorio.',
            'usu_id.exists' => 'El usuario seleccionado no existe.',
            'tit.required' => 'El título es obligatorio.',
            'tit.max' => 'El título no puede exceder 200 caracteres.',
            'des.required' => 'La descripción es obligatoria.',
            'tip.required' => 'El tipo es obligatorio.',
            'tip.in' => 'El tipo debe ser válido.',
            'est.in' => 'El estado debe ser válido.',
            'pri.in' => 'La prioridad debe ser válida.',
            'cat.in' => 'La categoría debe ser válida.',
            'ubi.max' => 'La ubicación no puede exceder 200 caracteres.',
            'pre.numeric' => 'El presupuesto debe ser un número.',
            'pre.min' => 'El presupuesto no puede ser negativo.',
        ];
    }
}
