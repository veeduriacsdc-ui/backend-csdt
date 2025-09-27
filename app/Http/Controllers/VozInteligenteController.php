<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\InterfazVozInteligente;

/**
 * Controlador para Interfaz de Voz Inteligente
 * Maneja comandos de voz avanzados con integración de IAs
 */
class VozInteligenteController extends Controller
{
    protected InterfazVozInteligente $interfazVoz;

    public function __construct(InterfazVozInteligente $interfazVoz)
    {
        $this->interfazVoz = $interfazVoz;
    }

    /**
     * Procesar comando de voz inteligente completo
     */
    public function procesarComando(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'audio' => 'required|file|mimes:mp3,wav,m4a,webm|max:51200',
                'contexto' => 'nullable|array',
                'contexto.tipo_sesion' => 'nullable|string|in:consulta,ayuda,control,navegacion',
                'contexto.modulo_actual' => 'nullable|string',
                'contexto.historial' => 'nullable|array',
                'preferencias' => 'nullable|array',
                'preferencias.idioma' => 'nullable|string|in:es,en,pt',
                'preferencias.velocidad_respuesta' => 'nullable|numeric|min:0.5|max:2.0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'exito' => false,
                    'error' => 'Datos de entrada inválidos',
                    'errores' => $validator->errors()
                ], 422);
            }

            // Guardar archivo de audio temporalmente
            $archivo = $request->file('audio');
            $pathTemporal = $archivo->store('temp/voz_ia', 'local');

            // Preparar contexto
            $contexto = $request->input('contexto', []);
            $contexto['usuario_id'] = auth()->id();
            $contexto['usuario_info'] = auth()->check() ? [
                'id' => auth()->id(),
                'nombre' => auth()->user()->nombre,
                'tipo' => auth()->user()->tipo_usuario ?? 'cliente'
            ] : null;

            // Procesar comando de voz
            $resultado = $this->interfazVoz->procesarComandoVoz($pathTemporal, $contexto);

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
                'datos' => [
                    'texto_usuario' => $resultado['texto_usuario'],
                    'idioma_detectado' => $resultado['idioma_detectado'],
                    'intencion' => $resultado['intencion'],
                    'respuesta_ia' => $resultado['respuesta_ia'],
                    'respuesta_voz' => $resultado['respuesta_voz'],
                    'tiempo_procesamiento' => $resultado['tiempo_procesamiento'],
                    'sesion_id' => $resultado['sesion_id'] ?? uniqid('voz_ia_', true)
                ],
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error en procesarComando', [
                'error' => $e->getMessage(),
                'usuario' => auth()->id()
            ]);

            return response()->json([
                'exito' => false,
                'error' => 'Error interno del servidor de voz inteligente'
            ], 500);
        }
    }

    /**
     * Obtener comandos disponibles
     */
    public function comandosDisponibles(): JsonResponse
    {
        try {
            $comandos = [
                'es' => [
                    'consultas' => [
                        'consultar PQRSFD',
                        'información sobre usuarios',
                        'estadísticas del sistema',
                        'estado del dashboard'
                    ],
                    'acciones' => [
                        'crear nueva petición',
                        'mostrar reportes',
                        'ayuda del sistema',
                        'estado del sistema'
                    ],
                    'navegacion' => [
                        'ir a mapas',
                        'ver documentos',
                        'acceder a perfil'
                    ]
                ],
                'en' => [
                    'consultas' => [
                        'consult PQRSFD',
                        'information about users',
                        'system statistics',
                        'dashboard status'
                    ],
                    'acciones' => [
                        'create new petition',
                        'show reports',
                        'system help',
                        'system status'
                    ],
                    'navegacion' => [
                        'go to maps',
                        'view documents',
                        'access profile'
                    ]
                ]
            ];

            return response()->json([
                'exito' => true,
                'comandos' => $comandos,
                'idiomas_soportados' => ['es', 'en', 'pt'],
                'modulos_disponibles' => [
                    'pqrsfd', 'usuarios', 'dashboard', 'mapas', 'documentos', 'reportes'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo comandos disponibles', ['error' => $e->getMessage()]);

            return response()->json([
                'exito' => false,
                'error' => 'Error obteniendo comandos disponibles'
            ], 500);
        }
    }

    /**
     * Obtener historial de interacciones de voz
     */
    public function historialInteracciones(Request $request): JsonResponse
    {
        try {
            // En una implementación completa, aquí se consultarían logs de interacciones
            // Por ahora, devolver estructura de ejemplo
            $historial = [
                'total_interacciones' => 0,
                'interacciones_recientes' => [],
                'estadisticas' => [
                    'consultas_exitosas' => 0,
                    'errores_procesamiento' => 0,
                    'tiempo_promedio_respuesta' => 0,
                    'comandos_mas_usados' => []
                ],
                'periodo' => $request->input('periodo', '24h')
            ];

            return response()->json([
                'exito' => true,
                'historial' => $historial
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo historial', ['error' => $e->getMessage()]);

            return response()->json([
                'exito' => false,
                'error' => 'Error obteniendo historial de interacciones'
            ], 500);
        }
    }

    /**
     * Configurar preferencias de voz del usuario
     */
    public function configurarPreferencias(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'idioma' => 'nullable|string|in:es,en,pt',
                'velocidad_voz' => 'nullable|numeric|min:0.25|max:4.0',
                'proveedor_voz' => 'nullable|string|in:openai,elevenlabs,google,azure',
                'voz_favorita' => 'nullable|string',
                'notificaciones_voz' => 'nullable|boolean',
                'modo_automatico' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'exito' => false,
                    'error' => 'Datos de configuración inválidos',
                    'errores' => $validator->errors()
                ], 422);
            }

            // En una implementación completa, aquí se guardarían las preferencias en BD
            $preferencias = [
                'idioma' => $request->input('idioma', 'es'),
                'velocidad_voz' => $request->input('velocidad_voz', 1.0),
                'proveedor_voz' => $request->input('proveedor_voz', 'elevenlabs'),
                'voz_favorita' => $request->input('voz_favorita', '21m00Tcm4TlvDq8ikWAM'),
                'notificaciones_voz' => $request->input('notificaciones_voz', true),
                'modo_automatico' => $request->input('modo_automatico', false),
                'usuario_id' => auth()->id(),
                'actualizado_en' => now()->toISOString()
            ];

            return response()->json([
                'exito' => true,
                'mensaje' => 'Preferencias de voz actualizadas correctamente',
                'preferencias' => $preferencias
            ]);

        } catch (\Exception $e) {
            Log::error('Error configurando preferencias', [
                'error' => $e->getMessage(),
                'usuario' => auth()->id()
            ]);

            return response()->json([
                'exito' => false,
                'error' => 'Error configurando preferencias de voz'
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de uso del sistema de voz
     */
    public function estadisticasUso(Request $request): JsonResponse
    {
        try {
            $estadisticas = [
                'uso_general' => [
                    'total_comandos_procesados' => 0,
                    'tasa_exito' => 0,
                    'tiempo_promedio_respuesta' => 0,
                    'usuarios_activos' => 0
                ],
                'por_proveedor' => [
                    'openai_whisper' => ['usos' => 0, 'exito' => 0],
                    'google_speech' => ['usos' => 0, 'exito' => 0],
                    'azure_speech' => ['usos' => 0, 'exito' => 0]
                ],
                'por_tipo_comando' => [
                    'consultar' => 0,
                    'crear' => 0,
                    'editar' => 0,
                    'eliminar' => 0,
                    'ayuda' => 0,
                    'estado' => 0
                ],
                'por_idioma' => [
                    'es' => 0,
                    'en' => 0,
                    'pt' => 0
                ],
                'periodo' => $request->input('periodo', '7d'),
                'timestamp' => now()->toISOString()
            ];

            return response()->json([
                'exito' => true,
                'estadisticas' => $estadisticas
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas', ['error' => $e->getMessage()]);

            return response()->json([
                'exito' => false,
                'error' => 'Error obteniendo estadísticas de uso'
            ], 500);
        }
    }

}
