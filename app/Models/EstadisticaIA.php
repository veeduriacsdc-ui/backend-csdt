<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadisticaIA extends Model
{
    use HasFactory;

    protected $table = 'estadisticas_ia';
    
    protected $fillable = [
        'fecha',
        'tipo_metrica',
        'categoria',
        'valor',
        'descripcion',
        'metadatos'
    ];

    protected $casts = [
        'fecha' => 'date',
        'valor' => 'decimal:2',
        'metadatos' => 'array'
    ];

    // Scopes
    public function scopePorFecha($query, $fecha)
    {
        return $query->where('fecha', $fecha);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_metrica', $tipo);
    }

    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function scopeRangoFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }

    // Métodos de utilidad
    public static function obtenerMetricasPorFecha($fecha)
    {
        return self::porFecha($fecha)->get();
    }

    public static function obtenerMetricasPorTipo($tipo)
    {
        return self::porTipo($tipo)->get();
    }

    public static function obtenerMetricasPorCategoria($categoria)
    {
        return self::porCategoria($categoria)->get();
    }

    public static function obtenerResumenMetricas($fechaInicio, $fechaFin)
    {
        return self::rangoFechas($fechaInicio, $fechaFin)
            ->selectRaw('tipo_metrica, categoria, SUM(valor) as total, AVG(valor) as promedio, COUNT(*) as cantidad')
            ->groupBy('tipo_metrica', 'categoria')
            ->get();
    }
}
