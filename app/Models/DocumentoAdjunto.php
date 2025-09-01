<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentoAdjunto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'DocumentosAdjuntos';
    protected $primaryKey = 'IdDocumento';

    protected $fillable = [
        'IdCliente', 'IdPQRSFD', 'NombreArchivo', 'RutaArchivo',
        'TipoArchivo', 'TamanoArchivo', 'Descripcion', 'Estado'
    ];

    protected $casts = [
        'TamanoArchivo' => 'integer',
        'Estado' => 'boolean',
    ];

    // Relaciones
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'IdCliente', 'IdCliente');
    }

    public function pqrsfd()
    {
        return $this->belongsTo(PQRSFD::class, 'IdPQRSFD', 'IdPQRSFD');
    }

    // Métodos de negocio
    public function getTamanoFormateadoAttribute()
    {
        $bytes = $this->TamanoArchivo;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getExtensionAttribute()
    {
        return pathinfo($this->NombreArchivo, PATHINFO_EXTENSION);
    }

    public function getIconoArchivoAttribute()
    {
        $extension = strtolower($this->extension);
        
        $iconos = [
            'pdf' => 'fas fa-file-pdf text-red-500',
            'doc' => 'fas fa-file-word text-blue-500',
            'docx' => 'fas fa-file-word text-blue-500',
            'xls' => 'fas fa-file-excel text-green-500',
            'xlsx' => 'fas fa-file-excel text-green-500',
            'jpg' => 'fas fa-file-image text-purple-500',
            'jpeg' => 'fas fa-file-image text-purple-500',
            'png' => 'fas fa-file-image text-purple-500',
            'gif' => 'fas fa-file-image text-purple-500',
            'txt' => 'fas fa-file-alt text-gray-500',
        ];
        
        return $iconos[$extension] ?? 'fas fa-file text-gray-500';
    }

    public function getUrlDescargaAttribute()
    {
        return route('documentos.download', $this->IdDocumento);
    }

    public function activar()
    {
        $this->update(['Estado' => true]);
    }

    public function desactivar()
    {
        $this->update(['Estado' => false]);
    }

    // Scopes para consultas
    public function scopeActivos($query)
    {
        return $query->where('Estado', true);
    }

    public function scopePorCliente($query, $idCliente)
    {
        return $query->where('IdCliente', $idCliente);
    }

    public function scopePorPQRSFD($query, $idPQRSFD)
    {
        return $query->where('IdPQRSFD', $idPQRSFD);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('TipoArchivo', $tipo);
    }

    public function scopePorExtension($query, $extension)
    {
        return $query->where('NombreArchivo', 'like', '%.' . $extension);
    }

    // Validaciones
    public static function rules($id = null)
    {
        return [
            'IdCliente' => 'required|exists:Clientes,IdCliente',
            'IdPQRSFD' => 'nullable|exists:PQRSFD,IdPQRSFD',
            'NombreArchivo' => 'required|string|max:255',
            'RutaArchivo' => 'required|string|max:500',
            'TipoArchivo' => 'required|string|max:100',
            'TamanoArchivo' => 'required|integer|min:1',
            'Descripcion' => 'nullable|string|max:1000',
            'Estado' => 'boolean',
        ];
    }

    // Eventos del modelo
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($documento) {
            // Eliminar archivo físico si existe
            if (file_exists(storage_path('app/' . $documento->RutaArchivo))) {
                unlink(storage_path('app/' . $documento->RutaArchivo));
            }
        });
    }
}
