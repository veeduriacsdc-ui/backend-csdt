<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\ServicioVozAvanzado;
use App\Services\IAAvanzada;

class VozController extends Controller
{
    protected ServicioVozAvanzado $servicioVoz;
    protected IAAvanzada $servicioIA;

    public function __construct(
        ServicioVozAvanzado $servicioVoz,
        IAAvanzada $servicioIA
    ) {
        $this->servicioVoz = $servicioVoz;
        $this->servicioIA = $servicioIA;
    }

    /**
     * Convertir texto a voz
     */
    public function textoAVoz(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'texto' => 'required|string|max:5000',
                'voz' => 'nullable|string|in:alloy,echo,fable,onyx,nova,shimmer',
                'velocidad' => 'nullable|numeric|min:0.25|max:4.0',
                'modelo' => 'nullable|string|in:tts-1,tts-1-hd',
                'formato' => 'nullable|string|in:mp3,opus,aac,flac',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'exito' => false,
                    'error' => 'Datos de entrada inválidos',
                    'errores' => $validator->errors()
                ], 422);
            }

            $opciones = [
                'voz' => $request->input('voz', 'alloy'),
                'velocidad' => $request->input('velocidad', 1.0),
                'modelo' => $request->input('modelo', 'tts-1'),
                'formato' => $request->input('formato', 'mp3'),
                'prioridad' => 'normal'
            ];

            $resultado = $this->servicioVoz->sintetizarVoz(
                $request->input('texto'),
                $opciones
            );

            if (!$resultado['exito']) {
                return response()->json([
                    'exito' => false,
                    'error' => $resultado['error'] ?? 'Error en síntesis de voz'
                ], 500);
            }

            return response()->json([
                'exito' => true,
                'audio_base64' => $resultado['audio_base64'],
                'duracion' => $resultado['duracion'],
                'formato' => $resultado['formato'],
                'proveedor' => $resultado['proveedor'] ?? 'desconocido'
            ]);

        } catch (\Exception $e) {
            Log::error('Error en textoAVoz', [
                'error' => $e->getMessage(),
                'usuario' => auth()->id()
            ]);

            return response()->json([
                'exito' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Convertir audio a texto
     */
    public function vozATexto(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'audio' => 'required|file|mimes:mp3,wav,m4a,webm|max:51200', // 50MB máximo
                'idioma' => 'nullable|string|in:es,en,pt,fr',
                'modelo' => 'nullable|string|in:whisper-1',
                'temperatura' => 'nullable|numeric|min:0|max:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'exito' => false,
                    'error' => 'Archivo de audio inválido',
                    'errores' => $validator->errors()
                ], 422);
            }

            // Guardar archivo temporalmente
            $archivo = $request->file('audio');
            $pathTemporal = $archivo->store('temp/voz', 'local');

            $opciones = [
                'idioma' => $request->input('idioma', 'es'),
                'modelo' => $request->input('modelo', 'whisper-1'),
                'temperatura' => $request->input('temperatura', 0.2),
            ];

            $resultado = $this->servicioVoz->transcribirAudio($pathTemporal, $opciones);

            // Limpiar archivo temporal
            Storage::disk('local')->delete($pathTemporal);

            if (!$resultado['exito']) {
                return response()->json([
                    'exito' => false,
                    'error' => $resultado['error'] ?? 'Error en transcripción'
                ], 500);
            }

            return response()->json([
                'exito' => true,
                'texto' => $resultado['texto'],
                'confianza' => $resultado['confianza'] ?? null,
                'duracion' => $resultado['duracion'] ?? null,
                'idioma_detectado' => $resultado['idioma'] ?? $opciones['idioma'],
                'proveedor' => $resultado['proveedor'] ?? 'desconocido'
            ]);

        } catch (\Exception $e) {
            Log::error('Error en vozATexto', [
                'error' => $e->getMessage(),
                'usuario' => auth()->id()
            ]);

            return response()->json([
                'exito' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Conversación completa por voz
     */
    public function conversacionVoz(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'audio' => 'required|file|mimes:mp3,wav,m4a,webm|max:51200',
                'contexto' => 'nullable|array',
                'contexto.tipo' => 'nullable|string|in:pqrsfd,consulta,informacion,ayuda',
                'contexto.sesion_id' => 'nullable|string',
                'contexto.historial' => 'nullable|array',
                'preferencias' => 'nullable|array',
                'preferencias.voz' => 'nullable|string',
                'preferencias.velocidad' => 'nullable|numeric|min:0.25|max:4.0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'exito' => false,
                    'error' => 'Datos de entrada inválidos',
                    'errores' => $validator->errors()
                ], 422);
            }

            // Guardar archivo de audio
            $archivo = $request->file('audio');
            $pathTemporal = $archivo->store('temp/conversacion', 'local');

            // Preparar contexto
            $contexto = $request->input('contexto', []);
            $preferencias = $request->input('preferencias', []);

            // Agregar información del usuario si está autenticado
            if (auth()->check()) {
                $contexto['usuario'] = [
                    'id' => auth()->id(),
                    'nombre' => auth()->user()->nombre,
                    'tipo' => auth()->user()->tipo_usuario ?? 'cliente'
                ];
            }

            $resultado = $this->servicioVoz->conversacionPorVoz($pathTemporal, $contexto);

            // Limpiar archivo temporal
            Storage::disk('local')->delete($pathTemporal);

            if (!$resultado['exito']) {
                return response()->json([
                    'exito' => false,
                    'error' => $resultado['error'],
                    'respuesta_fallback' => $resultado['respuesta_voz'] ?? null
                ], 500);
            }

            return response()->json([
                'exito' => true,
                'texto_usuario' => $resultado['texto_usuario'],
                'respuesta_ia' => $resultado['respuesta_ia']['respuesta'],
                'respuesta_voz' => $resultado['respuesta_voz']['audio_base64'],
                'tipo_respuesta' => $resultado['respuesta_ia']['tipo_respuesta'],
                'acciones_sugeridas' => $resultado['respuesta_ia']['acciones_sugeridas'],
                'tiempo_procesamiento' => $resultado['tiempo_procesamiento'],
                'sesion_id' => $contexto['sesion_id'] ?? uniqid('voz_', true)
            ]);

        } catch (\Exception $e) {
            Log::error('Error en conversacionVoz', [
                'error' => $e->getMessage(),
                'usuario' => auth()->id()
            ]);

            return response()->json([
                'exito' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener configuración de voz disponible
     */
    public function configuracionVoz(): JsonResponse
    {
        try {
            $configuracion = [
                'proveedores_disponibles' => [
                    'sintesis' => [
                        'openai_tts' => config('services.openai.api_key') ? true : false,
                        'elevenlabs' => config('services.elevenlabs.api_key') ? true : false,
                        'google_tts' => config('services.google_cloud.project_id') ? true : false,
                        'azure_tts' => config('services.azure_cognitive.api_key') ? true : false,
                    ],
                    'reconocimiento' => [
                        'openai_whisper' => config('services.openai.api_key') ? true : false,
                        'google_speech' => config('services.google_cloud.project_id') ? true : false,
                        'azure_speech' => config('services.azure_cognitive.api_key') ? true : false,
                    ]
                ],
                'configuracion_optima' => [
                    'sample_rate' => config('services.voice_optimization.optimal_sample_rate', 16000),
                    'audio_quality' => config('services.voice_optimization.audio_quality', 16),
                    'max_duration' => config('services.voice_optimization.max_audio_duration', 30),
                    'formatos_soportados' => ['mp3', 'wav', 'm4a', 'webm'],
                    'idiomas_soportados' => ['es', 'en', 'pt', 'fr']
                ],
                'cache_activo' => config('services.voice_optimization.cache_duration', 7200) > 0,
                'actualizacion_automatica' => config('services.voice_optimization.auto_update_models', true)
            ];

            return response()->json([
                'exito' => true,
                'configuracion' => $configuracion
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo configuración de voz', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'exito' => false,
                'error' => 'Error obteniendo configuración'
            ], 500);
        }
    }

    /**
     * Probar conectividad de servicios de voz
     */
    public function probarVoz(Request $request): JsonResponse
    {
        try {
            $tipo = $request->input('tipo', 'ambos'); // 'sintesis', 'reconocimiento', 'ambos'

            $resultados = [];

            // Probar síntesis de voz
            if ($tipo === 'sintesis' || $tipo === 'ambos') {
                $textoPrueba = "Hola, esta es una prueba del sistema de voz del CSDT.";

                $inicio = microtime(true);
                $resultadoSintesis = $this->servicioVoz->sintetizarVoz($textoPrueba, [
                    'prioridad' => 'alta',
                    'formato' => 'mp3'
                ]);
                $tiempoSintesis = microtime(true) - $inicio;

                $resultados['sintesis'] = [
                    'exito' => $resultadoSintesis['exito'],
                    'tiempo_respuesta' => round($tiempoSintesis, 2),
                    'proveedor' => $resultadoSintesis['proveedor'] ?? 'desconocido',
                    'tamano_audio' => $resultadoSintesis['tamano'] ?? null,
                    'error' => $resultadoSintesis['error'] ?? null
                ];
            }

            // Probar reconocimiento de voz (con archivo de prueba si existe)
            if ($tipo === 'reconocimiento' || $tipo === 'ambos') {
                // Aquí se podría usar un archivo de audio de prueba
                $resultados['reconocimiento'] = [
                    'exito' => false,
                    'mensaje' => 'Requiere archivo de audio de prueba'
                ];
            }

            return response()->json([
                'exito' => true,
                'pruebas' => $resultados,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error en prueba de voz', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'exito' => false,
                'error' => 'Error en pruebas de voz'
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de uso del servicio de voz
     */
    public function estadisticasVoz(Request $request): JsonResponse
    {
        try {
            // En una implementación completa, aquí se consultarían métricas de uso
            $estadisticas = [
                'total_solicitudes' => 0,
                'exito_sintesis' => 0,
                'exito_reconocimiento' => 0,
                'tiempo_promedio_sintesis' => 0,
                'tiempo_promedio_reconocimiento' => 0,
                'proveedores_mas_usados' => [],
                'errores_comunes' => [],
                'uso_por_hora' => [],
                'consumo_datos' => 0
            ];

            return response()->json([
                'exito' => true,
                'estadisticas' => $estadisticas,
                'periodo' => $request->input('periodo', '24h')
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas de voz', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'exito' => false,
                'error' => 'Error obteniendo estadísticas'
            ], 500);
        }
    }
}
