<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Veeduria extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vee';
    protected $primaryKey = 'id';

    protected $fillable = [
        'usu_id',
        'ope_id',
        'tit',
        'des',
        'tip',
        'est',
        'pri',
        'cat',
        'ubi',
        'pre',
        'fec_reg',
        'fec_rad',
        'fec_cer',
        'num_rad',
        'not_ope',
        'rec_ia',
        'arc'
    ];

    protected $casts = [
        'fec_reg' => 'datetime',
        'fec_rad' => 'datetime',
        'fec_cer' => 'datetime',
        'pre' => 'decimal:2',
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

    public function tareas()
    {
        return $this->hasMany(Tarea::class, 'vee_id');
    }

    public function archivos()
    {
        return $this->hasMany(Archivo::class, 'vee_id');
    }

    public function analisisIA()
    {
        return $this->hasMany(AnalisisIA::class, 'vee_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->whereIn('est', ['pen', 'pro', 'rad']);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tip', $tipo);
    }

    public function scopePorEstado($query, $estado)
    {
        return $query->where('est', $estado);
    }

    public function scopePorPrioridad($query, $prioridad)
    {
        return $query->where('pri', $prioridad);
    }

    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('cat', $categoria);
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

    public function scopeCanceladas($query)
    {
        return $query->where('est', 'can');
    }

    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usu_id', $usuarioId);
    }

    public function scopePorOperador($query, $operadorId)
    {
        return $query->where('ope_id', $operadorId);
    }

    public function scopeConPresupuesto($query)
    {
        return $query->whereNotNull('pre')->where('pre', '>', 0);
    }

    public function scopeSinPresupuesto($query)
    {
        return $query->where(function($q) {
            $q->whereNull('pre')->orWhere('pre', 0);
        });
    }

    // Accessors
    public function getTipoDescripcionAttribute()
    {
        $tipos = [
            'pet' => 'Petición',
            'que' => 'Queja',
            'rec' => 'Reclamo',
            'sug' => 'Sugerencia',
            'fel' => 'Felicitación',
            'den' => 'Denuncia'
        ];
        return $tipos[$this->tip] ?? 'Desconocido';
    }

    public function getEstadoDescripcionAttribute()
    {
        $estados = [
            'pen' => 'Pendiente',
            'pro' => 'En Proceso',
            'rad' => 'Radicada',
            'cer' => 'Cerrada',
            'can' => 'Cancelada'
        ];
        return $estados[$this->est] ?? 'Desconocido';
    }

    public function getPrioridadDescripcionAttribute()
    {
        $prioridades = [
            'baj' => 'Baja',
            'med' => 'Media',
            'alt' => 'Alta',
            'urg' => 'Urgente'
        ];
        return $prioridades[$this->pri] ?? 'Desconocida';
    }

    public function getCategoriaDescripcionAttribute()
    {
        $categorias = [
            'inf' => 'Infraestructura',
            'ser' => 'Servicios',
            'seg' => 'Seguridad',
            'edu' => 'Educación',
            'sal' => 'Salud',
            'tra' => 'Transporte',
            'amb' => 'Ambiente',
            'otr' => 'Otros'
        ];
        return $categorias[$this->cat] ?? 'Sin categoría';
    }

    public function getDiasTranscurridosAttribute()
    {
        return $this->fec_reg ? $this->fec_reg->diffInDays(now()) : 0;
    }

    public function getEsUrgenteAttribute()
    {
        return $this->pri === 'urg' || $this->dias_transcurridos > 30;
    }

    // Mutators
    public function setTitAttribute($value)
    {
        $this->attributes['tit'] = ucfirst(trim($value));
    }

    public function setDesAttribute($value)
    {
        $this->attributes['des'] = trim($value);
    }

    // Métodos de utilidad
    public function asignarOperador($operadorId, $notas = null)
    {
        $this->update([
            'ope_id' => $operadorId,
            'not_ope' => $notas,
            'est' => 'pro'
        ]);
    }

    public function radicar($numeroRadicacion = null)
    {
        $numero = $numeroRadicacion ?? $this->generarNumeroRadicacion();
        $this->update([
            'num_rad' => $numero,
            'fec_rad' => now(),
            'est' => 'rad'
        ]);
    }

    public function cerrar($notas = null)
    {
        $this->update([
            'fec_cer' => now(),
            'est' => 'cer',
            'not_ope' => $notas ? $this->not_ope . "\n" . $notas : $this->not_ope
        ]);
    }

    public function cancelar($motivo = null)
    {
        $this->update([
            'est' => 'can',
            'not_ope' => $motivo ? $this->not_ope . "\nCancelada: " . $motivo : $this->not_ope
        ]);
    }

    public function generarNumeroRadicacion()
    {
        $fecha = now()->format('Ymd');
        $ultimo = self::whereDate('fec_rad', now())->count();
        return 'VEE-' . $fecha . '-' . str_pad($ultimo + 1, 4, '0', STR_PAD_LEFT);
    }

    public function agregarRecomendacionIA($recomendacion)
    {
        $rec_ia = $this->rec_ia ?? [];
        $rec_ia[] = [
            'texto' => $recomendacion,
            'fecha' => now()->toISOString(),
            'tipo' => 'ia'
        ];
        $this->update(['rec_ia' => $rec_ia]);
    }

    public function agregarArchivo($archivo)
    {
        $arc = $this->arc ?? [];
        $arc[] = [
            'nombre' => $archivo['nombre'],
            'ruta' => $archivo['ruta'],
            'tipo' => $archivo['tipo'],
            'fecha' => now()->toISOString()
        ];
        $this->update(['arc' => $arc]);
    }
}