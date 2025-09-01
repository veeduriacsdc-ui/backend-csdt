<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class Archivo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'archivos';

    protected $fillable = [
        'codigo_archivo',
        'nombre_original',
        'nombre_almacenado',
        'ruta_archivo',
        'tipo_archivo',
        'extension',
        'tamanio_bytes',
        'mime_type',
        'categoria',
        'estado',
        'cliente_id',
        'veeduria_id',
        'tarea_id',
        'donacion_id',
        'narracion_id',
        'fecha_subida',
        'fecha_modificacion',
        'fecha_expiracion',
        'hash_archivo',
        'hash_verificacion',
        'metadatos',
        'tags',
        'descripcion',
        'palabras_clave',
        'nivel_privacidad',
        'permisos_acceso',
        'version',
        'comentarios',
        'notas_internas',
        'ubicacion_geografica',
        'coordenadas_lat',
        'coordenadas_lng',
        'fecha_captura',
        'dispositivo_captura',
        'resolucion',
        'calidad_estimada',
        'compresion_aplicada',
        'encriptacion',
        'firma_digital'
    ];

    protected $casts = [
        'fecha_subida' => 'datetime',
        'fecha_modificacion' => 'datetime',
        'fecha_expiracion' => 'datetime',
        'fecha_captura' => 'datetime',
        'metadatos' => 'array',
        'tags' => 'array',
        'palabras_clave' => 'array',
        'permisos_acceso' => 'array',
        'coordenadas_lat' => 'decimal:8',
        'coordenadas_lng' => 'decimal:8',
        'tamanio_bytes' => 'integer',
        'version' => 'integer',
        'calidad_estimada' => 'decimal:3,2',
        'resolucion' => 'array'
    ];

    // Relaciones
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function veeduria(): BelongsTo
    {
        return $this->belongsTo(Veeduria::class, 'veeduria_id');
    }

    public function tarea(): BelongsTo
    {
        return $this->belongsTo(Tarea::class, 'tarea_id');
    }

    public function donacion(): BelongsTo
    {
        return $this->belongsTo(Donacion::class, 'donacion_id');
    }

    public function narracion(): BelongsTo
    {
        return $this->belongsTo(NarracionConsejoIa::class, 'narracion_id');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'entidad_id')
            ->where('entidad', 'archivo');
    }

    public function logsAuditoria(): HasMany
    {
        return $this->hasMany(LogAuditoria::class, 'entidad_id')
            ->where('entidad', 'archivo');
    }

    // Scopes
    public function scopeActivos(Builder $query): void
    {
        $query->where('estado', 'activo');
    }

    public function scopeInactivos(Builder $query): void
    {
        $query->where('estado', 'inactivo');
    }

    public function scopePorTipo(Builder $query, string $tipo): void
    {
        $query->where('tipo_archivo', $tipo);
    }

    public function scopePorCategoria(Builder $query, string $categoria): void
    {
        $query->where('categoria', $categoria);
    }

    public function scopePorExtension(Builder $query, string $extension): void
    {
        $query->where('extension', strtolower($extension));
    }

    public function scopePorCliente(Builder $query, int $clienteId): void
    {
        $query->where('cliente_id', $clienteId);
    }

    public function scopePorVeeduria(Builder $query, int $veeduriaId): void
    {
        $query->where('veeduria_id', $veeduriaId);
    }

    public function scopePorTarea(Builder $query, int $tareaId): void
    {
        $query->where('tarea_id', $tareaId);
    }

    public function scopePorDonacion(Builder $query, int $donacionId): void
    {
        $query->where('donacion_id', $donacionId);
    }

    public function scopePorNarracion(Builder $query, int $narracionId): void
    {
        $query->where('narracion_id', $narracionId);
    }

    public function scopePorRangoTamanio(Builder $query, int $tamanioMinimo, int $tamanioMaximo = null): void
    {
        if ($tamanioMaximo) {
            $query->whereBetween('tamanio_bytes', [$tamanioMinimo, $tamanioMaximo]);
        } else {
            $query->where('tamanio_bytes', '>=', $tamanioMinimo);
        }
    }

    public function scopePorRangoFechas(Builder $query, string $fechaInicio, string $fechaFin): void
    {
        $query->whereBetween('fecha_subida', [$fechaInicio, $fechaFin]);
    }

    public function scopePorNivelPrivacidad(Builder $query, string $nivel): void
    {
        $query->where('nivel_privacidad', $nivel);
    }

    public function scopeConTags(Builder $query, array $tags): void
    {
        foreach ($tags as $tag) {
            $query->whereJsonContains('tags', $tag);
        }
    }

    public function scopePorMimeType(Builder $query, string $mimeType): void
    {
        $query->where('mime_type', $mimeType);
    }

    public function scopeImagenes(Builder $query): void
    {
        $query->where('mime_type', 'like', 'image/%');
    }

    public function scopeDocumentos(Builder $query): void
    {
        $query->where('mime_type', 'like', 'application/%');
    }

    public function scopeVideos(Builder $query): void
    {
        $query->where('mime_type', 'like', 'video/%');
    }

    public function scopeAudios(Builder $query): void
    {
        $query->where('mime_type', 'like', 'audio/%');
    }

    public function scopeSinExpiracion(Builder $query): void
    {
        $query->whereNull('fecha_expiracion');
    }

    public function scopeExpirados(Builder $query): void
    {
        $query->where('fecha_expiracion', '<', now());
    }

    public function scopePorVersion(Builder $query, int $version): void
    {
        $query->where('version', $version);
    }

    // Accessors
    public function getNombreOriginalFormateadoAttribute(): string
    {
        return ucfirst($this->nombre_original);
    }

    public function getEstadoFormateadoAttribute(): string
    {
        return ucfirst($this->estado);
    }

    public function getTipoArchivoFormateadoAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->tipo_archivo));
    }

    public function getCategoriaFormateadaAttribute(): string
    {
        return ucfirst($this->categoria);
    }

    public function getFechaSubidaFormateadaAttribute(): string
    {
        return $this->fecha_subida ? $this->fecha_subida->format('d/m/Y H:i') : 'N/A';
    }

    public function getFechaModificacionFormateadaAttribute(): string
    {
        return $this->fecha_modificacion ? $this->fecha_modificacion->format('d/m/Y H:i') : 'N/A';
    }

    public function getFechaExpiracionFormateadaAttribute(): string
    {
        return $this->fecha_expiracion ? $this->fecha_expiracion->format('d/m/Y H:i') : 'N/A';
    }

    public function getFechaCapturaFormateadaAttribute(): string
    {
        return $this->fecha_captura ? $this->fecha_captura->format('d/m/Y H:i') : 'N/A';
    }

    public function getDiasTranscurridosAttribute(): int
    {
        return $this->fecha_subida ? $this->fecha_subida->diffInDays(now()) : 0;
    }

    public function getDiasRestantesExpiracionAttribute(): int
    {
        if (!$this->fecha_expiracion) {
            return -1; // Sin fecha de expiración
        }
        
        $dias = $this->fecha_expiracion->diffInDays(now(), false);
        return $dias > 0 ? $dias : 0;
    }

    public function getTamanioFormateadoAttribute(): string
    {
        if (!$this->tamanio_bytes) {
            return 'No especificado';
        }
        
        $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
        $tamanio = $this->tamanio_bytes;
        $unidad = 0;
        
        while ($tamanio >= 1024 && $unidad < count($unidades) - 1) {
            $tamanio /= 1024;
            $unidad++;
        }
        
        return round($tamanio, 2) . ' ' . $unidades[$unidad];
    }

    public function getTamanioKBAttribute(): float
    {
        return $this->tamanio_bytes ? round($this->tamanio_bytes / 1024, 2) : 0;
    }

    public function getTamanioMBAttribute(): float
    {
        return $this->tamanio_bytes ? round($this->tamanio_bytes / (1024 * 1024), 2) : 0;
    }

    public function getExtensionFormateadaAttribute(): string
    {
        return strtoupper($this->extension);
    }

    public function getEsImagenAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function getEsDocumentoAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'application/');
    }

    public function getEsVideoAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    public function getEsAudioAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'audio/');
    }

    public function getEsPDFAttribute(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function getEstaExpiradoAttribute(): bool
    {
        return $this->fecha_expiracion && $this->fecha_expiracion < now();
    }

    public function getPuedeDescargarAttribute(): bool
    {
        return $this->estado === 'activo' && !$this->esta_expirado;
    }

    public function getPuedeEliminarAttribute(): bool
    {
        return $this->estado === 'activo';
    }

    public function getPuedeModificarAttribute(): bool
    {
        return $this->estado === 'activo';
    }

    public function getUrlDescargaAttribute(): string
    {
        if (!$this->puede_descargar) {
            return '';
        }
        
        return route('archivos.descargar', $this->id);
    }

    public function getUrlVistaPreviaAttribute(): string
    {
        if (!$this->es_imagen || !$this->puede_descargar) {
            return '';
        }
        
        return route('archivos.vista-previa', $this->id);
    }

    public function getCalidadFormateadaAttribute(): string
    {
        if (!$this->calidad_estimada) {
            return 'No evaluada';
        }
        
        $porcentaje = round($this->calidad_estimada * 100, 1);
        return $porcentaje . '%';
    }

    public function getResolucionFormateadaAttribute(): string
    {
        if (empty($this->resolucion) || !isset($this->resolucion['ancho']) || !isset($this->resolucion['alto'])) {
            return 'No especificada';
        }
        
        return $this->resolucion['ancho'] . 'x' . $this->resolucion['alto'];
    }

    public function getUbicacionFormateadaAttribute(): string
    {
        if (!$this->ubicacion_geografica) {
            return 'No especificada';
        }
        
        $partes = [$this->ubicacion_geografica];
        
        if ($this->tiene_coordenadas) {
            $partes[] = "({$this->coordenadas_lat}, {$this->coordenadas_lng})";
        }
        
        return implode(' ', $partes);
    }

    public function getTieneCoordenadasAttribute(): bool
    {
        return !is_null($this->coordenadas_lat) && !is_null($this->coordenadas_lng);
    }

    public function getCoordenadasAttribute(): array
    {
        if ($this->tiene_coordenadas) {
            return [
                'lat' => (float) $this->coordenadas_lat,
                'lng' => (float) $this->coordenadas_lng
            ];
        }
        
        return [];
    }

    public function getTagsFormateadosAttribute(): array
    {
        return $this->tags ?? [];
    }

    public function getPalabrasClaveFormateadasAttribute(): array
    {
        return $this->palabras_clave ?? [];
    }

    public function getMetadatosFormateadosAttribute(): array
    {
        return $this->metadatos ?? [];
    }

    // Mutators
    public function setNombreOriginalAttribute($value): void
    {
        $this->attributes['nombre_original'] = ucfirst(strtolower($value));
    }

    public function setTipoArchivoAttribute($value): void
    {
        $this->attributes['tipo_archivo'] = strtolower($value);
    }

    public function setCategoriaAttribute($value): void
    {
        $this->attributes['categoria'] = strtolower($value);
    }

    public function setEstadoAttribute($value): void
    {
        $this->attributes['estado'] = strtolower($value);
    }

    public function setTagsAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['tags'] = array_map('strtolower', $value);
        } else {
            $this->attributes['tags'] = [];
        }
    }

    public function setPalabrasClaveAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['palabras_clave'] = array_map('strtolower', $value);
        } else {
            $this->attributes['palabras_clave'] = [];
        }
    }

    public function setExtensionAttribute($value): void
    {
        $this->attributes['extension'] = strtolower($value);
    }

    // Métodos
    public function cambiarEstado(string $nuevoEstado): bool
    {
        $estadosValidos = ['activo', 'inactivo', 'archivado', 'eliminado'];
        
        if (!in_array($nuevoEstado, $estadosValidos)) {
            return false;
        }

        $estadoAnterior = $this->estado;
        $this->update(['estado' => $nuevoEstado]);

        // Log de auditoría
        LogAuditoria::crear([
            'usuario_id' => auth()->id() ?? 1,
            'tipo_usuario' => auth()->user() ? (auth()->user() instanceof Operador ? 'operador' : 'cliente') : 'sistema',
            'accion' => 'cambiar_estado_archivo',
            'entidad' => 'archivo',
            'entidad_id' => $this->id,
            'datos_anteriores' => ['estado' => $estadoAnterior],
            'datos_nuevos' => ['estado' => $nuevoEstado],
            'ip_cliente' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return true;
    }

    public function actualizarMetadatos(array $nuevosMetadatos): bool
    {
        $metadatosActuales = $this->metadatos ?? [];
        $metadatosActualizados = array_merge($metadatosActuales, $nuevosMetadatos);
        
        $this->update([
            'metadatos' => $metadatosActualizados,
            'fecha_modificacion' => now()
        ]);

        return true;
    }

    public function agregarTag(string $tag): bool
    {
        $tags = $this->tags ?? [];
        if (!in_array(strtolower($tag), $tags)) {
            $tags[] = strtolower($tag);
            $this->update(['tags' => $tags]);
        }
        
        return true;
    }

    public function removerTag(string $tag): bool
    {
        $tags = $this->tags ?? [];
        $tagLower = strtolower($tag);
        
        if (($key = array_search($tagLower, $tags)) !== false) {
            unset($tags[$key]);
            $this->update(['tags' => array_values($tags)]);
        }
        
        return true;
    }

    public function agregarPalabraClave(string $palabra): bool
    {
        $palabras = $this->palabras_clave ?? [];
        if (!in_array(strtolower($palabra), $palabras)) {
            $palabras[] = strtolower($palabra);
            $this->update(['palabras_clave' => $palabras]);
        }
        
        return true;
    }

    public function establecerFechaExpiracion(Carbon $fecha): bool
    {
        if ($fecha <= now()) {
            return false;
        }
        
        $this->update(['fecha_expiracion' => $fecha]);
        return true;
    }

    public function renovarExpiracion(int $diasAdicionales = 30): bool
    {
        $nuevaFecha = now()->addDays($diasAdicionales);
        return $this->establecerFechaExpiracion($nuevaFecha);
    }

    public function generarHashVerificacion(): string
    {
        $datos = [
            'id' => $this->id,
            'codigo_archivo' => $this->codigo_archivo,
            'nombre_original' => $this->nombre_original,
            'tamanio_bytes' => $this->tamanio_bytes,
            'fecha_subida' => $this->fecha_subida->toISOString(),
            'hash_archivo' => $this->hash_archivo
        ];
        
        $hash = hash('sha256', json_encode($datos));
        $this->update(['hash_verificacion' => $hash]);
        
        return $hash;
    }

    public function verificarIntegridad(): bool
    {
        if (!$this->hash_verificacion || !$this->hash_archivo) {
            return false;
        }
        
        $hashCalculado = $this->generarHashVerificacion();
        return $hashCalculado === $this->hash_verificacion;
    }

    public function obtenerEstadisticas(): array
    {
        return [
            'dias_transcurridos' => $this->dias_transcurridos,
            'dias_restantes_expiracion' => $this->dias_restantes_expiracion,
            'tamanio' => $this->tamanio_formateado,
            'tamanio_kb' => $this->tamanio_kb,
            'tamanio_mb' => $this->tamanio_mb,
            'extension' => $this->extension_formateada,
            'es_imagen' => $this->es_imagen,
            'es_documento' => $this->es_documento,
            'es_video' => $this->es_video,
            'es_audio' => $this->es_audio,
            'es_pdf' => $this->es_pdf,
            'esta_expirado' => $this->esta_expirado,
            'puede_descargar' => $this->puede_descargar,
            'puede_eliminar' => $this->puede_eliminar,
            'puede_modificar' => $this->puede_modificar,
            'calidad_estimada' => $this->calidad_formateada,
            'resolucion' => $this->resolucion_formateada,
            'ubicacion' => $this->ubicacion_formateada,
            'total_tags' => count($this->tags ?? []),
            'total_palabras_clave' => count($this->palabras_clave ?? [])
        ];
    }

    public function calcularTamanioDisco(): float
    {
        // Simular cálculo de tamaño en disco (considerando overhead del sistema de archivos)
        $overhead = 1.1; // 10% de overhead
        return $this->tamanio_bytes * $overhead;
    }

    public function obtenerTipoMime(): string
    {
        return $this->mime_type ?? 'application/octet-stream';
    }

    public function obtenerExtensionReal(): string
    {
        return $this->extension ?? pathinfo($this->nombre_original, PATHINFO_EXTENSION);
    }

    // Boot method para eventos del modelo
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($archivo) {
            if (empty($archivo->codigo_archivo)) {
                $archivo->codigo_archivo = static::generarCodigoArchivo();
            }
            
            if (empty($archivo->fecha_subida)) {
                $archivo->fecha_subida = now();
            }
            
            if (empty($archivo->estado)) {
                $archivo->estado = 'activo';
            }
            
            if (empty($archivo->version)) {
                $archivo->version = 1;
            }
        });

        static::updating(function ($archivo) {
            // Log de cambios importantes
            if ($archivo->isDirty('estado')) {
                LogAuditoria::crear([
                    'usuario_id' => auth()->id() ?? 1,
                    'tipo_usuario' => auth()->user() ? (auth()->user() instanceof Operador ? 'operador' : 'cliente') : 'sistema',
                    'accion' => 'cambiar_estado_archivo',
                    'entidad' => 'archivo',
                    'entidad_id' => $archivo->id,
                    'datos_anteriores' => ['estado' => $archivo->getOriginal('estado')],
                    'datos_nuevos' => ['estado' => $archivo->estado],
                    'ip_cliente' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]);
            }
        });
    }

    // Método estático para generar código único
    protected static function generarCodigoArchivo(): string
    {
        do {
            $codigo = 'ARC-' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (static::where('codigo_archivo', $codigo)->exists());

        return $codigo;
    }
}
