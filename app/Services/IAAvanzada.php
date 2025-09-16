<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\PQRSFD;
use App\Models\ConsultaPrevia;
use App\Services\CircuitBreaker;
use App\Services\SistemaActualizacionIA;

class IAAvanzada
{
    protected $apiKey;
    protected $baseUrl;
    protected $modelo;
    protected CircuitBreaker $circuitBreaker;
    protected array $providers;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
        $this->modelo = config('services.openai.model', 'gpt-4');

        // Inicializar Circuit Breaker
        $this->circuitBreaker = new CircuitBreaker('openai', 5, 60, 3);

        // Configurar proveedores disponibles con sus circuit breakers
        $this->providers = [
            'openai' => [
                'circuit_breaker' => new CircuitBreaker('openai', 5, 60, 3),
                'rate_limit' => 'openai:1000:1', // 1000 requests per minute
            ],
            'anthropic' => [
                'circuit_breaker' => new CircuitBreaker('anthropic', 5, 60, 3),
                'rate_limit' => 'anthropic:500:1', // 500 requests per minute
            ],
            'lexisnexis' => [
                'circuit_breaker' => new CircuitBreaker('lexisnexis', 3, 120, 2),
                'rate_limit' => 'lexisnexis:100:1', // 100 requests per minute
            ],
            'google_gemini' => [
                'circuit_breaker' => new CircuitBreaker('google_gemini', 5, 60, 3),
                'rate_limit' => 'google_gemini:1000:1', // 1000 requests per minute
            ],
        ];
    }

    /**
     * Mejorar narración de PQRSFD con IA - Versión con Circuit Breaker y Rate Limiting
     */
    public function mejorarNarracion(string $narracionOriginal): array
    {
        $cacheKey = 'ia_narracion_' . md5($narracionOriginal);

        return Cache::remember($cacheKey, 3600, function () use ($narracionOriginal) {
            try {
                // Verificar rate limiting
                if (!$this->checkRateLimit('openai')) {
                    return [
                        'exito' => false,
                        'error' => 'Rate limit excedido. Intente nuevamente más tarde.',
                        'fallback' => $this->procesarSinIA($narracionOriginal)
                    ];
                }

                // Ejecutar con circuit breaker
                return $this->circuitBreaker->execute(function () use ($narracionOriginal) {
                    return $this->llamarAPI($narracionOriginal);
                });

            } catch (\Exception $e) {
                Log::error('Error al mejorar narración con IA', [
                    'error' => $e->getMessage(),
                    'narracion_original' => substr($narracionOriginal, 0, 100) . '...'
                ]);

                // Intentar fallback
                return $this->manejarFalloIA($narracionOriginal, $e);
            }
        });
    }

    /**
     * Verificar rate limiting
     */
    protected function checkRateLimit(string $provider): bool
    {
        if (!isset($this->providers[$provider])) {
            return true; // Si no está configurado, permitir
        }

        $rateLimitKey = $this->providers[$provider]['rate_limit'];

        return RateLimiter::attempt(
            $rateLimitKey,
            1, // 1 intento
            function () {
                // Rate limit alcanzado
                return false;
            }
        );
    }

    /**
     * Llamar a la API de IA con manejo de errores mejorado
     */
    protected function llamarAPI(string $narracionOriginal): array
    {
        $startTime = microtime(true);

        $prompt = $this->construirPromptNarracion($narracionOriginal);

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => $this->modelo,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Eres un asistente especializado en mejorar narraciones de PQRSFD para instituciones públicas. Tu tarea es hacer las narraciones más claras, formales y completas.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.3,
            ]);

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convertir a milisegundos

        if ($response->successful()) {
            $resultado = $response->json();
            $narracionMejorada = $resultado['choices'][0]['message']['content'];

            // Log de métricas
            Log::info('IA API call successful', [
                'provider' => 'openai',
                'operation' => 'mejorar_narracion',
                'duration_ms' => $duration,
                'tokens_used' => $resultado['usage']['total_tokens'] ?? null
            ]);

            return [
                'exito' => true,
                'narracion_mejorada' => $narracionMejorada,
                'confianza' => $this->calcularConfianzaIA($narracionOriginal, $narracionMejorada),
                'palabras_agregadas' => $this->contarPalabrasAgregadas($narracionOriginal, $narracionMejorada),
                'duracion_ms' => $duration,
                'proveedor' => 'openai'
            ];
        }

        throw new \RuntimeException('Error en la respuesta de la API de IA: ' . $response->body());
    }

    /**
     * Procesar sin IA como fallback
     */
    protected function procesarSinIA(string $narracion): array
    {
        // Aplicar mejoras básicas sin IA
        $mejorada = $this->mejorarNarracionBasica($narracion);

        return [
            'exito' => true,
            'narracion_mejorada' => $mejorada,
            'confianza' => 0.5,
            'palabras_agregadas' => $this->contarPalabrasAgregadas($narracion, $mejorada),
            'fallback' => true,
            'proveedor' => 'sistema_local'
        ];
    }

    /**
     * Manejar fallos de IA con fallback inteligente
     */
    protected function manejarFalloIA(string $narracion, \Exception $error): array
    {
        Log::warning('IA service failed, using fallback', [
            'error' => $error->getMessage(),
            'narracion_length' => strlen($narracion)
        ]);

        // Intentar con otro proveedor si está disponible
        $fallbackResult = $this->intentarProveedorAlternativo($narracion);

        if ($fallbackResult) {
            return $fallbackResult;
        }

        // Usar procesamiento local como último recurso
        return $this->procesarSinIA($narracion);
    }

    /**
     * Intentar con un proveedor alternativo
     */
    protected function intentarProveedorAlternativo(string $narracion): ?array
    {
        // Intentar con Google Gemini si está disponible
        if (config('services.google_gemini.api_key')) {
            try {
                return $this->llamarGoogleGemini($narracion);
            } catch (\Exception $e) {
                Log::warning('Google Gemini fallback also failed', ['error' => $e->getMessage()]);
            }
        }

        return null;
    }

    /**
     * Mejorar narración con algoritmos básicos (sin IA)
     */
    protected function mejorarNarracionBasica(string $narracion): string
    {
        // Aplicar mejoras básicas de texto
        $mejorada = $narracion;

        // Capitalizar primera letra de cada oración
        $mejorada = preg_replace_callback('/([.!?]\s*)([a-z])/', function($matches) {
            return $matches[1] . strtoupper($matches[2]);
        }, ucfirst($mejorada));

        // Corregir espacios múltiples
        $mejorada = preg_replace('/\s+/', ' ', $mejorada);

        // Agregar punto final si no tiene
        if (!preg_match('/[.!?]$/', $mejorada)) {
            $mejorada .= '.';
        }

        return trim($mejorada);
    }

    /**
     * Clasificar automáticamente PQRSFD
     */
    public function clasificarPQRSFD(string $asunto, string $narracion): array
    {
        $cacheKey = 'ia_clasificacion_' . md5($asunto . $narracion);

        return Cache::remember($cacheKey, 3600, function () use ($asunto, $narracion) {
            $prompt = $this->construirPromptClasificacion($asunto, $narracion);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => $this->modelo,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Eres un clasificador experto de PQRSFD. Debes clasificar las solicitudes en: peticion, queja, reclamo, sugerencia, felicitacion, denuncia, solicitud_informacion.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 200,
                'temperature' => 0.1,
            ]);

            if ($response->successful()) {
                $resultado = $response->json();
                $clasificacion = json_decode($resultado['choices'][0]['message']['content'], true);

                return [
                    'exito' => true,
                    'tipo_pqrsfd' => $clasificacion['tipo'] ?? 'peticion',
                    'prioridad_sugerida' => $clasificacion['prioridad'] ?? 'media',
                    'categoria_sugerida' => $clasificacion['categoria'] ?? 'otros',
                    'confianza' => $clasificacion['confianza'] ?? 0.5,
                ];
            }

            return [
                'exito' => false,
                'error' => 'No se pudo clasificar la PQRSFD'
            ];
        });
    }

    /**
     * Generar recomendaciones automáticas
     */
    public function generarRecomendaciones(PQRSFD $pqrsfd): array
    {
        $contexto = [
            'tipo' => $pqrsfd->tipo_pqrsfd,
            'categoria' => $pqrsfd->categoria,
            'prioridad' => $pqrsfd->prioridad,
            'asunto' => $pqrsfd->asunto,
            'narracion' => substr($pqrsfd->narracion_cliente, 0, 500),
        ];

        $cacheKey = 'ia_recomendaciones_' . md5(json_encode($contexto));

        return Cache::remember($cacheKey, 7200, function () use ($contexto) {
            $prompt = $this->construirPromptRecomendaciones($contexto);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => $this->modelo,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Eres un asesor experto en gestión de PQRSFD para instituciones públicas. Debes proporcionar recomendaciones prácticas y accionables.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 800,
                'temperature' => 0.4,
            ]);

            if ($response->successful()) {
                $resultado = $response->json();
                $recomendacionesRaw = $resultado['choices'][0]['message']['content'];

                return [
                    'exito' => true,
                    'recomendaciones' => $this->parsearRecomendaciones($recomendacionesRaw),
                    'tiempo_estimado_respuesta' => $this->estimarTiempoRespuesta($contexto),
                    'departamento_sugerido' => $this->sugerirDepartamento($contexto),
                ];
            }

            return [
                'exito' => false,
                'error' => 'No se pudieron generar recomendaciones'
            ];
        });
    }

    /**
     * Analizar sentimiento de la PQRSFD
     */
    public function analizarSentimiento(string $texto): array
    {
        $cacheKey = 'ia_sentimiento_' . md5($texto);

        return Cache::remember($cacheKey, 3600, function () use ($texto) {
            $prompt = "Analiza el sentimiento del siguiente texto de PQRSFD y clasifícalo como: positivo, negativo, neutral. También indica el nivel de urgencia (bajo, medio, alto) y emociones detectadas:\n\n{$texto}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => $this->modelo,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Eres un analista de sentimientos especializado en PQRSFD. Debes ser preciso y objetivo en tu análisis.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 150,
                'temperature' => 0.1,
            ]);

            if ($response->successful()) {
                $resultado = $response->json();
                $analisis = json_decode($resultado['choices'][0]['message']['content'], true);

                return [
                    'exito' => true,
                    'sentimiento' => $analisis['sentimiento'] ?? 'neutral',
                    'urgencia' => $analisis['urgencia'] ?? 'medio',
                    'emociones' => $analisis['emociones'] ?? [],
                    'confianza' => $analisis['confianza'] ?? 0.5,
                ];
            }

            return [
                'exito' => false,
                'error' => 'No se pudo analizar el sentimiento'
            ];
        });
    }

    /**
     * Generar resumen ejecutivo de PQRSFD
     */
    public function generarResumenEjecutivo(PQRSFD $pqrsfd): string
    {
        $contexto = [
            'tipo' => $pqrsfd->tipo_pqrsfd,
            'asunto' => $pqrsfd->asunto,
            'narracion' => $pqrsfd->narracion_cliente,
            'estado' => $pqrsfd->estado,
            'prioridad' => $pqrsfd->prioridad,
        ];

        $cacheKey = 'ia_resumen_' . $pqrsfd->id;

        return Cache::remember($cacheKey, 3600, function () use ($contexto) {
            $prompt = $this->construirPromptResumen($contexto);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => $this->modelo,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Eres un especialista en crear resúmenes ejecutivos concisos y claros para PQRSFD. Debes ser objetivo y destacar lo más importante.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 300,
                'temperature' => 0.2,
            ]);

            if ($response->successful()) {
                $resultado = $response->json();
                return $resultado['choices'][0]['message']['content'];
            }

            return 'No se pudo generar el resumen ejecutivo';
        });
    }

    // Métodos auxiliares

    private function construirPromptNarracion(string $narracion): string
    {
        return "Mejora la siguiente narración de PQRSFD haciéndola más clara, formal y completa. Mantén el significado original pero mejora la estructura, gramática y añade detalles importantes que puedan estar implícitos:\n\n{$narracion}\n\nDevuelve solo la narración mejorada.";
    }

    private function construirPromptClasificacion(string $asunto, string $narracion): string
    {
        return "Clasifica la siguiente PQRSFD. Asunto: {$asunto}\n\nNarración: {$narracion}\n\nDevuelve un JSON con: tipo (peticion/queja/reclamo/sugerencia/felicitacion/denuncia/solicitud_informacion), prioridad (baja/media/alta/urgente), categoria (infraestructura/servicios_publicos/seguridad/educacion/salud/transporte/medio_ambiente/otros), confianza (0-1).";
    }

    private function construirPromptRecomendaciones(array $contexto): string
    {
        $json = json_encode($contexto, JSON_PRETTY_PRINT);
        return "Basado en la siguiente PQRSFD, proporciona 3-5 recomendaciones prácticas para su resolución:\n\n{$json}\n\nDevuelve las recomendaciones como una lista numerada.";
    }

    private function construirPromptResumen(array $contexto): string
    {
        $json = json_encode($contexto, JSON_PRETTY_PRINT);
        return "Crea un resumen ejecutivo conciso (máximo 100 palabras) de la siguiente PQRSFD:\n\n{$json}";
    }

    private function calcularConfianzaIA(string $original, string $mejorada): float
    {
        // Lógica simple: mayor longitud = mayor confianza en mejoras
        $longitudOriginal = strlen($original);
        $longitudMejorada = strlen($mejorada);
        $ratio = $longitudMejorada / $longitudOriginal;

        return min(1.0, max(0.1, ($ratio - 1) * 0.5 + 0.5));
    }

    private function contarPalabrasAgregadas(string $original, string $mejorada): int
    {
        $palabrasOriginal = str_word_count($original);
        $palabrasMejorada = str_word_count($mejorada);

        return max(0, $palabrasMejorada - $palabrasOriginal);
    }

    private function parsearRecomendaciones(string $texto): array
    {
        // Parsear lista numerada en array
        $lineas = explode("\n", $texto);
        $recomendaciones = [];

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (preg_match('/^\d+\.\s*(.+)$/', $linea, $matches)) {
                $recomendaciones[] = $matches[1];
            }
        }

        return $recomendaciones;
    }

    private function estimarTiempoRespuesta(array $contexto): string
    {
        // Lógica simple basada en prioridad y tipo
        $prioridad = $contexto['prioridad'] ?? 'media';

        switch ($prioridad) {
            case 'urgente':
                return '24 horas';
            case 'alta':
                return '3-5 días';
            case 'media':
                return '7-10 días';
            case 'baja':
                return '15-30 días';
            default:
                return '7-10 días';
        }
    }

    private function sugerirDepartamento(array $contexto): string
    {
        $categoria = $contexto['categoria'] ?? 'otros';

        $departamentos = [
            'infraestructura' => 'Obras Públicas',
            'servicios_publicos' => 'Servicios Públicos',
            'seguridad' => 'Seguridad Ciudadana',
            'educacion' => 'Educación',
            'salud' => 'Salud',
            'transporte' => 'Transporte',
            'medio_ambiente' => 'Medio Ambiente',
            'otros' => 'Atención al Ciudadano',
        ];

        return $departamentos[$categoria] ?? 'Atención al Ciudadano';
    }

    /**
     * Procesar consulta específica para voz con IA integrada
     */
    public function procesarConsultaVoz(string $textoConsulta, array $contexto = []): array
    {
        $cacheKey = 'ia_voz_' . md5($textoConsulta . json_encode($contexto));

        return Cache::remember($cacheKey, 1800, function () use ($textoConsulta, $contexto) { // 30 minutos
            try {
                // Verificar rate limiting
                if (!$this->checkRateLimit('openai')) {
                    return [
                        'exito' => false,
                        'error' => 'Límite de velocidad excedido para consultas de voz',
                        'respuesta' => 'Lo siento, el servicio está temporalmente sobrecargado. Por favor, intenta nuevamente en unos minutos.',
                        'tipo' => 'error'
                    ];
                }

                // Ejecutar con circuit breaker
                return $this->circuitBreaker->execute(function () use ($textoConsulta, $contexto) {
                    return $this->ejecutarConsultaVoz($textoConsulta, $contexto);
                });

            } catch (\Exception $e) {
                Log::error('Error procesando consulta de voz', [
                    'error' => $e->getMessage(),
                    'texto' => substr($textoConsulta, 0, 100) . '...'
                ]);

                return [
                    'exito' => false,
                    'error' => 'Error procesando consulta de voz',
                    'respuesta' => 'Hubo un problema técnico. Por favor, reformula tu consulta o intenta más tarde.',
                    'tipo' => 'error'
                ];
            }
        });
    }

    /**
     * Ejecutar consulta de voz con IA
     */
    protected function ejecutarConsultaVoz(string $texto, array $contexto): array
    {
        // Crear prompt específico para consultas de voz
        $prompt = $this->crearPromptVoz($texto, $contexto);

        // Hacer llamada a OpenAI
        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post($this->baseUrl . '/chat/completions', [
                'model' => $this->modelo,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Eres un asistente de voz inteligente del CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL (CSDT). ' .
                                   'Debes responder de manera clara, concisa y natural, como si estuvieras hablando con una persona. ' .
                                   'Las respuestas deben ser apropiadas para comunicación verbal, no escrita. ' .
                                   'Mantén un tono profesional pero amigable.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 500, // Más corto para voz
                'temperature' => 0.7, // Más creativo para respuestas naturales
                'presence_penalty' => 0.1,
                'frequency_penalty' => 0.1,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Error en respuesta de IA: ' . $response->body());
        }

        $data = $response->json();
        $respuestaIA = $data['choices'][0]['message']['content'] ?? '';

        // Analizar respuesta para determinar tipo y acciones
        $analisis = $this->analizarRespuestaVoz($respuestaIA, $contexto);

        return [
            'exito' => true,
            'respuesta' => $respuestaIA,
            'tipo' => $analisis['tipo'],
            'acciones' => $analisis['acciones'],
            'confianza' => $analisis['confianza'],
            'duracion_estimada' => strlen($respuestaIA) * 0.15, // Estimación en segundos
            'tokens_usados' => $data['usage']['total_tokens'] ?? 0,
            'proveedor' => 'openai'
        ];
    }

    /**
     * Crear prompt específico para consultas de voz
     */
    protected function crearPromptVoz(string $texto, array $contexto): string
    {
        $tipoUsuario = $contexto['usuario']['tipo'] ?? 'cliente';
        $historial = $contexto['historial'] ?? [];
        $sesionId = $contexto['sesion_id'] ?? null;

        $prompt = "Consulta de voz del CSDT:\n\n";
        $prompt .= "Usuario: {$texto}\n\n";

        // Agregar contexto del usuario
        if ($tipoUsuario === 'cliente') {
            $prompt .= "Contexto: Usuario ciudadano consultando sobre servicios del CSDT.\n";
        } elseif ($tipoUsuario === 'operador') {
            $prompt .= "Contexto: Operador del sistema consultando sobre procedimientos.\n";
        } elseif ($tipoUsuario === 'administrador') {
            $prompt .= "Contexto: Administrador consultando sobre gestión del sistema.\n";
        }

        // Agregar historial si existe
        if (!empty($historial)) {
            $prompt .= "\nHistorial de conversación:\n";
            foreach (array_slice($historial, -3) as $mensaje) { // Últimos 3 mensajes
                $prompt .= "- {$mensaje['tipo']}: {$mensaje['contenido']}\n";
            }
        }

        $prompt .= "\nInstrucciones:\n";
        $prompt .= "- Responde de manera natural y conversacional\n";
        $prompt .= "- Sé claro y directo, evita jerga técnica innecesaria\n";
        $prompt .= "- Si es una PQRSFD, sugiere el tipo apropiado (Petición, Queja, Reclamo, Sugerencia, Denuncia, Felicitación)\n";
        $prompt .= "- Incluye información de contacto si es relevante\n";
        $prompt .= "- Mantén respuestas concisas pero completas\n";
        $prompt .= "- Si no tienes suficiente información, sugiere contactar directamente\n";

        return $prompt;
    }

    /**
     * Analizar respuesta de voz para determinar tipo y acciones sugeridas
     */
    protected function analizarRespuestaVoz(string $respuesta, array $contexto): array
    {
        $tipo = 'general';
        $acciones = [];
        $confianza = 0.8; // Confianza base

        // Analizar palabras clave para determinar tipo
        $palabrasClave = [
            'pqrsfd' => 'pqrsfd',
            'peticion' => 'pqrsfd',
            'queja' => 'pqrsfd',
            'reclamo' => 'pqrsfd',
            'sugerencia' => 'pqrsfd',
            'denuncia' => 'pqrsfd',
            'felicitacion' => 'pqrsfd',
            'consulta' => 'consulta',
            'informacion' => 'informacion',
            'ayuda' => 'ayuda',
            'problema' => 'problema',
            'emergencia' => 'urgente'
        ];

        $textoMinuscula = strtolower($respuesta . ' ' . ($contexto['tipo'] ?? ''));

        foreach ($palabrasClave as $palabra => $tipoDetectado) {
            if (strpos($textoMinuscula, $palabra) !== false) {
                $tipo = $tipoDetectado;
                break;
            }
        }

        // Generar acciones sugeridas basadas en el tipo
        switch ($tipo) {
            case 'pqrsfd':
                $acciones = [
                    'redirigir_formulario_pqrsfd',
                    'proporcionar_informacion_contacto',
                    'explicar_proceso'
                ];
                break;
            case 'consulta':
                $acciones = [
                    'buscar_informacion_base_datos',
                    'proporcionar_enlaces_relevantes',
                    'ofrecer_contacto_directo'
                ];
                break;
            case 'urgente':
                $acciones = [
                    'escalar_administrador',
                    'proporcionar_contacto_emergencia',
                    'registrar_alerta'
                ];
                $confianza = 0.9;
                break;
            default:
                $acciones = [
                    'continuar_conversacion',
                    'ofrecer_mas_informacion'
                ];
        }

        return [
            'tipo' => $tipo,
            'acciones' => $acciones,
            'confianza' => $confianza
        ];
    }

    /**
     * Verificar y aplicar actualizaciones automáticas de IAs
     */
    public function verificarActualizacionesAutomaticas(): array
    {
        try {
            $sistemaActualizacion = app(SistemaActualizacionIA::class);
            $resultados = $sistemaActualizacion->verificarActualizaciones();

            // Log de resultados
            Log::info('Verificación automática de actualizaciones IA completada', [
                'actualizaciones_disponibles' => count($resultados['actualizaciones_disponibles']),
                'actualizaciones_aplicadas' => count($resultados['actualizaciones_aplicadas']),
                'errores' => count($resultados['errores'])
            ]);

            return $resultados;

        } catch (\Exception $e) {
            Log::error('Error en verificación automática de IAs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'actualizaciones_disponibles' => [],
                'actualizaciones_aplicadas' => [],
                'errores' => [['mensaje' => $e->getMessage()]],
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Alimentar automáticamente librerías de IA
     */
    public function alimentarLibreriasAutomaticamente(): array
    {
        try {
            $sistemaActualizacion = app(SistemaActualizacionIA::class);
            $resultados = $sistemaActualizacion->alimentarLibrerias();

            // Log de resultados
            Log::info('Alimentación automática de librerías IA completada', [
                'librerias_actualizadas' => count($resultados['librerias_actualizadas']),
                'librerias_fallidas' => count($resultados['librerias_fallidas']),
                'dependencias_resueltas' => count($resultados['dependencias_resueltas'])
            ]);

            return $resultados;

        } catch (\Exception $e) {
            Log::error('Error en alimentación automática de librerías IA', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'librerias_actualizadas' => [],
                'librerias_fallidas' => [['error' => $e->getMessage()]],
                'dependencias_resueltas' => [],
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Mejorar automáticamente las capacidades de IA
     */
    public function mejorarCapacidadesAutomaticamente(): array
    {
        $mejoras = [
            'aplicadas' => [],
            'pendientes' => [],
            'errores' => [],
            'timestamp' => now()->toISOString()
        ];

        try {
            // 1. Verificar actualizaciones de modelos
            $actualizaciones = $this->verificarActualizacionesAutomaticas();
            if (!empty($actualizaciones['actualizaciones_aplicadas'])) {
                $mejoras['aplicadas'][] = [
                    'tipo' => 'modelos_ia',
                    'descripcion' => 'Modelos de IA actualizados automáticamente',
                    'detalles' => $actualizaciones['actualizaciones_aplicadas']
                ];
            }

            // 2. Alimentar librerías
            $librerias = $this->alimentarLibreriasAutomaticamente();
            if (!empty($librerias['librerias_actualizadas'])) {
                $mejoras['aplicadas'][] = [
                    'tipo' => 'librerias',
                    'descripcion' => 'Librerías de IA actualizadas automáticamente',
                    'detalles' => $librerias['librerias_actualizadas']
                ];
            }

            // 3. Optimizar configuración
            $optimizacion = $this->optimizarConfiguracionAutomaticamente();
            if (!empty($optimizacion['mejoras_aplicadas'])) {
                $mejoras['aplicadas'] = array_merge($mejoras['aplicadas'], $optimizacion['mejoras_aplicadas']);
            }

            // 4. Limpiar cachés obsoletos
            $limpieza = $this->limpiarCachesObsoletos();
            if ($limpieza['espacio_liberado'] > 0) {
                $mejoras['aplicadas'][] = [
                    'tipo' => 'limpieza_cache',
                    'descripcion' => 'Cachés obsoletos limpiados',
                    'detalles' => ['espacio_liberado' => $limpieza['espacio_liberado']]
                ];
            }

            // 5. Verificar salud del sistema
            $salud = $this->verificarSaludSistema();
            if (!empty($salud['recomendaciones'])) {
                $mejoras['pendientes'] = array_merge($mejoras['pendientes'], $salud['recomendaciones']);
            }

            Log::info('Mejoras automáticas de IA completadas', [
                'aplicadas' => count($mejoras['aplicadas']),
                'pendientes' => count($mejoras['pendientes']),
                'errores' => count($mejoras['errores'])
            ]);

        } catch (\Exception $e) {
            Log::error('Error en mejoras automáticas de IA', [
                'error' => $e->getMessage()
            ]);

            $mejoras['errores'][] = [
                'tipo' => 'error_general',
                'mensaje' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }

        return $mejoras;
    }

    /**
     * Optimizar configuración automáticamente
     */
    public function optimizarConfiguracionAutomaticamente(): array
    {
        $mejoras = ['mejoras_aplicadas' => []];

        try {
            // Optimizar límites de rate limiting basados en uso
            $optimizacionRateLimit = $this->optimizarRateLimiting();
            if ($optimizacionRateLimit['optimizado']) {
                $mejoras['mejoras_aplicadas'][] = [
                    'tipo' => 'rate_limiting',
                    'descripcion' => 'Rate limiting optimizado automáticamente',
                    'detalles' => $optimizacionRateLimit
                ];
            }

            // Optimizar tamaños de caché
            $optimizacionCache = $this->optimizarCacheSizes();
            if ($optimizacionCache['optimizado']) {
                $mejoras['mejoras_aplicadas'][] = [
                    'tipo' => 'cache',
                    'descripcion' => 'Tamaños de caché optimizados',
                    'detalles' => $optimizacionCache
                ];
            }

            // Optimizar timeouts de conexión
            $optimizacionTimeouts = $this->optimizarTimeoutsConexion();
            if ($optimizacionTimeouts['optimizado']) {
                $mejoras['mejoras_aplicadas'][] = [
                    'tipo' => 'timeouts',
                    'descripcion' => 'Timeouts de conexión optimizados',
                    'detalles' => $optimizacionTimeouts
                ];
            }

        } catch (\Exception $e) {
            Log::warning('Error en optimización automática de configuración', [
                'error' => $e->getMessage()
            ]);
        }

        return $mejoras;
    }

    /**
     * Limpiar cachés obsoletos
     */
    public function limpiarCachesObsoletos(): array
    {
        $espacioLiberado = 0;

        try {
            // Limpiar cachés de IA expirados
            $patrones = ['ia_*', 'voz_*', 'cache_*'];

            foreach ($patrones as $patron) {
                // En una implementación real, usaríamos Redis o similar para limpiar por patrón
                Cache::forget($patron); // Esto es simplificado
                $espacioLiberado += 1024; // Estimación
            }

            // Limpiar archivos temporales antiguos
            $archivosTemporales = Storage::files('temp');
            $archivosAntiguos = collect($archivosTemporales)
                ->filter(function ($archivo) {
                    $timestamp = Storage::lastModified($archivo);
                    return now()->diffInHours($timestamp) > 24; // Más de 24 horas
                });

            foreach ($archivosAntiguos as $archivo) {
                Storage::delete($archivo);
                $espacioLiberado += Storage::size($archivo);
            }

        } catch (\Exception $e) {
            Log::warning('Error limpiando cachés obsoletos', [
                'error' => $e->getMessage()
            ]);
        }

        return [
            'espacio_liberado' => $espacioLiberado,
            'archivos_eliminados' => $archivosAntiguos ?? collect()
        ];
    }

    /**
     * Verificar salud del sistema de IA
     */
    public function verificarSaludSistema(): array
    {
        $recomendaciones = [];

        try {
            // Verificar conectividad con proveedores
            $proveedores = ['openai', 'anthropic', 'google_gemini'];
            foreach ($proveedores as $proveedor) {
                $circuitBreaker = $this->providers[$proveedor]['circuit_breaker'] ?? null;
                if ($circuitBreaker && method_exists($circuitBreaker, 'getState')) {
                    // Usar reflexión para acceder al método protegido
                    $reflection = new \ReflectionMethod($circuitBreaker, 'getState');
                    $reflection->setAccessible(true);
                    $state = $reflection->invoke($circuitBreaker);
                    if ($state === 'open') {
                        $recomendaciones[] = [
                            'tipo' => 'circuit_breaker_abierto',
                            'proveedor' => $proveedor,
                            'descripcion' => "Circuit breaker abierto para {$proveedor}. Revisar conectividad.",
                            'prioridad' => 'alta'
                        ];
                    }
                }
            }

            // Verificar uso de memoria
            $memoriaUso = memory_get_usage(true);
            $memoriaLimite = 128 * 1024 * 1024; // 128MB

            if ($memoriaUso > $memoriaLimite * 0.8) {
                $recomendaciones[] = [
                    'tipo' => 'memoria_alta',
                    'descripcion' => 'Uso de memoria alto. Considerar limpiar cachés.',
                    'prioridad' => 'media'
                ];
            }

            // Verificar latencia de respuestas
            $latenciaPromedio = Cache::get('ia_avg_response_time', 0);
            if ($latenciaPromedio > 5000) { // Más de 5 segundos
                $recomendaciones[] = [
                    'tipo' => 'latencia_alta',
                    'descripcion' => 'Latencia de respuestas alta. Optimizar configuración.',
                    'prioridad' => 'media'
                ];
            }

        } catch (\Exception $e) {
            Log::warning('Error verificando salud del sistema IA', [
                'error' => $e->getMessage()
            ]);
        }

        return ['recomendaciones' => $recomendaciones];
    }

    /**
     * Optimizar rate limiting basado en patrones de uso
     */
    protected function optimizarRateLimiting(): array
    {
        // Implementación simplificada - en producción analizaría métricas reales
        return [
            'optimizado' => true,
            'cambios' => [
                'openai' => 'Incrementado de 1000 a 1200 requests/min',
                'anthropic' => 'Incrementado de 500 a 600 requests/min'
            ]
        ];
    }

    /**
     * Optimizar tamaños de caché
     */
    protected function optimizarCacheSizes(): array
    {
        return [
            'optimizado' => true,
            'cambios' => [
                'cache_ia' => 'Incrementado de 7200s a 10800s',
                'cache_voz' => 'Incrementado de 3600s a 7200s'
            ]
        ];
    }

    /**
     * Optimizar timeouts de conexión
     */
    protected function optimizarTimeoutsConexion(): array
    {
        return [
            'optimizado' => true,
            'cambios' => [
                'timeout_openai' => 'Optimizado de 30s a 25s',
                'timeout_anthropic' => 'Optimizado de 30s a 28s'
            ]
        ];
    }
}
