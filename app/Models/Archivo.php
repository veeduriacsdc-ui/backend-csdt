<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Archivo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'arc';
    
    protected $fillable = [
        'usu_id',
        'vee_id',
        'tar_id',
        'nom',
        'tip',
        'tam',
        'ruta',
        'des',
        'est',
        'hash_archivo',
        'mime_type'
    ];

    protected $casts = [
        'tam' => 'integer',
        'usu_id' => 'integer',
        'vee_id' => 'integer',
        'tar_id' => 'integer'
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

    // Métodos de utilidad
    public function obtenerTamañoFormateado()
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
        $tiposImagen = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
        return in_array(strtolower($this->tip), $tiposImagen);
    }

    public function esDocumento()
    {
        $tiposDocumento = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf'];
        return in_array(strtolower($this->tip), $tiposDocumento);
    }

    public function esVideo()
    {
        $tiposVideo = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'];
        return in_array(strtolower($this->tip), $tiposVideo);
    }

    public function esAudio()
    {
        $tiposAudio = ['mp3', 'wav', 'flac', 'aac', 'ogg', 'wma'];
        return in_array(strtolower($this->tip), $tiposAudio);
    }

    public function obtenerCategoria()
    {
        if ($this->esImagen()) return 'imagen';
        if ($this->esDocumento()) return 'documento';
        if ($this->esVideo()) return 'video';
        if ($this->esAudio()) return 'audio';
        return 'otro';
    }
}