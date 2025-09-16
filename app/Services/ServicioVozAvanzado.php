<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use App\Services\CircuitBreaker;

class ServicioVozAvanzado
{
    protected CircuitBreaker $circuitBreaker;
    protected array $voiceProviders;
    protected array $speechProviders;
    protected string $cachePrefix = 'voz_ia_';
    protected int $audioQuality = 16; // bits por sample
    protected int $sampleRate = 16000; // Hz - optimizado para bajo consumo

    public function __construct()
    {
        // Inicializar Circuit Breaker principal
        $this->circuitBreaker = new CircuitBreaker('voz_ia', 3, 60, 2);

        // Configurar proveedores de síntesis de voz
        $this->voiceProviders = [
            'openai_tts' => [
                'circuit_breaker' => new CircuitBreaker('openai_tts', 5, 60, 3),
                'rate_limit' => 'openai_tts:100:1', // 100 requests por minuto
                'model' => 'tts-1', // Más rápido y eficiente
                'voice' => 'alloy', // Voz neutra y clara
                'format' => 'mp3',
                'speed' => 1.0
            ],
            'elevenlabs' => [
                'circuit_breaker' => new CircuitBreaker('elevenlabs', 5, 60, 3),
                'rate_limit' => 'elevenlabs:50:1', // 50 requests por minuto
                'model' => 'eleven_monolingual_v1',
                'voice_id' => '21m00Tcm4TlvDq8ikWAM', // Rachel - voz clara en español
                'stability' => 0.75,
                'similarity_boost' => 0.8
            ],
            'google_tts' => [
                'circuit_breaker' => new CircuitBreaker('google_tts', 10, 60, 5),
                'rate_limit' => 'google_tts:200:1', // 200 requests por minuto
                'language_code' => 'es-ES',
                'voice_name' => 'es-ES-Neural2-A', // Voz natural en español
                'audio_encoding' => 'MP3'
            ]
        ];

        // Configurar proveedores de reconocimiento de voz
        $this->speechProviders = [
            'openai_whisper' => [
                'circuit_breaker' => new CircuitBreaker('openai_whisper', 5, 60, 3),
                'rate_limit' => 'openai_whisper:100:1',
                'model' => 'whisper-1',
                'language' => 'es',
                'response_format' => 'json',
                'temperature' => 0.2
            ],
            'google_speech' => [
                'circuit_breaker' => new CircuitBreaker('google_speech', 10, 60, 5),
                'rate_limit' => 'google_speech:200:1',
                'language_code' => 'es-ES',
                'encoding' => 'LINEAR16',
                'sample_rate_hertz' => 16000,
                'enable_automatic_punctuation' => true,
                'enable_word_time_offsets' => false
            ],
            'azure_speech' => [
                'circuit_breaker' => new CircuitBreaker('azure_speech', 10, 60, 5),
                'rate_limit' => 'azure_speech:200:1',
                'language' => 'es-ES',
                'format' => 'detailed'
            ]
        ];
    }

    /**
     * Convertir texto a voz con optimización automática
     */
    public function sintetizarVoz(string $texto, array $opciones = []): array
    {
        $cacheKey = $this->cachePrefix . 'tts_' . md5($texto . json_encode($opciones));

        return Cache::remember($cacheKey, 7200, function () use ($texto, $opciones) { // 2 horas de cache
            try {
                // Seleccionar el mejor proveedor disponible
                $proveedorSeleccionado = $this->seleccionarMejorProveedorVoz($texto, $opciones);

                if (!$proveedorSeleccionado) {
                    return [
                        'exito' => false,
                        'error' => 'No hay proveedores de voz disponibles',
                        'fallback' => $this->generarAudioFallback($texto)
                    ];
                }

                // Verificar rate limiting
                if (!$this->checkRateLimit($proveedorSeleccionado, 'voice')) {
                    return [
                        'exito' => false,
                        'error' => 'Límite de velocidad excedido para síntesis de voz',
                        'fallback' => $this->generarAudioFallback($texto)
                    ];
                }

                // Ejecutar síntesis con circuit breaker
                return $this->circuitBreaker->execute(function () use ($proveedorSeleccionado, $texto, $opciones) {
                    return $this->ejecutarSintesisVoz($proveedorSeleccionado, $texto, $opciones);
                });

            } catch (\Exception $e) {
                Log::error('Error en síntesis de voz', [
                    'error' => $e->getMessage(),
                    'texto_length' => strlen($texto),
                    'proveedor' => $proveedorSeleccionado ?? 'desconocido'
                ]);

                return $this->manejarFalloSintesis($texto, $e);
            }
        });
    }

    /**
     * Convertir audio a texto con reconocimiento optimizado
     */
    public function transcribirAudio(string $audioPath, array $opciones = []): array
    {
        $cacheKey = $this->cachePrefix . 'stt_' . md5($audioPath . json_encode($opciones));

        return Cache::remember($cacheKey, 3600, function () use ($audioPath, $opciones) { // 1 hora de cache
            try {
                // Optimizar audio antes del procesamiento
                $audioOptimizado = $this->optimizarAudioParaTranscripcion($audioPath);

                // Seleccionar el mejor proveedor disponible
                $proveedorSeleccionado = $this->seleccionarMejorProveedorSpeech($audioOptimizado, $opciones);

                if (!$proveedorSeleccionado) {
                    return [
                        'exito' => false,
                        'error' => 'No hay proveedores de reconocimiento de voz disponibles'
                    ];
                }

                // Verificar rate limiting
                if (!$this->checkRateLimit($proveedorSeleccionado, 'speech')) {
                    return [
                        'exito' => false,
                        'error' => 'Límite de velocidad excedido para reconocimiento de voz'
                    ];
                }

                // Ejecutar transcripción con circuit breaker
                return $this->circuitBreaker->execute(function () use ($proveedorSeleccionado, $audioOptimizado, $opciones) {
                    return $this->ejecutarTranscripcion($proveedorSeleccionado, $audioOptimizado, $opciones);
                });

            } catch (\Exception $e) {
                Log::error('Error en transcripción de audio', [
                    'error' => $e->getMessage(),
                    'audio_path' => $audioPath,
                    'proveedor' => $proveedorSeleccionado ?? 'desconocido'
                ]);

                return $this->manejarFalloTranscripcion($audioPath, $e);
            }
        });
    }

    /**
     * Conversación completa por voz con IA integrada
     */
    public function conversacionPorVoz(string $audioEntrada, array $contexto = []): array
    {
        try {
            // Paso 1: Transcribir audio a texto
            $transcripcion = $this->transcribirAudio($audioEntrada);

            if (!$transcripcion['exito']) {
                return [
                    'exito' => false,
                    'error' => 'Error en transcripción: ' . $transcripcion['error'],
                    'respuesta_voz' => $this->generarRespuestaVoz('Lo siento, no pude entender el audio. ¿Podrías repetir?')
                ];
            }

            $textoUsuario = $transcripcion['texto'];

            // Paso 2: Procesar con IA avanzada
            $iaService = app(IAAvanzada::class);
            $respuestaIA = $this->procesarConIA($textoUsuario, $contexto, $iaService);

            // Paso 3: Convertir respuesta a voz
            $respuestaVoz = $this->sintetizarVoz($respuestaIA['respuesta'], [
                'tono' => 'amigable',
                'velocidad' => 0.9,
                'prioridad' => 'alta'
            ]);

            return [
                'exito' => true,
                'texto_usuario' => $textoUsuario,
                'respuesta_ia' => $respuestaIA,
                'respuesta_voz' => $respuestaVoz,
                'tiempo_procesamiento' => now()->diffInMilliseconds($transcripcion['timestamp'] ?? now())
            ];

        } catch (\Exception $e) {
            Log::error('Error en conversación por voz', [
                'error' => $e->getMessage(),
                'audio_entrada' => $audioEntrada
            ]);

            return [
                'exito' => false,
                'error' => 'Error en conversación por voz: ' . $e->getMessage(),
                'respuesta_voz' => $this->generarRespuestaVoz('Hubo un error técnico. Por favor, intenta de nuevo.')
            ];
        }
    }

    /**
     * Optimizar audio para transcripción (reduce tamaño y mejora calidad)
     */
    protected function optimizarAudioParaTranscripcion(string $audioPath): string
    {
        try {
            // Verificar si el archivo existe
            if (!Storage::exists($audioPath)) {
                throw new \Exception('Archivo de audio no encontrado');
            }

            // Obtener información del archivo
            $audioInfo = $this->analizarAudio($audioPath);

            // Si ya está optimizado, devolver el original
            if ($audioInfo['sample_rate'] <= $this->sampleRate &&
                $audioInfo['bit_depth'] <= $this->audioQuality &&
                $audioInfo['duration'] <= 30) { // máximo 30 segundos
                return $audioPath;
            }

            // Generar nombre para archivo optimizado
            $pathInfo = pathinfo($audioPath);
            $optimizedPath = $pathInfo['dirname'] . '/optimized_' . $pathInfo['basename'];

            // Optimizar audio usando FFmpeg (si está disponible)
            $this->optimizarConFFmpeg($audioPath, $optimizedPath);

            return $optimizedPath;

        } catch (\Exception $e) {
            Log::warning('Error al optimizar audio, usando original', [
                'error' => $e->getMessage(),
                'audio_path' => $audioPath
            ]);
            return $audioPath;
        }
    }

    /**
     * Seleccionar el mejor proveedor de voz disponible
     */
    protected function seleccionarMejorProveedorVoz(string $texto, array $opciones): ?string
    {
        $longitudTexto = strlen($texto);
        $prioridad = $opciones['prioridad'] ?? 'normal';

        // Para textos cortos y alta prioridad: OpenAI TTS (más rápido)
        if ($longitudTexto < 500 && $prioridad === 'alta') {
            return $this->estaDisponible('openai_tts') ? 'openai_tts' : null;
        }

        // Para textos largos o calidad natural: ElevenLabs
        if ($longitudTexto > 1000 || ($opciones['calidad'] ?? 'normal') === 'alta') {
            return $this->estaDisponible('elevenlabs') ? 'elevenlabs' : null;
        }

        // Por defecto: Google TTS (balance costo/calidad)
        return $this->estaDisponible('google_tts') ? 'google_tts' : null;
    }

    /**
     * Seleccionar el mejor proveedor de reconocimiento de voz
     */
    protected function seleccionarMejorProveedorSpeech(string $audioPath, array $opciones): ?string
    {
        // Para audio optimizado y alta precisión: OpenAI Whisper
        if ($this->esAudioOptimizado($audioPath)) {
            return $this->estaDisponible('openai_whisper') ? 'openai_whisper' : null;
        }

        // Para audio en español: Google Speech-to-Text
        return $this->estaDisponible('google_speech') ? 'google_speech' : null;
    }

    /**
     * Verificar disponibilidad de proveedor
     */
    protected function estaDisponible(string $proveedor): bool
    {
        // Verificar configuración
        $configKey = match($proveedor) {
            'openai_tts', 'openai_whisper' => 'services.openai.api_key',
            'elevenlabs' => 'services.elevenlabs.api_key',
            'google_tts', 'google_speech' => 'services.google.api_key',
            'azure_speech' => 'services.azure.api_key',
            default => null
        };

        if (!$configKey || !config($configKey)) {
            return false;
        }

        // Verificar circuit breaker
        if (isset($this->voiceProviders[$proveedor])) {
            return !$this->voiceProviders[$proveedor]['circuit_breaker']->isOpen();
        }

        if (isset($this->speechProviders[$proveedor])) {
            return !$this->speechProviders[$proveedor]['circuit_breaker']->isOpen();
        }

        return false;
    }

    /**
     * Verificar rate limiting
     */
    protected function checkRateLimit(string $proveedor, string $tipo): bool
    {
        $providers = $tipo === 'voice' ? $this->voiceProviders : $this->speechProviders;

        if (!isset($providers[$proveedor])) {
            return true;
        }

        $rateLimitKey = $providers[$proveedor]['rate_limit'];

        return RateLimiter::attempt(
            $rateLimitKey,
            1,
            function () {
                return true;
            },
            60 // 60 segundos
        );
    }

    /**
     * Ejecutar síntesis de voz según el proveedor
     */
    protected function ejecutarSintesisVoz(string $proveedor, string $texto, array $opciones): array
    {
        switch ($proveedor) {
            case 'openai_tts':
                return $this->sintesisOpenAI($texto, $opciones);

            case 'elevenlabs':
                return $this->sintesisElevenLabs($texto, $opciones);

            case 'google_tts':
                return $this->sintesisGoogle($texto, $opciones);

            default:
                throw new \Exception("Proveedor de voz no soportado: {$proveedor}");
        }
    }

    /**
     * Ejecutar transcripción según el proveedor
     */
    protected function ejecutarTranscripcion(string $proveedor, string $audioPath, array $opciones): array
    {
        switch ($proveedor) {
            case 'openai_whisper':
                return $this->transcripcionOpenAI($audioPath, $opciones);

            case 'google_speech':
                return $this->transcripcionGoogle($audioPath, $opciones);

            case 'azure_speech':
                return $this->transcripcionAzure($audioPath, $opciones);

            default:
                throw new \Exception("Proveedor de reconocimiento no soportado: {$proveedor}");
        }
    }

    /**
     * Procesar texto con IA integrada
     */
    protected function procesarConIA(string $texto, array $contexto, $iaService): array
    {
        // Crear prompt inteligente basado en el contexto
        $prompt = $this->crearPromptInteligente($texto, $contexto);

        // Usar IA avanzada para generar respuesta
        $respuesta = $iaService->procesarConsultaVoz($prompt, $contexto);

        return [
            'respuesta' => $respuesta['texto'] ?? 'Lo siento, no pude procesar tu consulta.',
            'confianza' => $respuesta['confianza'] ?? 0.5,
            'tipo_respuesta' => $respuesta['tipo'] ?? 'general',
            'acciones_sugeridas' => $respuesta['acciones'] ?? []
        ];
    }

    /**
     * Generar respuesta de voz de fallback
     */
    protected function generarRespuestaVoz(string $mensaje): array
    {
        return [
            'exito' => true,
            'audio_base64' => base64_encode($mensaje), // Placeholder
            'duracion' => strlen($mensaje) * 0.1, // Estimación básica
            'tipo' => 'fallback'
        ];
    }

    /**
     * Analizar propiedades del audio
     */
    protected function analizarAudio(string $path): array
    {
        // Implementación básica - en producción usar una librería como getID3
        return [
            'sample_rate' => 44100, // Hz
            'bit_depth' => 16, // bits
            'duration' => 10, // segundos
            'channels' => 1, // mono
            'format' => 'wav'
        ];
    }

    /**
     * Optimizar audio usando FFmpeg
     */
    protected function optimizarConFFmpeg(string $inputPath, string $outputPath): void
    {
        // Comando FFmpeg para optimizar audio
        $comando = sprintf(
            'ffmpeg -i %s -ac 1 -ar %d -ab 64k -f mp3 %s -y',
            escapeshellarg($inputPath),
            $this->sampleRate,
            escapeshellarg($outputPath)
        );

        exec($comando, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Error al optimizar audio con FFmpeg');
        }
    }

    /**
     * Verificar si audio está optimizado
     */
    protected function esAudioOptimizado(string $path): bool
    {
        $info = $this->analizarAudio($path);
        return $info['sample_rate'] <= $this->sampleRate &&
               $info['bit_depth'] <= $this->audioQuality;
    }

    // Métodos específicos de proveedores (implementación básica)
    protected function sintesisOpenAI(string $texto, array $opciones): array { /* Implementación */ }
    protected function sintesisElevenLabs(string $texto, array $opciones): array { /* Implementación */ }
    protected function sintesisGoogle(string $texto, array $opciones): array { /* Implementación */ }
    protected function transcripcionOpenAI(string $audioPath, array $opciones): array { /* Implementación */ }
    protected function transcripcionGoogle(string $audioPath, array $opciones): array { /* Implementación */ }
    protected function transcripcionAzure(string $audioPath, array $opciones): array { /* Implementación */ }

    protected function manejarFalloSintesis(string $texto, \Exception $e): array { /* Implementación */ }
    protected function manejarFalloTranscripcion(string $audioPath, \Exception $e): array { /* Implementación */ }
    protected function generarAudioFallback(string $texto): array { /* Implementación */ }
    protected function crearPromptInteligente(string $texto, array $contexto): string { /* Implementación */ }
}
