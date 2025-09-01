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

class NarracionConsejoIa extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'narraciones_consejo_ia';

    protected $fillable = [
        'codigo_narracion',
        'titulo',
        'narracion_original',
        'narracion_mejorada',
        'tipo_narracion',
        'categoria',
        'estado',
        'cliente_id',
        'fecha_creacion',
        'fecha_mejora',
        'fecha_completada',
        'idioma_original',
        'idioma_mejorado',
        'longitud_original',
        'longitud_mejorada',
        'calidad_estimada',
        'palabras_clave',
        'temas_identificados',
        'sentimiento_analisis',
        'nivel_confianza_ia',
        'sugerencias_mejora',
        'comentarios_cliente',
        'observaciones_ia',
        'tags',
        'metadatos',
        'hash_verificacion',
        'version_ia',
        'parametros_mejora',
        'tiempo_procesamiento',
        'recursos_utilizados',
        'notas_internas'
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'fecha_mejora' => 'datetime',
        'fecha_completada' => 'datetime',
        'palabras_clave' => 'array',
        'temas_identificados' => 'array',
        'sentimiento_analisis' => 'array',
        'sugerencias_mejora' => 'array',
        'tags' => 'array',
        'metadatos' => 'array',
        'parametros_mejora' => 'array',
        'longitud_original' => 'integer',
        'longitud_mejorada' => 'integer',
        'calidad_estimada' => 'decimal:3,2',
        'nivel_confianza_ia' => 'decimal:3,2',
        'tiempo_procesamiento' => 'decimal:8,2'
    ];

    // Relaciones
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function archivos(): HasMany
    {
        return $this->hasMany(Archivo::class, 'narracion_id');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'entidad_id')
            ->where('entidad', 'narracion_consejo_ia');
    }

    public function logsAuditoria(): HasMany
    {
        return $this->hasMany(LogAuditoria::class, 'entidad_id')
            ->where('entidad', 'narracion_consejo_ia');
    }

    // Scopes
    public function scopeActivas(Builder $query): void
    {
        $query->where('estado', '!=', 'completada');
    }

    public function scopeCompletadas(Builder $query): void
    {
        $query->where('estado', 'completada');
    }

    public function scopePendientes(Builder $query): void
    {
        $query->where('estado', 'pendiente');
    }

    public function scopeEnProceso(Builder $query): void
    {
        $query->where('estado', 'en_proceso');
    }

    public function scopePorTipo(Builder $query, string $tipo): void
    {
        $query->where('tipo_narracion', $tipo);
    }

    public function scopePorCategoria(Builder $query, string $categoria): void
    {
        $query->where('categoria', $categoria);
    }

    public function scopePorCliente(Builder $query, int $clienteId): void
    {
        $query->where('cliente_id', $clienteId);
    }

    public function scopePorIdioma(Builder $query, string $idioma): void
    {
        $query->where('idioma_original', $idioma);
    }

    public function scopePorRangoFechas(Builder $query, string $fechaInicio, string $fechaFin): void
    {
        $query->whereBetween('fecha_creacion', [$fechaInicio, $fechaFin]);
    }

    public function scopeConMejoras(Builder $query): void
    {
        $query->whereNotNull('narracion_mejorada');
    }

    public function scopeSinMejoras(Builder $query): void
    {
        $query->whereNull('narracion_mejorada');
    }

    public function scopePorCalidad(Builder $query, float $calidadMinima): void
    {
        $query->where('calidad_estimada', '>=', $calidadMinima);
    }

    public function scopePorConfianza(Builder $query, float $confianzaMinima): void
    {
        $query->where('nivel_confianza_ia', '>=', $confianzaMinima);
    }

    public function scopeConTags(Builder $query, array $tags): void
    {
        foreach ($tags as $tag) {
            $query->whereJsonContains('tags', $tag);
        }
    }

    public function scopePorLongitud(Builder $query, int $longitudMinima, int $longitudMaxima = null): void
    {
        if ($longitudMaxima) {
            $query->whereBetween('longitud_original', [$longitudMinima, $longitudMaxima]);
        } else {
            $query->where('longitud_original', '>=', $longitudMinima);
        }
    }

    // Accessors
    public function getTituloFormateadoAttribute(): string
    {
        return ucfirst($this->titulo);
    }

    public function getEstadoFormateadoAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->estado));
    }

    public function getTipoNarracionFormateadoAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->tipo_narracion));
    }

    public function getCategoriaFormateadaAttribute(): string
    {
        return ucfirst($this->categoria);
    }

    public function getFechaCreacionFormateadaAttribute(): string
    {
        return $this->fecha_creacion ? $this->fecha_creacion->format('d/m/Y H:i') : 'N/A';
    }

    public function getFechaMejoraFormateadaAttribute(): string
    {
        return $this->fecha_mejora ? $this->fecha_mejora->format('d/m/Y H:i') : 'N/A';
    }

    public function getFechaCompletadaFormateadaAttribute(): string
    {
        return $this->fecha_completada ? $this->fecha_completada->format('d/m/Y H:i') : 'N/A';
    }

    public function getDiasTranscurridosAttribute(): int
    {
        return $this->fecha_creacion ? $this->fecha_creacion->diffInDays(now()) : 0;
    }

    public function getDiasDesdeMejoraAttribute(): int
    {
        if (!$this->fecha_mejora) {
            return -1;
        }
        
        return $this->fecha_mejora->diffInDays(now());
    }

    public function getLongitudOriginalFormateadaAttribute(): string
    {
        if (!$this->longitud_original) {
            return 'No calculada';
        }
        
        if ($this->longitud_original < 1000) {
            return $this->longitud_original . ' caracteres';
        } else {
            return number_format($this->longitud_original / 1000, 1) . 'k caracteres';
        }
    }

    public function getLongitudMejoradaFormateadaAttribute(): string
    {
        if (!$this->longitud_mejorada) {
            return 'No disponible';
        }
        
        if ($this->longitud_mejorada < 1000) {
            return $this->longitud_mejorada . ' caracteres';
        } else {
            return number_format($this->longitud_mejorada / 1000, 1) . 'k caracteres';
        }
    }

    public function getCalidadFormateadaAttribute(): string
    {
        if (!$this->calidad_estimada) {
            return 'No evaluada';
        }
        
        $porcentaje = round($this->calidad_estimada * 100, 1);
        return $porcentaje . '%';
    }

    public function getConfianzaFormateadaAttribute(): string
    {
        if (!$this->nivel_confianza_ia) {
            return 'No calculada';
        }
        
        $porcentaje = round($this->nivel_confianza_ia * 100, 1);
        return $porcentaje . '%';
    }

    public function getTiempoProcesamientoFormateadoAttribute(): string
    {
        if (!$this->tiempo_procesamiento) {
            return 'No registrado';
        }
        
        if ($this->tiempo_procesamiento < 60) {
            return round($this->tiempo_procesamiento, 2) . ' segundos';
        } else {
            return round($this->tiempo_procesamiento / 60, 2) . ' minutos';
        }
    }

    public function getTieneMejorasAttribute(): bool
    {
        return !empty($this->narracion_mejorada);
    }

    public function getEstaCompletadaAttribute(): bool
    {
        return $this->estado === 'completada';
    }

    public function getEstaPendienteAttribute(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function getEstaEnProcesoAttribute(): bool
    {
        return $this->estado === 'en_proceso';
    }

    public function getPuedeMejorarAttribute(): bool
    {
        return in_array($this->estado, ['pendiente', 'en_proceso']);
    }

    public function getPuedeCompletarAttribute(): bool
    {
        return $this->tiene_mejoras && $this->estado !== 'completada';
    }

    public function getMejoraPorcentualAttribute(): float
    {
        if (!$this->longitud_original || !$this->longitud_mejorada) {
            return 0.0;
        }
        
        $diferencia = $this->longitud_mejorada - $this->longitud_original;
        return round(($diferencia / $this->longitud_original) * 100, 2);
    }

    public function getMejoraPorcentualFormateadaAttribute(): string
    {
        $porcentaje = $this->mejora_porcentual;
        
        if ($porcentaje === 0.0) {
            return 'Sin cambios';
        }
        
        $signo = $porcentaje > 0 ? '+' : '';
        return $signo . $porcentaje . '%';
    }

    public function getPalabrasClaveFormateadasAttribute(): array
    {
        return $this->palabras_clave ?? [];
    }

    public function getTemasIdentificadosFormateadosAttribute(): array
    {
        return $this->temas_identificados ?? [];
    }

    public function getSentimientoFormateadoAttribute(): string
    {
        if (empty($this->sentimiento_analisis)) {
            return 'No analizado';
        }
        
        $sentimiento = $this->sentimiento_analisis['sentimiento'] ?? 'neutral';
        $confianza = $this->sentimiento_analisis['confianza'] ?? 0;
        
        return ucfirst($sentimiento) . ' (' . round($confianza * 100, 1) . '%)';
    }

    // Mutators
    public function setTituloAttribute($value): void
    {
        $this->attributes['titulo'] = ucfirst(strtolower($value));
    }

    public function setTipoNarracionAttribute($value): void
    {
        $this->attributes['tipo_narracion'] = strtolower($value);
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

    public function setNarracionOriginalAttribute($value): void
    {
        $this->attributes['narracion_original'] = $value;
        $this->attributes['longitud_original'] = strlen($value);
    }

    public function setNarracionMejoradaAttribute($value): void
    {
        $this->attributes['narracion_mejorada'] = $value;
        if ($value) {
            $this->attributes['longitud_mejorada'] = strlen($value);
        }
    }

    // Métodos
    public function mejorarConIa(array $parametros = []): bool
    {
        if (!$this->puede_mejorar) {
            return false;
        }

        $this->update(['estado' => 'en_proceso']);

        // Simular proceso de mejora con IA
        $narracionMejorada = $this->simularMejoraIa($this->narracion_original, $this->tipo_narracion);
        
        $datosActualizacion = [
            'narracion_mejorada' => $narracionMejorada,
            'estado' => 'en_proceso',
            'fecha_mejora' => now(),
            'version_ia' => '1.0.0',
            'parametros_mejora' => $parametros,
            'tiempo_procesamiento' => rand(1, 30) + (rand(0, 100) / 100), // Simular tiempo de procesamiento
            'nivel_confianza_ia' => rand(70, 95) / 100, // Simular nivel de confianza
            'calidad_estimada' => rand(75, 95) / 100 // Simular calidad estimada
        ];

        // Generar análisis adicionales
        $datosActualizacion['palabras_clave'] = $this->extraerPalabrasClave($narracionMejorada);
        $datosActualizacion['temas_identificados'] = $this->identificarTemas($narracionMejorada);
        $datosActualizacion['sentimiento_analisis'] = $this->analizarSentimiento($narracionMejorada);
        $datosActualizacion['sugerencias_mejora'] = $this->generarSugerencias($narracionMejorada);

        $this->update($datosActualizacion);

        // Log de auditoría
        LogAuditoria::crear([
            'usuario_id' => $this->cliente_id,
            'tipo_usuario' => 'cliente',
            'accion' => 'mejorar_narracion_ia',
            'entidad' => 'narracion_consejo_ia',
            'entidad_id' => $this->id,
            'datos_anteriores' => ['estado' => 'pendiente'],
            'datos_nuevos' => ['estado' => 'en_proceso', 'fecha_mejora' => now()],
            'ip_cliente' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return true;
    }

    public function completarNarracion(): bool
    {
        if (!$this->puede_completar) {
            return false;
        }

        $this->update([
            'estado' => 'completada',
            'fecha_completada' => now()
        ]);

        // Log de auditoría
        LogAuditoria::crear([
            'usuario_id' => $this->cliente_id,
            'tipo_usuario' => 'cliente',
            'accion' => 'completar_narracion',
            'entidad' => 'narracion_consejo_ia',
            'entidad_id' => $this->id,
            'datos_anteriores' => ['estado' => $this->getOriginal('estado')],
            'datos_nuevos' => ['estado' => 'completada'],
            'ip_cliente' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return true;
    }

    public function cambiarEstado(string $nuevoEstado): bool
    {
        $estadosValidos = ['pendiente', 'en_proceso', 'en_revision', 'completada', 'cancelada'];
        
        if (!in_array($nuevoEstado, $estadosValidos)) {
            return false;
        }

        $estadoAnterior = $this->estado;
        $this->update(['estado' => $nuevoEstado]);

        // Log de auditoría
        LogAuditoria::crear([
            'usuario_id' => auth()->id() ?? $this->cliente_id,
            'tipo_usuario' => auth()->user() ? (auth()->user() instanceof Operador ? 'operador' : 'cliente') : 'cliente',
            'accion' => 'cambiar_estado_narracion',
            'entidad' => 'narracion_consejo_ia',
            'entidad_id' => $this->id,
            'datos_anteriores' => ['estado' => $estadoAnterior],
            'datos_nuevos' => ['estado' => $nuevoEstado],
            'ip_cliente' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return true;
    }

    public function agregarComentario(string $comentario, string $tipo = 'general'): bool
    {
        $comentarios = $this->comentarios_cliente ?? [];
        $comentarios[] = [
            'texto' => $comentario,
            'tipo' => $tipo,
            'fecha' => now()->toISOString(),
            'usuario_id' => auth()->id() ?? $this->cliente_id,
            'tipo_usuario' => 'cliente'
        ];
        
        $this->update(['comentarios_cliente' => $comentarios]);
        
        return true;
    }

    public function obtenerEstadisticas(): array
    {
        return [
            'dias_transcurridos' => $this->dias_transcurridos,
            'dias_desde_mejora' => $this->dias_desde_mejora,
            'longitud_original' => $this->longitud_original_formateada,
            'longitud_mejorada' => $this->longitud_mejorada_formateada,
            'mejora_porcentual' => $this->mejora_porcentual_formateada,
            'calidad_estimada' => $this->calidad_formateada,
            'nivel_confianza' => $this->confianza_formateada,
            'tiempo_procesamiento' => $this->tiempo_procesamiento_formateado,
            'tiene_mejoras' => $this->tiene_mejoras,
            'esta_completada' => $this->esta_completada,
            'puede_mejorar' => $this->puede_mejorar,
            'puede_completar' => $this->puede_completar,
            'total_archivos' => $this->archivos()->count(),
            'total_comentarios' => count($this->comentarios_cliente ?? [])
        ];
    }

    public function generarHashVerificacion(): string
    {
        $datos = [
            'id' => $this->id,
            'codigo_narracion' => $this->codigo_narracion,
            'titulo' => $this->titulo,
            'narracion_original' => $this->narracion_original,
            'fecha_creacion' => $this->fecha_creacion->toISOString(),
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

    // Métodos privados para simulación de IA
    private function simularMejoraIa(string $texto, string $tipo): string
    {
        $mejoras = [
            'correccion_gramatical' => 'Se han corregido errores gramaticales y ortográficos.',
            'mejora_fluidez' => 'Se ha mejorado la fluidez y coherencia del texto.',
            'ampliacion_contenido' => 'Se ha ampliado el contenido con información adicional relevante.',
            'reestructuracion' => 'Se ha reestructurado el texto para mejor comprensión.',
            'enriquecimiento_vocabulario' => 'Se ha enriquecido el vocabulario utilizado.'
        ];

        $mejora = $mejoras[$tipo] ?? 'Se ha mejorado el texto utilizando técnicas de procesamiento de lenguaje natural.';
        
        return $texto . "\n\n[MEJORA APLICADA: {$mejora}]";
    }

    private function extraerPalabrasClave(string $texto): array
    {
        $palabras = str_word_count(strtolower($texto), 1, 'áéíóúñü');
        $palabras = array_filter($palabras, function($palabra) {
            return strlen($palabra) > 3;
        });
        
        $frecuencia = array_count_values($palabras);
        arsort($frecuencia);
        
        return array_slice(array_keys($frecuencia), 0, 10);
    }

    private function identificarTemas(string $texto): array
    {
        $temas = ['general', 'tecnologia', 'salud', 'educacion', 'medio_ambiente', 'social', 'economia'];
        $temasIdentificados = [];
        
        foreach ($temas as $tema) {
            if (rand(0, 1)) {
                $temasIdentificados[] = $tema;
            }
        }
        
        return empty($temasIdentificados) ? ['general'] : $temasIdentificados;
    }

    private function analizarSentimiento(string $texto): array
    {
        $sentimientos = ['positivo', 'negativo', 'neutral'];
        $sentimiento = $sentimientos[array_rand($sentimientos)];
        $confianza = rand(60, 95) / 100;
        
        return [
            'sentimiento' => $sentimiento,
            'confianza' => $confianza,
            'intensidad' => rand(1, 5)
        ];
    }

    private function generarSugerencias(string $texto): array
    {
        $sugerencias = [
            'Considerar agregar más detalles específicos',
            'Revisar la estructura de párrafos',
            'Incluir ejemplos prácticos',
            'Mejorar la conclusión',
            'Agregar transiciones entre ideas'
        ];
        
        return array_slice($sugerencias, 0, rand(2, 4));
    }

    // Boot method para eventos del modelo
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($narracion) {
            if (empty($narracion->codigo_narracion)) {
                $narracion->codigo_narracion = static::generarCodigoNarracion();
            }
            
            if (empty($narracion->fecha_creacion)) {
                $narracion->fecha_creacion = now();
            }
            
            if (empty($narracion->estado)) {
                $narracion->estado = 'pendiente';
            }
            
            if (empty($narracion->idioma_original)) {
                $narracion->idioma_original = 'es';
            }
        });

        static::updating(function ($narracion) {
            // Log de cambios importantes
            if ($narracion->isDirty('estado')) {
                LogAuditoria::crear([
                    'usuario_id' => auth()->id() ?? $narracion->cliente_id,
                    'tipo_usuario' => auth()->user() ? (auth()->user() instanceof Operador ? 'operador' : 'cliente') : 'cliente',
                    'accion' => 'cambiar_estado_narracion',
                    'entidad' => 'narracion_consejo_ia',
                    'entidad_id' => $narracion->id,
                    'datos_anteriores' => ['estado' => $narracion->getOriginal('estado')],
                    'datos_nuevos' => ['estado' => $narracion->estado],
                    'ip_cliente' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]);
            }
        });
    }

    // Método estático para generar código único
    protected static function generarCodigoNarracion(): string
    {
        do {
            $codigo = 'NAR-' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (static::where('codigo_narracion', $codigo)->exists());

        return $codigo;
    }
}
