<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Archivo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'archivos';
    
    protected $fillable = [
        'usu_id', 'vee_id', 'tar_id', 'nom', 'nom_ori', 'ruta', 'tip', 
        'tam', 'est', 'des', 'met'
    ];

    protected $casts = [
        'met' => 'array',
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

    public function tarea()
    {
        return $this->belongsTo(Tarea::class, 'tar_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('est', 'act');
    }

    public function scopePorEstado($query, $estado)
    {
        return $query->where('est', $estado);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tip', $tipo);
    }

    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usu_id', $usuarioId);
    }

    public function scopePorVeeduria($query, $veeduriaId)
    {
        return $query->where('vee_id', $veeduriaId);
    }

    public function scopePorTarea($query, $tareaId)
    {
        return $query->where('tar_id', $tareaId);
    }

    public function scopeImagenes($query)
    {
        return $query->whereIn('tip', ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
    }

    public function scopeDocumentos($query)
    {
        return $query->whereIn('tip', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt']);
    }

    public function scopeVideos($query)
    {
        return $query->whereIn('tip', ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm']);
    }

    public function scopeAudios($query)
    {
        return $query->whereIn('tip', ['mp3', 'wav', 'ogg', 'aac', 'flac']);
    }

    // Métodos de negocio
    public function getEstadoColorAttribute()
    {
        return match ($this->est) {
            'act' => 'success',
            'eli' => 'danger',
            'err' => 'warning',
            default => 'secondary'
        };
    }

    public function getEstadoTextoAttribute()
    {
        return match ($this->est) {
            'act' => 'Activo',
            'eli' => 'Eliminado',
            'err' => 'Error',
            default => 'Desconocido'
        };
    }

    public function getTamañoFormateadoAttribute()
    {
        $bytes = $this->tam;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function esImagen()
    {
        return in_array($this->tip, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
    }

    public function esDocumento()
    {
        return in_array($this->tip, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt']);
    }

    public function esVideo()
    {
        return in_array($this->tip, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm']);
    }

    public function esAudio()
    {
        return in_array($this->tip, ['mp3', 'wav', 'ogg', 'aac', 'flac']);
    }

    public function marcarComoEliminado()
    {
        $this->update(['est' => 'eli']);
    }

    public function marcarComoError($descripcion = null)
    {
        $this->update([
            'est' => 'err',
            'des' => $descripcion ? $this->des . "\nError: " . $descripcion : $this->des
        ]);
    }

    public function restaurar()
    {
        $this->update(['est' => 'act']);
    }

    // Validaciones
    public static function reglas($id = null)
    {
        return [
            'usu_id' => 'required|exists:usuarios,id',
            'vee_id' => 'nullable|exists:veedurias,id',
            'tar_id' => 'nullable|exists:tareas,id',
            'nom' => 'required|string|max:255',
            'nom_ori' => 'required|string|max:255',
            'ruta' => 'required|string|max:500',
            'tip' => 'required|string|max:100',
            'tam' => 'required|integer|min:0',
            'est' => 'sometimes|in:act,eli,err',
            'des' => 'nullable|string',
        ];
    }

    public static function mensajes()
    {
        return [
            'usu_id.required' => 'El usuario es obligatorio.',
            'usu_id.exists' => 'El usuario seleccionado no existe.',
            'vee_id.exists' => 'La veeduría seleccionada no existe.',
            'tar_id.exists' => 'La tarea seleccionada no existe.',
            'nom.required' => 'El nombre del archivo es obligatorio.',
            'nom.max' => 'El nombre del archivo no puede exceder 255 caracteres.',
            'nom_ori.required' => 'El nombre original es obligatorio.',
            'nom_ori.max' => 'El nombre original no puede exceder 255 caracteres.',
            'ruta.required' => 'La ruta es obligatoria.',
            'ruta.max' => 'La ruta no puede exceder 500 caracteres.',
            'tip.required' => 'El tipo de archivo es obligatorio.',
            'tip.max' => 'El tipo de archivo no puede exceder 100 caracteres.',
            'tam.required' => 'El tamaño es obligatorio.',
            'tam.integer' => 'El tamaño debe ser un número entero.',
            'tam.min' => 'El tamaño no puede ser negativo.',
            'est.in' => 'El estado debe ser válido.',
        ];
    }
}