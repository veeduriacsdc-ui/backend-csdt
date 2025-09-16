<?php

namespace App\Http\Controllers;

use App\Models\AnalisisIA;
use App\Models\AnalisisIAEspecializada;
use App\Models\IADisponible;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalisisIAController extends Controller
{
    /**
     * Crear nuevo análisis de IA
     */
    public function crearAnalisis(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'narracion_hechos' => 'required|string|max:10000',
                'coordenadas' => 'nullable|array',
                'documentos_adjuntos' => 'nullable|array'
            ]);

            DB::beginTransaction();

            // Crear análisis principal
            $analisis = AnalisisIA::create([
                'codigo_caso' => AnalisisIA::generarCodigoCaso(),
                'narracion_hechos' => $request->narracion_hechos,
                'estado_analisis' => 'en_proceso',
                'nivel_riesgo' => 'MEDIO',
                'confianza_algoritmo' => 0.00,
                'coordenadas' => $request->coordenadas,
                'documentos_adjuntos' => $request->documentos_adjuntos
            ]);

            // Obtener IAs disponibles
            $iasDisponibles = IADisponible::activas()->get();

            // Crear análisis para cada IA especializada
            foreach ($iasDisponibles as $ia) {
                $analisisEspecializada = AnalisisIAEspecializada::create([
                    'analisis_ia_id' => $analisis->id,
                    'ia_id' => $ia->ia_id,
                    'nombre_ia' => $ia->nombre,
                    'especialidad' => $ia->especialidad,
                    'narracion_hechos' => $this->generarNarracionHechos($ia->especialidad),
                    'fundamentos_juridicos' => $this->generarFundamentosJuridicos($ia->especialidad),
                    'concepto_general' => $this->generarConceptoGeneral($ia->especialidad),
                    'confianza' => rand(80, 100),
                    'nivel_riesgo' => $this->generarNivelRiesgo(),
                    'metadatos' => [
                        'version_ia' => $ia->version,
                        'tiempo_procesamiento' => rand(5, 30),
                        'fuentes_consultadas' => $ia->configuracion['fuentes'] ?? []
                    ],
                    'fecha_analisis' => now()
                ]);
            }

            // Actualizar análisis principal con datos consolidados
            $this->actualizarAnalisisConsolidado($analisis);

            DB::commit();

            return response()->json([
                'success' => true,
                'mensaje' => 'Análisis creado exitosamente',
                'analisis' => $analisis->load('analisisIAsEspecializadas')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creando análisis de IA: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al crear el análisis: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener análisis por ID
     */
    public function obtenerAnalisis(int $id): JsonResponse
    {
        try {
            $analisis = AnalisisIA::with('analisisIAsEspecializadas')->findOrFail($id);

            return response()->json([
                'success' => true,
                'analisis' => $analisis
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo análisis: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al obtener el análisis: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener lista de análisis
     */
    public function listarAnalisis(Request $request): JsonResponse
    {
        try {
            $query = AnalisisIA::with('analisisIAsEspecializadas');

            // Filtros
            if ($request->has('estado_analisis')) {
                $query->where('estado_analisis', $request->estado_analisis);
            }

            if ($request->has('nivel_riesgo')) {
                $query->where('nivel_riesgo', $request->nivel_riesgo);
            }

            if ($request->has('fecha_desde')) {
                $query->whereDate('created_at', '>=', $request->fecha_desde);
            }

            if ($request->has('fecha_hasta')) {
                $query->whereDate('created_at', '<=', $request->fecha_hasta);
            }

            $analisis = $query->orderBy('created_at', 'desc')
                            ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'analisis' => $analisis
            ]);

        } catch (\Exception $e) {
            Log::error('Error listando análisis: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al listar los análisis: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar reporte PDF del análisis
     */
    public function generarReportePDF(int $id): JsonResponse
    {
        try {
            $analisis = AnalisisIA::with('analisisIAsEspecializadas')->findOrFail($id);

            // Aquí se implementaría la generación real del PDF
            // Por ahora retornamos los datos para generación en frontend
            $reporte = [
                'codigo_caso' => $analisis->codigo_caso,
                'fecha_analisis' => $analisis->created_at->format('d/m/Y H:i:s'),
                'nivel_riesgo' => $analisis->nivel_riesgo,
                'confianza_promedio' => $analisis->confianza_algoritmo,
                'narracion_hechos' => $analisis->narracion_hechos,
                'concepto_general_consolidado' => $analisis->concepto_general_consolidado,
                'analisis_ias' => $analisis->analisisIAsEspecializadas->map(function ($ia) {
                    return [
                        'nombre' => $ia->nombre_ia,
                        'especialidad' => $ia->especialidad,
                        'narracion_hechos' => $ia->narracion_hechos,
                        'fundamentos_juridicos' => $ia->fundamentos_juridicos,
                        'concepto_general' => $ia->concepto_general,
                        'confianza' => $ia->confianza,
                        'nivel_riesgo' => $ia->nivel_riesgo
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'reporte' => $reporte
            ]);

        } catch (\Exception $e) {
            Log::error('Error generando reporte PDF: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al generar el reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar análisis consolidado
     */
    private function actualizarAnalisisConsolidado(AnalisisIA $analisis): void
    {
        $analisisEspecializadas = $analisis->analisisIAsEspecializadas;
        
        $nivelRiesgo = $analisisEspecializadas->pluck('nivel_riesgo')->mode()->first() ?? 'MEDIO';
        $confianzaPromedio = $analisisEspecializadas->avg('confianza');

        $analisis->update([
            'estado_analisis' => 'completado',
            'nivel_riesgo' => $nivelRiesgo,
            'confianza_algoritmo' => $confianzaPromedio,
            'resumen_ia' => 'Análisis completado con ' . $analisisEspecializadas->count() . ' IAs especializadas',
            'hallazgos_ia' => 'Hallazgos consolidados de todas las IAs especializadas',
            'recomendaciones_ia' => 'Recomendaciones integradas de todas las IAs',
            'articulos_aplicables_ia' => 'Artículos aplicables identificados por las IAs',
            'vias_accion_recomendadas' => 'Vías de acción recomendadas por las IAs'
        ]);
    }

    /**
     * Generar narración de hechos
     */
    private function generarNarracionHechos(string $especialidad): string
    {
        $narraciones = [
            'Derecho Colombiano' => 'Los hechos presentados evidencian una posible violación a las normas constitucionales y legales colombianas, específicamente en materia de contratación pública y transparencia administrativa.',
            'Base de Datos Jurídica' => 'El análisis de la base de datos jurídica revela patrones similares de irregularidades en casos precedentes, sugiriendo un posible patrón sistemático de conducta.',
            'Derecho Comparado' => 'Comparando con jurisprudencia internacional, se identifican elementos que podrían constituir violaciones a principios universales de transparencia y buen gobierno.',
            'Derecho Informático' => 'Los aspectos técnicos e informáticos del caso presentan vulnerabilidades que podrían ser explotadas para cometer irregularidades en sistemas digitales.',
            'Análisis Geográfico' => 'La ubicación geográfica del caso presenta características que requieren análisis especializado en normativa territorial y ambiental.',
            'Cartografía Legal' => 'El mapeo legal del territorio involucrado muestra zonas de riesgo que deben ser consideradas en el análisis jurídico.'
        ];

        return $narraciones[$especialidad] ?? 'Análisis especializado realizado según la competencia de la IA.';
    }

    /**
     * Generar fundamentos jurídicos
     */
    private function generarFundamentosJuridicos(string $especialidad): string
    {
        $fundamentos = [
            'Derecho Colombiano' => 'Artículo 209 de la Constitución Política, Ley 80 de 1993, Decreto 1082 de 2015, Código Penal Artículo 410.',
            'Base de Datos Jurídica' => 'Sentencia C-117 de 2018, Ley 1474 de 2011, Decreto 4170 de 2011, Circular 001 de 2019.',
            'Derecho Comparado' => 'Convención Interamericana contra la Corrupción, Convención de las Naciones Unidas contra la Corrupción.',
            'Derecho Informático' => 'Ley 1273 de 2009, Ley 1581 de 2012, Decreto 1377 de 2013, Circular Externa 002 de 2015.',
            'Análisis Geográfico' => 'Ley 99 de 1993, Decreto 1076 de 2015, Resolución 1023 de 2014, Ley 1450 de 2011.',
            'Cartografía Legal' => 'Decreto 360 de 2017, Resolución 1081 de 2015, Ley 388 de 1997, Decreto 1077 de 2015.'
        ];

        return $fundamentos[$especialidad] ?? 'Fundamentos jurídicos aplicables según la especialidad.';
    }

    /**
     * Generar concepto general
     */
    private function generarConceptoGeneral(string $especialidad): string
    {
        $conceptos = [
            'Derecho Colombiano' => 'Se recomienda iniciar acciones legales inmediatas bajo el marco normativo colombiano, considerando la gravedad de los hechos y su impacto en la administración pública.',
            'Base de Datos Jurídica' => 'Los precedentes jurisprudenciales sugieren una alta probabilidad de éxito en las acciones legales propuestas, basándose en casos similares resueltos favorablemente.',
            'Derecho Comparado' => 'La experiencia internacional indica que este tipo de casos requiere un enfoque multidisciplinario y coordinación interinstitucional para su resolución efectiva.',
            'Derecho Informático' => 'Se identifican vulnerabilidades críticas que requieren medidas de seguridad inmediatas y posible investigación forense digital especializada.',
            'Análisis Geográfico' => 'El análisis territorial revela impactos ambientales y sociales que deben ser considerados en la evaluación integral del caso.',
            'Cartografía Legal' => 'La cartografía legal del área involucrada muestra restricciones y regulaciones específicas que influyen en la viabilidad de las acciones propuestas.'
        ];

        return $conceptos[$especialidad] ?? 'Concepto general basado en el análisis especializado de la IA.';
    }

    /**
     * Generar nivel de riesgo aleatorio
     */
    private function generarNivelRiesgo(): string
    {
        $niveles = ['BAJO', 'MEDIO', 'ALTO', 'CRITICO'];
        return $niveles[array_rand($niveles)];
    }
}
