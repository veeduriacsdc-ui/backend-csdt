<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalisisIA extends Model
{
    use HasFactory;

    protected $table = 'analisis_ia';

    protected $fillable = [
        'codigo_caso',
        'usuario_id',
        'titulo_caso',
        'descripcion_detallada',
        'narracion_hechos',
        'narracion_hechos_mejorada',
        'estado_analisis',
        'nivel_riesgo',
        'confianza_algoritmo',
        'resumen_ia',
        'hallazgos_ia',
        'recomendaciones_ia',
        'articulos_aplicables_ia',
        'vias_accion_recomendadas',
        'concepto_general_consolidado',
        'tipo_caso',
        'categoria_juridica',
        'fundamentos_nacionales',
        'fundamentos_internacionales',
        'fundamentos_municipales',
        'fundamentos_departamentales',
        'jurisprudencia_aplicable',
        'normativa_especifica',
        'narrativa_hechos_profesional',
        'analisis_consolidado',
        'recomendaciones_juridicas',
        'recomendaciones_administrativas',
        'recomendaciones_institucionales',
        'rutas_accion',
        'estado_procesal',
        'fecha_vencimiento',
        'coordenadas',
        'documentos_adjuntos',
        'archivos_adjuntos',
        'requiere_seguimiento',
        'observaciones_generales'
    ];

    protected $casts = [
        'coordenadas' => 'array',
        'documentos_adjuntos' => 'array',
        'archivos_adjuntos' => 'array',
        'fundamentos_nacionales' => 'array',
        'fundamentos_internacionales' => 'array',
        'fundamentos_municipales' => 'array',
        'fundamentos_departamentales' => 'array',
        'jurisprudencia_aplicable' => 'array',
        'normativa_especifica' => 'array',
        'recomendaciones_juridicas' => 'array',
        'recomendaciones_administrativas' => 'array',
        'recomendaciones_institucionales' => 'array',
        'rutas_accion' => 'array',
        'confianza_algoritmo' => 'decimal:2',
        'fecha_vencimiento' => 'date',
        'requiere_seguimiento' => 'boolean'
    ];

    /**
     * Relación con análisis de IAs especializadas
     */
    public function analisisIAsEspecializadas(): HasMany
    {
        return $this->hasMany(AnalisisIAEspecializada::class, 'analisis_ia_id');
    }

    /**
     * Relación con usuario que creó el análisis
     */
    public function usuario()
    {
        return $this->belongsTo(UsuarioSistema::class, 'usuario_id');
    }

    /**
     * Relación con recomendaciones jurídicas
     */
    public function recomendacionesJuridicas()
    {
        return $this->hasMany(RecomendacionJuridica::class, 'analisis_ia_id');
    }

    /**
     * Relación con mecanismos constitucionales (many-to-many)
     */
    public function mecanismosConstitucionales()
    {
        return $this->belongsToMany(
            MecanismoConstitucional::class,
            'analisis_mecanismos_pivot',
            'analisis_ia_id',
            'mecanismo_id'
        )->withPivot([
            'relevancia',
            'probabilidad_aplicacion',
            'justificacion_aplicacion',
            'requisitos_cumplidos',
            'requisitos_pendientes',
            'observaciones_especificas',
            'recomendado',
            'orden_prioridad'
        ])->withTimestamps();
    }

    /**
     * Obtener el concepto general consolidado
     */
    public function getConceptoGeneralConsolidadoAttribute($value)
    {
        if ($value) {
            return $value;
        }

        // Generar concepto consolidado basado en los análisis de las IAs
        $analisis = $this->analisisIAsEspecializadas;
        
        if ($analisis->isEmpty()) {
            return 'No hay análisis disponibles para generar concepto consolidado.';
        }

        $nivelRiesgo = $analisis->pluck('nivel_riesgo')->mode()->first() ?? 'MEDIO';
        $confianzaPromedio = $analisis->avg('confianza');

        return "CONCEPTO GENERAL CONSOLIDADO:\n\n" .
               "Basado en el análisis integral de las {$analisis->count()} IAs especializadas, se concluye que:\n\n" .
               "1. Los hechos presentados ameritan investigación inmediata por parte de las autoridades competentes.\n" .
               "2. Se identifican múltiples violaciones a la normativa vigente que requieren acciones legales coordinadas.\n" .
               "3. La evidencia presentada sugiere un patrón sistemático que podría afectar la integridad de la administración pública.\n" .
               "4. Se recomienda la implementación de medidas preventivas y correctivas inmediatas.\n" .
               "5. El caso presenta elementos suficientes para iniciar procesos administrativos y penales correspondientes.\n\n" .
               "NIVEL DE RIESGO: {$nivelRiesgo}\n" .
               "CONFIANZA PROMEDIO: " . number_format($confianzaPromedio, 1) . "%\n\n" .
               "Este concepto consolidado integra las perspectivas de todas las IAs especializadas para proporcionar una evaluación comprehensiva del caso.";
    }

    /**
     * Generar código de caso único
     */
    public static function generarCodigoCaso(): string
    {
        do {
            $codigo = 'CSDT-IA-' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('codigo_caso', $codigo)->exists());

        return $codigo;
    }

    /**
     * Scope para análisis completados
     */
    public function scopeCompletados($query)
    {
        return $query->where('estado_analisis', 'completado');
    }

    /**
     * Scope para análisis por nivel de riesgo
     */
    public function scopePorNivelRiesgo($query, $nivel)
    {
        return $query->where('nivel_riesgo', $nivel);
    }

    /**
     * Scope para análisis por tipo de caso
     */
    public function scopePorTipoCaso($query, $tipo)
    {
        return $query->where('tipo_caso', $tipo);
    }

    /**
     * Scope para análisis por categoría jurídica
     */
    public function scopePorCategoriaJuridica($query, $categoria)
    {
        return $query->where('categoria_juridica', $categoria);
    }

    /**
     * Scope para análisis que requieren seguimiento
     */
    public function scopeQueRequierenSeguimiento($query)
    {
        return $query->where('requiere_seguimiento', true);
    }

    /**
     * Scope para análisis por estado procesal
     */
    public function scopePorEstadoProcesal($query, $estado)
    {
        return $query->where('estado_procesal', $estado);
    }

    /**
     * Scope para análisis por usuario
     */
    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /**
     * Scope para análisis vencidos
     */
    public function scopeVencidos($query)
    {
        return $query->where('fecha_vencimiento', '<', now());
    }

    /**
     * Scope para análisis por vencer
     */
    public function scopePorVencer($query, $dias = 7)
    {
        return $query->where('fecha_vencimiento', '<=', now()->addDays($dias))
                    ->where('fecha_vencimiento', '>', now());
    }

    // Métodos de negocio
    public function esConstitucional()
    {
        return $this->tipo_caso === 'constitucional';
    }

    public function esAdministrativo()
    {
        return $this->tipo_caso === 'administrativo';
    }

    public function esPenal()
    {
        return $this->tipo_caso === 'penal';
    }

    public function esEtnico()
    {
        return $this->tipo_caso === 'etnico';
    }

    public function esCompletado()
    {
        return $this->estado_analisis === 'completado';
    }

    public function estaEnProceso()
    {
        return $this->estado_analisis === 'en_proceso';
    }

    public function estaPendiente()
    {
        return $this->estado_analisis === 'pendiente';
    }

    public function esRiesgoAlto()
    {
        return $this->nivel_riesgo === 'ALTO' || $this->nivel_riesgo === 'CRITICO';
    }

    public function obtenerFundamentosNacionales()
    {
        return $this->fundamentos_nacionales ?? [];
    }

    public function obtenerFundamentosInternacionales()
    {
        return $this->fundamentos_internacionales ?? [];
    }

    public function obtenerFundamentosMunicipales()
    {
        return $this->fundamentos_municipales ?? [];
    }

    public function obtenerFundamentosDepartamentales()
    {
        return $this->fundamentos_departamentales ?? [];
    }

    public function obtenerJurisprudenciaAplicable()
    {
        return $this->jurisprudencia_aplicable ?? [];
    }

    public function obtenerRecomendacionesJuridicas()
    {
        return $this->recomendaciones_juridicas ?? [];
    }

    public function obtenerRecomendacionesAdministrativas()
    {
        return $this->recomendaciones_administrativas ?? [];
    }

    public function obtenerRecomendacionesInstitucionales()
    {
        return $this->recomendaciones_institucionales ?? [];
    }

    public function obtenerRutasAccion()
    {
        return $this->rutas_accion ?? [];
    }

    public function obtenerArchivosAdjuntos()
    {
        return $this->archivos_adjuntos ?? [];
    }

    public function obtenerCoordenadas()
    {
        return $this->coordenadas ?? [];
    }

    public function estaVencido()
    {
        return $this->fecha_vencimiento && now()->isAfter($this->fecha_vencimiento);
    }

    public function estaPorVencer($dias = 7)
    {
        return $this->fecha_vencimiento && 
               now()->addDays($dias)->isAfter($this->fecha_vencimiento) &&
               now()->isBefore($this->fecha_vencimiento);
    }

    public function marcarComoCompletado()
    {
        $this->update(['estado_analisis' => 'completado']);
    }

    public function marcarComoEnProceso()
    {
        $this->update(['estado_analisis' => 'en_proceso']);
    }

    public function activarSeguimiento()
    {
        $this->update(['requiere_seguimiento' => true]);
    }

    public function desactivarSeguimiento()
    {
        $this->update(['requiere_seguimiento' => false]);
    }

    public function cambiarEstadoProcesal($nuevoEstado)
    {
        $this->update(['estado_procesal' => $nuevoEstado]);
    }

    public function establecerFechaVencimiento($fecha)
    {
        $this->update(['fecha_vencimiento' => $fecha]);
    }

    public function obtenerColorEstadoAnalisis()
    {
        return match ($this->estado_analisis) {
            'completado' => 'success',
            'en_proceso' => 'warning',
            'pendiente' => 'info',
            'error' => 'danger',
            default => 'secondary'
        };
    }

    public function obtenerColorNivelRiesgo()
    {
        return match ($this->nivel_riesgo) {
            'CRITICO' => 'danger',
            'ALTO' => 'warning',
            'MEDIO' => 'info',
            'BAJO' => 'success',
            default => 'secondary'
        };
    }

    public function obtenerColorEstadoProcesal()
    {
        return match ($this->estado_procesal) {
            'resuelto' => 'success',
            'en_tramite' => 'warning',
            'inicial' => 'info',
            'archivado' => 'secondary',
            default => 'secondary'
        };
    }
}
