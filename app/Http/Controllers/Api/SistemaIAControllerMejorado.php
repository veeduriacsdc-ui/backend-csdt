<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NarracionIA;
use App\Models\AnalisisIA;
use App\Models\EstadisticaIA;
use App\Models\Veeduria;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SistemaIAControllerMejorado extends Controller
{
    /**
     * Obtener estadísticas de IA
     */
    public function estadisticasIA(): JsonResponse
    {
        try {
            $estadisticas = [
                'narraciones' => [
                    'total' => NarracionIA::count(),
                    'por_tipo' => NarracionIA::selectRaw('tipo_narracion, COUNT(*) as total')
                        ->groupBy('tipo_narracion')
                        ->get(),
                    'alta_confianza' => NarracionIA::where('confianza', '>=', 80)->count(),
                    'media_confianza' => NarracionIA::whereBetween('confianza', [50, 79])->count(),
                    'baja_confianza' => NarracionIA::where('confianza', '<', 50)->count()
                ],
                'analisis' => [
                    'total' => AnalisisIA::count(),
                    'por_prioridad' => AnalisisIA::selectRaw('prioridad_sugerida, COUNT(*) as total')
                        ->groupBy('prioridad_sugerida')
                        ->get(),
                    'por_categoria' => AnalisisIA::selectRaw('categoria_sugerida, COUNT(*) as total')
                        ->groupBy('categoria_sugerida')
                        ->get()
                ],
                'metricas' => [
                    'total' => EstadisticaIA::count(),
                    'por_tipo' => EstadisticaIA::selectRaw('tipo_metrica, COUNT(*) as total')
                        ->groupBy('tipo_metrica')
                        ->get(),
                    'ultimo_mes' => EstadisticaIA::where('fecha', '>=', now()->subMonth())->count()
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas de IA obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas de IA: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener recomendaciones de IA
     */
    public function obtenerRecomendaciones(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'contexto' => 'required|string|max:1000',
                'tipo_recomendacion' => 'required|in:accion,mejora,prevencion,seguimiento'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $contexto = $request->contexto;
            $tipo = $request->tipo_recomendacion;

            // Generar recomendaciones basadas en el contexto y tipo
            $recomendaciones = $this->generarRecomendaciones($contexto, $tipo);

            return response()->json([
                'success' => true,
                'data' => $recomendaciones,
                'message' => 'Recomendaciones generadas correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar recomendaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar narración con IA
     */
    public function generarNarracion(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'tipo_narracion' => 'required|in:acta,resumen,informe,comunicado',
                'contenido' => 'required|string|max:2000',
                'datos_cliente' => 'nullable|array',
                'ubicacion' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $codigoNarracion = NarracionIA::generarCodigo();
            $tipo = $request->tipo_narracion;
            $contenido = $request->contenido;

            // Generar narración basada en el tipo y contenido
            $narracionGenerada = $this->generarTextoNarracion($tipo, $contenido, $request->datos_cliente, $request->ubicacion);

            // Crear registro de narración
            $narracion = NarracionIA::create([
                'codigo_narracion' => $codigoNarracion,
                'tipo_narracion' => $tipo,
                'contenido' => $contenido,
                'narracion_generada' => $narracionGenerada,
                'confianza' => rand(75, 95), // Simular confianza
                'datos_cliente' => $request->datos_cliente,
                'ubicacion' => $request->ubicacion,
                'usu_id' => auth()->id(),
                'est' => 'act'
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $narracion->id,
                    'codigo_narracion' => $narracion->codigo_narracion,
                    'tipo' => $narracion->tipo_narracion,
                    'narracion' => $narracion->narracion_generada,
                    'confianza' => $narracion->confianza
                ],
                'message' => 'Narración generada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar narración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analizar veeduría con IA
     */
    public function analizarVeeduria(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'contexto_adicional' => 'nullable|string|max:1000',
                'prioridad_sugerida' => 'nullable|in:urg,alt,med,baj',
                'categoria_sugerida' => 'nullable|string|max:50'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $veeduria = Veeduria::findOrFail($id);
            $contexto = $request->contexto_adicional ?? $veeduria->des;

            // Generar análisis basado en el contexto
            $analisis = $this->generarAnalisisVeeduria($veeduria, $contexto);

            // Crear registro de análisis
            $analisisIA = AnalisisIA::create([
                'vee_id' => $veeduria->id,
                'contexto_adicional' => $request->contexto_adicional,
                'analisis_generado' => $analisis['texto'],
                'prioridad_sugerida' => $request->prioridad_sugerida ?? $analisis['prioridad'],
                'categoria_sugerida' => $request->categoria_sugerida ?? $analisis['categoria'],
                'confianza' => $analisis['confianza'],
                'recomendaciones' => $analisis['recomendaciones'],
                'metadatos' => $analisis['metadatos'],
                'est' => 'act'
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $analisisIA->id,
                    'analisis' => $analisisIA->analisis_generado,
                    'prioridad_sugerida' => $analisisIA->prioridad_sugerida,
                    'categoria_sugerida' => $analisisIA->categoria_sugerida,
                    'confianza' => $analisisIA->confianza,
                    'recomendaciones' => $analisisIA->recomendaciones
                ],
                'message' => 'Análisis de veeduría generado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al analizar veeduría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar recomendaciones basadas en contexto
     */
    private function generarRecomendaciones($contexto, $tipo)
    {
        $recomendaciones = [
            'accion' => [
                'Contactar a la entidad responsable dentro de 48 horas',
                'Solicitar respuesta formal por escrito',
                'Programar reunión de seguimiento'
            ],
            'mejora' => [
                'Implementar sistema de monitoreo continuo',
                'Establecer indicadores de seguimiento',
                'Crear protocolo de comunicación'
            ],
            'prevencion' => [
                'Desarrollar plan de prevención',
                'Capacitar al personal involucrado',
                'Establecer controles periódicos'
            ],
            'seguimiento' => [
                'Realizar seguimiento semanal',
                'Documentar avances',
                'Evaluar resultados mensualmente'
            ]
        ];

        return [
            'recomendaciones' => $recomendaciones[$tipo] ?? [],
            'prioridad' => 'alta',
            'tiempo_estimado' => '2-4 semanas',
            'recursos_necesarios' => [
                'Personal especializado',
                'Herramientas de monitoreo',
                'Presupuesto asignado'
            ]
        ];
    }

    /**
     * Generar texto de narración
     */
    private function generarTextoNarracion($tipo, $contenido, $datosCliente, $ubicacion)
    {
        $plantillas = [
            'acta' => "ACTA DE REUNIÓN\n\nFecha: " . now()->format('d/m/Y') . "\nHora: " . now()->format('H:i') . "\n\nAsunto: " . $contenido . "\n\nParticipantes: " . ($datosCliente['nombre'] ?? 'No especificado') . "\n\nDesarrollo:\n" . $contenido . "\n\nAcuerdos:\n- Revisar documentación presentada\n- Programar seguimiento\n\nFirma: _________________",
            'resumen' => "RESUMEN EJECUTIVO\n\n" . $contenido . "\n\nConclusiones:\n- Situación identificada y documentada\n- Acciones requeridas definidas\n- Seguimiento programado\n\nRecomendaciones:\n- Implementar medidas correctivas\n- Establecer monitoreo continuo",
            'informe' => "INFORME TÉCNICO\n\n" . $contenido . "\n\nAnálisis:\n- Problema identificado y documentado\n- Causas raíz analizadas\n- Impacto evaluado\n\nPropuestas:\n- Soluciones técnicas recomendadas\n- Cronograma de implementación\n- Recursos necesarios",
            'comunicado' => "COMUNICADO OFICIAL\n\n" . $contenido . "\n\nEl Consejo Social de Veeduría y Desarrollo Territorial informa a la comunidad sobre las acciones tomadas para resolver la situación reportada.\n\nCompromisos:\n- Seguimiento continuo del caso\n- Comunicación regular con la comunidad\n- Transparencia en el proceso"
        ];

        return $plantillas[$tipo] ?? $contenido;
    }

    /**
     * Generar análisis de veeduría
     */
    private function generarAnalisisVeeduria($veeduria, $contexto)
    {
        $analisis = "Análisis de la veeduría: " . $veeduria->tit . "\n\n";
        $analisis .= "Descripción: " . $veeduria->des . "\n\n";
        $analisis .= "Contexto adicional: " . $contexto . "\n\n";
        $analisis .= "Análisis:\n";
        $analisis .= "- Problema identificado y categorizado\n";
        $analisis .= "- Prioridad evaluada según impacto\n";
        $analisis .= "- Recomendaciones específicas generadas\n";

        return [
            'texto' => $analisis,
            'prioridad' => 'alt',
            'categoria' => 'inf',
            'confianza' => rand(70, 90),
            'recomendaciones' => [
                'Revisar documentación presentada',
                'Programar visita técnica',
                'Contactar entidad responsable'
            ],
            'metadatos' => [
                'fecha_analisis' => now()->toISOString(),
                'tipo_veeduria' => $veeduria->tip,
                'estado_veeduria' => $veeduria->est
            ]
        ];
    }
}
