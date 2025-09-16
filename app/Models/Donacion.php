<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donacion extends Model
{
    use HasFactory;

    protected $table = 'donaciones';

    protected $fillable = [
        'numero_referencia',
        'nombre_donante',
        'email_donante',
        'telefono_donante',
        'monto',
        'tipo_donacion',
        'metodo_pago',
        'mensaje',
        'referencia_donacion',
        'archivo_comprobante',
        'archivo_original',
        'estado',
        'observaciones_admin',
        'certificado_pdf',
        'fecha_validacion',
        'fecha_certificacion',
        'validado_por',
        'certificado_por'
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_validacion' => 'datetime',
        'fecha_certificacion' => 'datetime',
    ];

    // Relaciones
    public function validador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'validado_por');
    }

    public function certificador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'certificado_por');
    }

    // Accessors
    public function getMontoFormateadoAttribute(): string
    {
        return '$' . number_format($this->monto, 0, ',', '.') . ' COP';
    }

    public function getArchivoComprobanteUrlAttribute(): string
    {
        return $this->archivo_comprobante ? 
            asset('storage/donaciones/comprobantes/' . $this->archivo_comprobante) : 
            '';
    }

    public function getCertificadoPdfUrlAttribute(): string
    {
        return $this->certificado_pdf ? 
            asset('storage/donaciones/certificados/' . $this->certificado_pdf) : 
            '';
    }

    public function getEstadoColorAttribute(): string
    {
        return match($this->estado) {
            'pendiente' => '#f59e0b',
            'validado' => '#10b981',
            'rechazado' => '#ef4444',
            'certificado' => '#3b82f6',
            default => '#6b7280'
        };
    }

    public function getEstadoTextoAttribute(): string
    {
        return match($this->estado) {
            'pendiente' => 'Pendiente',
            'validado' => 'Validado',
            'rechazado' => 'Rechazado',
            'certificado' => 'Certificado',
            default => 'Desconocido'
        };
    }

    // Métodos estáticos
    public static function generarNumeroReferencia(): string
    {
        do {
            $numero = 'CSDT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (self::where('numero_referencia', $numero)->exists());

        return $numero;
    }

    public static function obtenerEstadisticas(): array
    {
        return [
            'total_donaciones' => self::count(),
            'donaciones_pendientes' => self::where('estado', 'pendiente')->count(),
            'donaciones_validadas' => self::where('estado', 'validado')->count(),
            'donaciones_certificadas' => self::where('estado', 'certificado')->count(),
            'monto_total' => self::where('estado', '!=', 'rechazado')->sum('monto'),
            'monto_mes_actual' => self::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->where('estado', '!=', 'rechazado')
                ->sum('monto')
        ];
    }
}