<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NarracionIA extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'narraciones_consejo_ia';
    
    protected $fillable = [
        'codigo_narracion',
        'tipo_narracion',
        'contenido',
        'narracion_generada',
        'confianza',
        'datos_cliente',
        'ubicacion',
        'usu_id',
        'vee_id',
        'est'
    ];

    protected $casts = [
        'datos_cliente' => 'array',
        'ubicacion' => 'array',
        'confianza' => 'integer',
        'usu_id' => 'integer',
        'vee_id' => 'integer'
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

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('est', 'act');
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_narracion', $tipo);
    }

    // Métodos de utilidad
    public static function generarCodigo()
    {
        return 'NAR-' . strtoupper(substr(md5(uniqid()), 0, 8));
    }

    public function esAltaConfianza()
    {
        return $this->confianza >= 80;
    }

    public function esMediaConfianza()
    {
        return $this->confianza >= 50 && $this->confianza < 80;
    }

    public function esBajaConfianza()
    {
        return $this->confianza < 50;
    }
}
