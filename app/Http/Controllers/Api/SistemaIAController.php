<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalisisIA;
use App\Models\NarracionIA;
use App\Models\EstadisticaIA;
use App\Models\Veeduria;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SistemaIAController extends Controller
{
    /**
     * Analizar veeduría con IA
     */
    public function analizarVeeduria(Request $request, $veeduriaId): JsonResponse
    {
        try {
            $veeduria = Veeduria::findOrFail($veeduriaId);
            $usuario = $request->user();

            $validator = Validator::make($request->all(), [
                'tipo_analisis' => 'required|string|in:clasificacion,prioridad,categoria,recomendaciones',
                'texto_adicional' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Simular análisis de IA (aquí se integraría con OpenAI, Claude, etc.)
            $resultadoIA = $this->simularAnalisisIA($veeduria, $request->tipo_analisis, $request->texto_adicional);

            // Guardar análisis en la base de datos
            $analisis = AnalisisIA::create([
                'usu_id' => $usuario->id,
                'vee_id' => $veeduria->id,
                'tip' => $request->tipo_analisis,
                'tex' => $veeduria->des . ' ' . ($request->texto_adicional ?? ''),
                'res' => $resultadoIA,
                'con' => $resultadoIA['confianza'] ?? 0.85,
                'est' => 'com'
            ]);

            // Actualizar veeduría con recomendaciones de IA
            if (isset($resultadoIA['recomendaciones'])) {
                $veeduria->agregarRecomendacionIA($resultadoIA['recomendaciones']);
            }

            // Log de análisis
            Log::crear('analizar_veeduria_ia', 'veedurias', $veeduriaId, 
                      "Veeduría analizada con IA por {$usuario->nombre_completo}");

            return response()->json([
                'success' => true,
                'data' => $analisis,
                'message' => 'Análisis de IA completado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al analizar veeduría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar narración del consejo con IA
     */
    public function generarNarracion(Request $request): JsonResponse
    {
        try {
            $usuario = $request->user();

            $validator = Validator::make($request->all(), [
                'tipo_narracion' => 'required|string|in:acta,resumen,informe,comunicado',
                'contenido' => 'required|string|max:5000',
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

            // Simular generación de narración con IA
            $narracionIA = $this->simularGeneracionNarracion(
                $request->tipo_narracion,
                $request->contenido,
                $request->datos_cliente,
                $request->ubicacion
            );

            // Guardar narración en la base de datos
            $narracion = NarracionConsejoIa::create([
                'usu_id' => $usuario->id,
                'cod' => 'NAR-' . now()->format('YmdHis'),
                'tex' => $narracionIA['texto'],
                'dat_cli' => $request->datos_cliente,
                'ubi' => $request->ubicacion,
                'res_ai' => $narracionIA,
                'est' => 'com'
            ]);

            // Log de generación
            Log::crear('generar_narracion_ia', 'narraciones', $narracion->id, 
                      "Narración generada con IA por {$usuario->nombre_completo}");

            return response()->json([
                'success' => true,
                'data' => $narracion,
                'message' => 'Narración generada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar narración: ' . $e->getMessage()
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
                'contexto' => 'required|string|max:2000',
                'tipo_recomendacion' => 'required|string|in:accion,mejora,prevencion,seguimiento'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Simular recomendaciones de IA
            $recomendaciones = $this->simularRecomendacionesIA(
                $request->contexto,
                $request->tipo_recomendacion
            );

            return response()->json([
                'success' => true,
                'data' => $recomendaciones,
                'message' => 'Recomendaciones obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener recomendaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de IA
     */
    public function estadisticasIA(): JsonResponse
    {
        try {
            $estadisticas = [
                'analisis_totales' => AnalisisIA::count(),
                'analisis_por_tipo' => AnalisisIA::selectRaw('tip, COUNT(*) as total')
                    ->groupBy('tip')
                    ->get(),
                'narraciones_totales' => NarracionConsejoIa::count(),
                'narraciones_por_tipo' => NarracionConsejoIa::selectRaw('SUBSTRING(cod, 1, 3) as tipo, COUNT(*) as total')
                    ->groupBy('tipo')
                    ->get(),
                'confianza_promedio' => AnalisisIA::avg('con'),
                'analisis_recientes' => AnalisisIA::where('created_at', '>=', now()->subDays(7))->count(),
                'narraciones_recientes' => NarracionConsejoIa::where('created_at', '>=', now()->subDays(7))->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas de IA obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Simular análisis de IA
     */
    private function simularAnalisisIA($veeduria, $tipoAnalisis, $textoAdicional = null)
    {
        $textoCompleto = $veeduria->des . ' ' . ($textoAdicional ?? '');
        
        switch ($tipoAnalisis) {
            case 'clasificacion':
                return [
                    'tipo_detectado' => $this->clasificarTipoVeeduria($textoCompleto),
                    'confianza' => 0.92,
                    'razones' => [
                        'Palabras clave detectadas',
                        'Contexto del problema',
                        'Patrón de lenguaje'
                    ]
                ];
                
            case 'prioridad':
                return [
                    'prioridad_sugerida' => $this->determinarPrioridad($textoCompleto),
                    'confianza' => 0.88,
                    'factores' => [
                        'Urgencia del problema',
                        'Impacto en la comunidad',
                        'Recursos necesarios'
                    ]
                ];
                
            case 'categoria':
                return [
                    'categoria_sugerida' => $this->categorizarVeeduria($textoCompleto),
                    'confianza' => 0.85,
                    'subcategorias' => [
                        'Infraestructura vial',
                        'Servicios públicos',
                        'Seguridad ciudadana'
                    ]
                ];
                
            case 'recomendaciones':
                return [
                    'recomendaciones' => $this->generarRecomendaciones($textoCompleto),
                    'confianza' => 0.90,
                    'acciones_sugeridas' => [
                        'Contactar entidad responsable',
                        'Solicitar información adicional',
                        'Programar visita técnica'
                    ]
                ];
                
            default:
                return [
                    'error' => 'Tipo de análisis no válido',
                    'confianza' => 0.0
                ];
        }
    }

    /**
     * Simular generación de narración
     */
    private function simularGeneracionNarracion($tipo, $contenido, $datosCliente = null, $ubicacion = null)
    {
        $plantillas = [
            'acta' => "ACTA DE REUNIÓN DEL CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL\n\nFecha: " . now()->format('d/m/Y') . "\nHora: " . now()->format('H:i') . "\n\nContenido: {$contenido}\n\n",
            'resumen' => "RESUMEN EJECUTIVO\n\nEl Consejo Social de Veeduría y Desarrollo Territorial presenta el siguiente resumen:\n\n{$contenido}\n\n",
            'informe' => "INFORME TÉCNICO\n\nFecha de elaboración: " . now()->format('d/m/Y') . "\n\nDescripción: {$contenido}\n\n",
            'comunicado' => "COMUNICADO OFICIAL\n\nEl Consejo Social de Veeduría y Desarrollo Territorial informa:\n\n{$contenido}\n\n"
        ];

        return [
            'texto' => $plantillas[$tipo] ?? $contenido,
            'tipo' => $tipo,
            'fecha_generacion' => now()->toISOString(),
            'version' => '1.0'
        ];
    }

    /**
     * Simular recomendaciones de IA
     */
    private function simularRecomendacionesIA($contexto, $tipoRecomendacion)
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
            'recomendaciones' => $recomendaciones[$tipoRecomendacion] ?? [],
            'prioridad' => 'alta',
            'tiempo_estimado' => '2-4 semanas',
            'recursos_necesarios' => ['Personal técnico', 'Recursos financieros', 'Herramientas de monitoreo']
        ];
    }

    /**
     * Clasificar tipo de veeduría
     */
    private function clasificarTipoVeeduria($texto)
    {
        $palabrasClave = [
            'pet' => ['solicitud', 'petición', 'pedir', 'solicitar'],
            'que' => ['queja', 'malestar', 'insatisfacción', 'protesta'],
            'rec' => ['reclamo', 'reclamar', 'exigir', 'demandar'],
            'sug' => ['sugerencia', 'propuesta', 'recomendación', 'sugerir'],
            'fel' => ['felicitación', 'agradecimiento', 'reconocimiento', 'felicitar'],
            'den' => ['denuncia', 'denunciar', 'reportar', 'delito']
        ];

        foreach ($palabrasClave as $tipo => $palabras) {
            foreach ($palabras as $palabra) {
                if (stripos($texto, $palabra) !== false) {
                    return $tipo;
                }
            }
        }

        return 'pet'; // Por defecto
    }

    /**
     * Determinar prioridad
     */
    private function determinarPrioridad($texto)
    {
        $palabrasUrgentes = ['urgente', 'emergencia', 'inmediato', 'crítico', 'grave'];
        $palabrasAlta = ['importante', 'prioritario', 'necesario', 'relevante'];
        $palabrasBaja = ['sugerencia', 'mejora', 'opcional', 'futuro'];

        foreach ($palabrasUrgentes as $palabra) {
            if (stripos($texto, $palabra) !== false) {
                return 'urg';
            }
        }

        foreach ($palabrasAlta as $palabra) {
            if (stripos($texto, $palabra) !== false) {
                return 'alt';
            }
        }

        foreach ($palabrasBaja as $palabra) {
            if (stripos($texto, $palabra) !== false) {
                return 'baj';
            }
        }

        return 'med'; // Por defecto
    }

    /**
     * Categorizar veeduría
     */
    private function categorizarVeeduria($texto)
    {
        $categorias = [
            'inf' => ['infraestructura', 'vía', 'calle', 'puente', 'construcción'],
            'ser' => ['servicio', 'agua', 'luz', 'gas', 'basura'],
            'seg' => ['seguridad', 'policía', 'delito', 'robo', 'violencia'],
            'edu' => ['educación', 'colegio', 'escuela', 'universidad', 'estudiante'],
            'sal' => ['salud', 'hospital', 'clínica', 'médico', 'enfermedad'],
            'tra' => ['transporte', 'bus', 'taxi', 'metro', 'movilidad'],
            'amb' => ['ambiente', 'contaminación', 'basura', 'aire', 'agua']
        ];

        foreach ($categorias as $categoria => $palabras) {
            foreach ($palabras as $palabra) {
                if (stripos($texto, $palabra) !== false) {
                    return $categoria;
                }
            }
        }

        return 'otr'; // Por defecto
    }

    /**
     * Generar recomendaciones
     */
    private function generarRecomendaciones($texto)
    {
        return [
            'Revisar la documentación presentada',
            'Contactar a las entidades involucradas',
            'Programar una visita técnica al lugar',
            'Solicitar información adicional si es necesario',
            'Establecer un cronograma de seguimiento'
        ];
    }
}
