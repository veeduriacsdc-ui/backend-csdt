<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Donacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'donaciones';

    protected $fillable = [
        'codigo_donacion',
        'titulo',
        'descripcion',
        'tipo_donacion',
        'categoria',
        'valor_estimado',
        'valor_real',
        'moneda',
        'estado',
        'cliente_id',
        'operador_validador_id',
        'fecha_registro',
        'fecha_validacion',
        'fecha_rechazo',
        'motivo_rechazo',
        'ubicacion',
        'departamento',
        'municipio',
        'direccion_detallada',
        'coordenadas_lat',
        'coordenadas_lng',
        'evidencia_fotografica',
        'documentos_adjuntos',
        'comprobantes_pago',
        'observaciones_validador',
        'comentarios_cliente',
        'tags',
        'nivel_urgencia',
        'impacto_social',
        'beneficiarios_directos',
        'beneficiarios_indirectos',
        'tiempo_entrega_estimado',
        'condiciones_entrega',
        'restricciones_uso',
        'notas_internas',
        'metadatos',
        'hash_verificacion',
        'firma_digital'
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
        'fecha_validacion' => 'datetime',
        'fecha_rechazo' => 'datetime',
        'evidencia_fotografica' => 'array',
        'documentos_adjuntos' => 'array',
        'comprobantes_pago' => 'array',
        'tags' => 'array',
        'beneficiarios_directos' => 'array',
        'beneficiarios_indirectos' => 'array',
        'condiciones_entrega' => 'array',
        'restricciones_uso' => 'array',
        'metadatos' => 'array',
        'coordenadas_lat' => 'decimal:8',
        'coordenadas_lng' => 'decimal:8',
        'valor_estimado' => 'decimal:15,2',
        'valor_real' => 'decimal:15,2',
        'impacto_social' => 'integer',
        'tiempo_entrega_estimado' => 'integer'
    ];

    // Relaciones
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function operadorValidador(): BelongsTo
    {
        return $this->belongsTo(Operador::class, 'operador_validador_id');
    }

    public function archivos(): HasMany
    {
        return $this->hasMany(Archivo::class, 'donacion_id');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'entidad_id')
            ->where('entidad', 'donacion');
    }

    public function logsAuditoria(): HasMany
    {
        return $this->hasMany(LogAuditoria::class, 'entidad_id')
            ->where('entidad', 'donacion');
    }

    // Scopes
    public function scopeActivas(Builder $query): void
    {
        $query->where('estado', '!=', 'rechazada');
    }

    public function scopeValidadas(Builder $query): void
    {
        $query->where('estado', 'validada');
    }

    public function scopePendientes(Builder $query): void
    {
        $query->where('estado', 'pendiente');
    }

    public function scopeEnRevision(Builder $query): void
    {
        $query->where('estado', 'en_revision');
    }

    public function scopeRechazadas(Builder $query): void
    {
        $query->where('estado', 'rechazada');
    }

    public function scopePorTipo(Builder $query, string $tipo): void
    {
        $query->where('tipo_donacion', $tipo);
    }

    public function scopePorCategoria(Builder $query, string $categoria): void
    {
        $query->where('categoria', $categoria);
    }

    public function scopePorEstado(Builder $query, string $estado): void
    {
        $query->where('estado', $estado);
    }

    public function scopePorCliente(Builder $query, int $clienteId): void
    {
        $query->where('cliente_id', $clienteId);
    }

    public function scopePorOperador(Builder $query, int $operadorId): void
    {
        $query->where('operador_validador_id', $operadorId);
    }

    public function scopePorDepartamento(Builder $query, string $departamento): void
    {
        $query->where('departamento', $departamento);
    }

    public function scopePorMunicipio(Builder $query, string $municipio): void
    {
        $query->where('municipio', $municipio);
    }

    public function scopePorRangoValores(Builder $query, float $valorMinimo, float $valorMaximo): void
    {
        $query->whereBetween('valor_estimado', [$valorMinimo, $valorMaximo]);
    }

    public function scopePorRangoFechas(Builder $query, string $fechaInicio, string $fechaFin): void
    {
        $query->whereBetween('fecha_registro', [$fechaInicio, $fechaFin]);
    }

    public function scopePorNivelUrgencia(Builder $query, string $nivel): void
    {
        $query->where('nivel_urgencia', $nivel);
    }

    public function scopeConTags(Builder $query, array $tags): void
    {
        foreach ($tags as $tag) {
            $query->whereJsonContains('tags', $tag);
        }
    }

    public function scopePorMoneda(Builder $query, string $moneda): void
    {
        $query->where('moneda', $moneda);
    }

    public function scopeSinValidar(Builder $query): void
    {
        $query->whereNull('operador_validador_id');
    }

    // Accessors
    public function getTituloFormateadoAttribute(): string
    {
        return ucfirst($this->titulo);
    }

    public function getDescripcionFormateadaAttribute(): string
    {
        return ucfirst($this->descripcion);
    }

    public function getEstadoFormateadoAttribute(): string
    {
        return ucfirst($this->estado);
    }

    public function getTipoDonacionFormateadoAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->tipo_donacion));
    }

    public function getCategoriaFormateadaAttribute(): string
    {
        return ucfirst($this->categoria);
    }

    public function getFechaRegistroFormateadaAttribute(): string
    {
        return $this->fecha_registro ? $this->fecha_registro->format('d/m/Y H:i') : 'N/A';
    }

    public function getFechaValidacionFormateadaAttribute(): string
    {
        return $this->fecha_validacion ? $this->fecha_validacion->format('d/m/Y H:i') : 'N/A';
    }

    public function getFechaRechazoFormateadaAttribute(): string
    {
        return $this->fecha_rechazo ? $this->fecha_rechazo->format('d/m/Y H:i') : 'N/A';
    }

    public function getDiasTranscurridosAttribute(): int
    {
        return $this->fecha_registro ? $this->fecha_registro->diffInDays(now()) : 0;
    }

    public function getDiasDesdeValidacionAttribute(): int
    {
        if (!$this->fecha_validacion) {
            return -1;
        }
        
        return $this->fecha_validacion->diffInDays(now());
    }

    public function getValorEstimadoFormateadoAttribute(): string
    {
        if (!$this->valor_estimado) {
            return 'No especificado';
        }
        
        return number_format($this->valor_estimado, 2) . ' ' . ($this->moneda ?? 'COP');
    }

    public function getValorRealFormateadoAttribute(): string
    {
        if (!$this->valor_real) {
            return 'No especificado';
        }
        
        return number_format($this->valor_real, 2) . ' ' . ($this->moneda ?? 'COP');
    }

    public function getDiferenciaValorAttribute(): float
    {
        if (!$this->valor_estimado || !$this->valor_real) {
            return 0.0;
        }
        
        return $this->valor_real - $this->valor_estimado;
    }

    public function getDiferenciaValorFormateadaAttribute(): string
    {
        $diferencia = $this->diferencia_valor;
        if ($diferencia === 0.0) {
            return 'Sin diferencia';
        }
        
        $signo = $diferencia > 0 ? '+' : '';
        return $signo . number_format($diferencia, 2) . ' ' . ($this->moneda ?? 'COP');
    }

    public function getEstaValidadaAttribute(): bool
    {
        return $this->estado === 'validada';
    }

    public function getEstaRechazadaAttribute(): bool
    {
        return $this->estado === 'rechazada';
    }

    public function getEstaPendienteAttribute(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function getEstaEnRevisionAttribute(): bool
    {
        return $this->estado === 'en_revision';
    }

    public function getPuedeValidarAttribute(): bool
    {
        return in_array($this->estado, ['pendiente', 'en_revision']);
    }

    public function getPuedeRechazarAttribute(): bool
    {
        return in_array($this->estado, ['pendiente', 'en_revision']);
    }

    public function getPuedeDescargarAttribute(): bool
    {
        return $this->estado === 'validada';
    }

    public function getUbicacionFormateadaAttribute(): string
    {
        $partes = [];
        
        if ($this->direccion_detallada) {
            $partes[] = $this->direccion_detallada;
        }
        
        if ($this->municipio) {
            $partes[] = $this->municipio;
        }
        
        if ($this->departamento) {
            $partes[] = $this->departamento;
        }
        
        return implode(', ', $partes);
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

    public function getTotalBeneficiariosAttribute(): int
    {
        $directos = count($this->beneficiarios_directos ?? []);
        $indirectos = count($this->beneficiarios_indirectos ?? []);
        
        return $directos + $indirectos;
    }

    public function getTotalBeneficiariosFormateadoAttribute(): string
    {
        $total = $this->total_beneficiarios;
        
        if ($total === 0) {
            return 'No especificado';
        }
        
        $directos = count($this->beneficiarios_directos ?? []);
        $indirectos = count($this->beneficiarios_indirectos ?? []);
        
        return "{$directos} directos, {$indirectos} indirectos";
    }

    // Mutators
    public function setTituloAttribute($value): void
    {
        $this->attributes['titulo'] = ucfirst(strtolower($value));
    }

    public function setDescripcionAttribute($value): void
    {
        $this->attributes['descripcion'] = ucfirst(strtolower($value));
    }

    public function setTipoDonacionAttribute($value): void
    {
        $this->attributes['tipo_donacion'] = strtolower($value);
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

    public function setMonedaAttribute($value): void
    {
        $this->attributes['moneda'] = strtoupper($value);
    }

    // Métodos
    public function validar(int $operadorId, array $datosValidacion = []): bool
    {
        if (!$this->puede_validar) {
            return false;
        }

        $operador = Operador::find($operadorId);
        if (!$operador || $operador->estado !== 'activo') {
            return false;
        }

        $datosActualizacion = [
            'estado' => 'validada',
            'operador_validador_id' => $operadorId,
            'fecha_validacion' => now()
        ];

        if (!empty($datosValidacion)) {
            $datosActualizacion = array_merge($datosActualizacion, $datosValidacion);
        }

        $this->update($datosActualizacion);
        
        // Crear notificación para el cliente
        Notificacion::crear([
            'usuario_id' => $this->cliente_id,
            'tipo_usuario' => 'cliente',
            'titulo' => 'Donación Validada',
            'mensaje' => "Tu donación '{$this->titulo}' ha sido validada exitosamente",
            'tipo' => 'validacion',
            'entidad' => 'donacion',
            'entidad_id' => $this->id,
            'leida' => false
        ]);

        // Log de auditoría
        LogAuditoria::crear([
            'usuario_id' => $operadorId,
            'tipo_usuario' => 'operador',
            'accion' => 'validar_donacion',
            'entidad' => 'donacion',
            'entidad_id' => $this->id,
            'datos_anteriores' => ['estado' => $this->getOriginal('estado')],
            'datos_nuevos' => ['estado' => 'validada'],
            'ip_cliente' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return true;
    }

    public function rechazar(int $operadorId, string $motivo, array $datosRechazo = []): bool
    {
        if (!$this->puede_rechazar) {
            return false;
        }

        $operador = Operador::find($operadorId);
        if (!$operador || $operador->estado !== 'activo') {
            return false;
        }

        $datosActualizacion = [
            'estado' => 'rechazada',
            'operador_validador_id' => $operadorId,
            'fecha_rechazo' => now(),
            'motivo_rechazo' => $motivo
        ];

        if (!empty($datosRechazo)) {
            $datosActualizacion = array_merge($datosActualizacion, $datosRechazo);
        }

        $this->update($datosActualizacion);
        
        // Crear notificación para el cliente
        Notificacion::crear([
            'usuario_id' => $this->cliente_id,
            'tipo_usuario' => 'cliente',
            'titulo' => 'Donación Rechazada',
            'mensaje' => "Tu donación '{$this->titulo}' ha sido rechazada. Motivo: {$motivo}",
            'tipo' => 'rechazo',
            'entidad' => 'donacion',
            'entidad_id' => $this->id,
            'leida' => false
        ]);

        // Log de auditoría
        LogAuditoria::crear([
            'usuario_id' => $operadorId,
            'tipo_usuario' => 'operador',
            'accion' => 'rechazar_donacion',
            'entidad' => 'donacion',
            'entidad_id' => $this->id,
            'datos_anteriores' => ['estado' => $this->getOriginal('estado')],
            'datos_nuevos' => ['estado' => 'rechazada', 'motivo_rechazo' => $motivo],
            'ip_cliente' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return true;
    }

    public function cambiarEstado(string $nuevoEstado, array $datosAdicionales = []): bool
    {
        $estadosValidos = ['pendiente', 'en_revision', 'validada', 'rechazada', 'suspendida'];
        
        if (!in_array($nuevoEstado, $estadosValidos)) {
            return false;
        }

        $estadoAnterior = $this->estado;
        $datosActualizacion = ['estado' => $nuevoEstado];

        if ($nuevoEstado === 'validada' && !$this->fecha_validacion) {
            $datosActualizacion['fecha_validacion'] = now();
        }

        if ($nuevoEstado === 'rechazada' && !$this->fecha_rechazo) {
            $datosActualizacion['fecha_rechazo'] = now();
        }

        if (!empty($datosAdicionales)) {
            $datosActualizacion = array_merge($datosActualizacion, $datosAdicionales);
        }

        $this->update($datosActualizacion);

        // Log de auditoría
        LogAuditoria::crear([
            'usuario_id' => auth()->id() ?? 1,
            'tipo_usuario' => auth()->user() ? (auth()->user() instanceof Operador ? 'operador' : 'cliente') : 'sistema',
            'accion' => 'cambiar_estado_donacion',
            'entidad' => 'donacion',
            'entidad_id' => $this->id,
            'datos_anteriores' => ['estado' => $estadoAnterior],
            'datos_nuevos' => $datosActualizacion,
            'ip_cliente' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return true;
    }

    public function agregarComentario(string $comentario, string $tipo = 'general'): bool
    {
        $comentarios = $this->comentarios ?? [];
        $comentarios[] = [
            'texto' => $comentario,
            'tipo' => $tipo,
            'fecha' => now()->toISOString(),
            'usuario_id' => auth()->id(),
            'tipo_usuario' => auth()->user() ? (auth()->user() instanceof Operador ? 'operador' : 'cliente') : 'sistema'
        ];
        
        $this->update(['comentarios' => $comentarios]);
        
        return true;
    }

    public function obtenerEstadisticas(): array
    {
        return [
            'dias_transcurridos' => $this->dias_transcurridos,
            'dias_desde_validacion' => $this->dias_desde_validacion,
            'valor_estimado' => $this->valor_estimado_formateado,
            'valor_real' => $this->valor_real_formateado,
            'diferencia_valor' => $this->diferencia_valor_formateada,
            'total_beneficiarios' => $this->total_beneficiarios_formateado,
            'total_archivos' => $this->archivos()->count(),
            'total_comentarios' => count($this->comentarios ?? []),
            'esta_validada' => $this->esta_validada,
            'esta_rechazada' => $this->esta_rechazada,
            'puede_validar' => $this->puede_validar,
            'puede_rechazar' => $this->puede_rechazar,
            'puede_descargar' => $this->puede_descargar
        ];
    }

    public function generarHashVerificacion(): string
    {
        $datos = [
            'id' => $this->id,
            'codigo_donacion' => $this->codigo_donacion,
            'titulo' => $this->titulo,
            'valor_estimado' => $this->valor_estimado,
            'fecha_registro' => $this->fecha_registro->toISOString(),
            'cliente_id' => $this->cliente_id
        ];
        
        $hash = hash('sha256', json_encode($datos));
        $this->update(['hash_verificacion' => $hash]);
        
        return $hash;
    }

    public function verificarIntegridad(): bool
    {
        if (!$this->hash_verificacion) {
            return false;
        }
        
        $hashCalculado = $this->generarHashVerificacion();
        return $hashCalculado === $this->hash_verificacion;
    }

    // Boot method para eventos del modelo
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($donacion) {
            if (empty($donacion->codigo_donacion)) {
                $donacion->codigo_donacion = static::generarCodigoDonacion();
            }
            
            if (empty($donacion->fecha_registro)) {
                $donacion->fecha_registro = now();
            }
            
            if (empty($donacion->estado)) {
                $donacion->estado = 'pendiente';
            }
            
            if (empty($donacion->moneda)) {
                $donacion->moneda = 'COP';
            }
        });

        static::updating(function ($donacion) {
            // Log de cambios importantes
            if ($donacion->isDirty('estado')) {
                LogAuditoria::crear([
                    'usuario_id' => auth()->id() ?? 1,
                    'tipo_usuario' => auth()->user() ? (auth()->user() instanceof Operador ? 'operador' : 'cliente') : 'sistema',
                    'accion' => 'cambiar_estado_donacion',
                    'entidad' => 'donacion',
                    'entidad_id' => $donacion->id,
                    'datos_anteriores' => ['estado' => $donacion->getOriginal('estado')],
                    'datos_nuevos' => ['estado' => $donacion->estado],
                    'ip_cliente' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]);
            }
        });
    }

    // Método estático para generar código único
    protected static function generarCodigoDonacion(): string
    {
        do {
            $codigo = 'DON-' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (static::where('codigo_donacion', $codigo)->exists());

        return $codigo;
    }
}
