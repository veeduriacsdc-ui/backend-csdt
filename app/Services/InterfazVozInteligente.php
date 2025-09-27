<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\CircuitBreaker;
use App\Services\IAAvanzada;
use App\Services\ServicioVozAvanzado;

/**
 * Interfaz de Voz Inteligente para CSDT
 * Control de sistemas mediante voz en español con integración de IAs
 */
class InterfazVozInteligente
{
    protected ServicioVozAvanzado $servicioVoz;
    protected IAAvanzada $servicioIA;
    protected CircuitBreaker $circuitBreaker;

    // Comandos de voz disponibles
    protected array $comandosSistema = [
        'es' => [
            'consultar' => ['consultar', 'buscar', 'información sobre', 'dime sobre'],
            'crear' => ['crear', 'nuevo', 'registrar', 'agregar'],
            'editar' => ['editar', 'modificar', 'cambiar', 'actualizar'],
            'eliminar' => ['eliminar', 'borrar', 'quitar'],
            'listar' => ['listar', 'mostrar', 'ver', 'consultar todos'],
            'ayuda' => ['ayuda', 'help', 'ayúdame', 'qué puedo hacer'],
            'estado' => ['estado', 'cómo estás', 'status', 'información del sistema'],
            'salir' => ['salir', 'terminar', 'chau', 'adiós']
        ],
        'en' => [
            'consultar' => ['consult', 'search', 'information about', 'tell me about'],
            'crear' => ['create', 'new', 'register', 'add'],
            'editar' => ['edit', 'modify', 'change', 'update'],
            'eliminar' => ['delete', 'remove', 'erase'],
            'listar' => ['list', 'show', 'view', 'consult all'],
            'ayuda' => ['help', 'aid', 'what can I do'],
            'estado' => ['status', 'how are you', 'system information'],
            'salir' => ['exit', 'quit', 'bye', 'goodbye']
        ]
    ];

    protected array $modulosSistema = [
        'pqrsfd' => ['pqrsfd', 'petición', 'queja', 'reclamo', 'sugerencia', 'denuncia', 'felicitación'],
        'usuarios' => ['usuario', 'cliente', 'operador', 'administrador', 'perfil'],
        'dashboard' => ['dashboard', 'panel', 'estadísticas', 'métricas', 'gráficos'],
        'mapas' => ['mapa', 'geográfico', 'ubicación', 'territorial'],
        'documentos' => ['documento', 'archivo', 'expediente', 'caso'],
        'reportes' => ['reporte', 'informe', 'estadística', 'análisis']
    ];

    public function __construct(
        ServicioVozAvanzado $servicioVoz,
        IAAvanzada $servicioIA
    ) {
        $this->servicioVoz = $servicioVoz;
        $this->servicioIA = $servicioIA;
        $this->circuitBreaker = new CircuitBreaker('interfaz_voz_ia', 5, 60, 3);
    }

    /**
     * Procesar comando de voz completo
     */
    public function procesarComandoVoz(string $audioPath, array $contexto = []): array
    {
        try {
            if ($this->circuitBreaker->isOpen()) {
                return $this->respuestaFallback('Sistema temporalmente no disponible');
            }

            // 1. Transcribir audio a texto
            $transcripcion = $this->transcribirAudio($audioPath);
            if (!$transcripcion['exito']) {
                return $this->respuestaFallback('No pude entender el audio');
            }

            $textoUsuario = $transcripcion['texto'];
            $idiomaDetectado = $transcripcion['idioma'] ?? 'es';

            // 2. Analizar intención del usuario
            $intencion = $this->analizarIntencion($textoUsuario, $idiomaDetectado);

            // 3. Ejecutar comando según intención
            $resultadoComando = $this->ejecutarComando($intencion, $contexto);

            // 4. Generar respuesta inteligente
            $respuestaIA = $this->generarRespuestaIA($textoUsuario, $resultadoComando, $idiomaDetectado);

            // 5. Convertir respuesta a voz
            $respuestaVoz = $this->generarRespuestaVoz($respuestaIA['respuesta'], $idiomaDetectado);

            // 6. Registrar interacción
            $this->registrarInteraccion($textoUsuario, $respuestaIA, $intencion);

            return [
                'exito' => true,
                'texto_usuario' => $textoUsuario,
                'idioma_detectado' => $idiomaDetectado,
                'intencion' => $intencion,
                'respuesta_ia' => $respuestaIA,
                'respuesta_voz' => $respuestaVoz,
                'tiempo_procesamiento' => now()->diffInMilliseconds($inicio ?? now())
            ];

        } catch (\Exception $e) {
            Log::error('Error en procesarComandoVoz', [
                'error' => $e->getMessage(),
                'audio_path' => $audioPath
            ]);

            $this->circuitBreaker->recordFailure();
            return $this->respuestaFallback('Error interno del sistema');
        }
    }

    /**
     * Transcribir audio usando múltiples proveedores
     */
    protected function transcribirAudio(string $audioPath): array
    {
        // Intentar primero con OpenAI Whisper
        if (config('services.openai.api_key')) {
            try {
                return $this->servicioVoz->transcribirAudio($audioPath, [
                    'proveedor' => 'openai_whisper',
                    'idioma' => 'es'
                ]);
            } catch (\Exception $e) {
                Log::warning('Error con OpenAI Whisper', ['error' => $e->getMessage()]);
            }
        }

        // Fallback a Google Speech
        if (config('services.google_cloud.project_id')) {
            try {
                return $this->servicioVoz->transcribirAudio($audioPath, [
                    'proveedor' => 'google_speech',
                    'idioma' => 'es-ES'
                ]);
            } catch (\Exception $e) {
                Log::warning('Error con Google Speech', ['error' => $e->getMessage()]);
            }
        }

        // Último fallback a Azure
        if (config('services.azure_cognitive.api_key')) {
            try {
                return $this->servicioVoz->transcribirAudio($audioPath, [
                    'proveedor' => 'azure_speech',
                    'idioma' => 'es-ES'
                ]);
            } catch (\Exception $e) {
                Log::error('Error con Azure Speech', ['error' => $e->getMessage()]);
            }
        }

        return [
            'exito' => false,
            'error' => 'No hay proveedores de transcripción disponibles'
        ];
    }

    /**
     * Analizar intención del usuario
     */
    protected function analizarIntencion(string $texto, string $idioma = 'es'): array
    {
        $texto = strtolower($texto);
        $comandos = $this->comandosSistema[$idioma] ?? $this->comandosSistema['es'];

        // Detectar tipo de comando
        $tipoComando = null;
        foreach ($comandos as $tipo => $palabrasClave) {
            foreach ($palabrasClave as $palabra) {
                if (str_contains($texto, $palabra)) {
                    $tipoComando = $tipo;
                    break 2;
                }
            }
        }

        // Detectar módulo del sistema
        $modulo = null;
        foreach ($this->modulosSistema as $moduloNombre => $palabrasModulo) {
            foreach ($palabrasModulo as $palabra) {
                if (str_contains($texto, $palabra)) {
                    $modulo = $moduloNombre;
                    break 2;
                }
            }
        }

        // Extraer entidades específicas
        $entidades = $this->extraerEntidades($texto, $idioma);

        return [
            'tipo' => $tipoComando ?? 'consultar',
            'modulo' => $modulo ?? 'general',
            'entidades' => $entidades,
            'texto_original' => $texto,
            'confianza' => $this->calcularConfianzaIntencion($texto, $tipoComando, $modulo)
        ];
    }

    /**
     * Extraer entidades del texto
     */
    protected function extraerEntidades(string $texto, string $idioma): array
    {
        $entidades = [];

        // Buscar números de identificación
        if (preg_match('/\b\d{8,12}\b/', $texto, $matches)) {
            $entidades['id'] = $matches[0];
        }

        // Buscar fechas
        if (preg_match('/\b\d{1,2}[-\/]\d{1,2}[-\/]\d{4}\b/', $texto, $matches)) {
            $entidades['fecha'] = $matches[0];
        }

        // Buscar emails
        if (preg_match('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', $texto, $matches)) {
            $entidades['email'] = $matches[0];
        }

        // Buscar tipos de PQRSFD
        $tiposPQRSFD = ['petición', 'queja', 'reclamo', 'sugerencia', 'denuncia', 'felicitación'];
        foreach ($tiposPQRSFD as $tipo) {
            if (str_contains($texto, $tipo)) {
                $entidades['tipo_pqrsfd'] = $tipo;
                break;
            }
        }

        return $entidades;
    }

    /**
     * Ejecutar comando según intención
     */
    protected function ejecutarComando(array $intencion, array $contexto): array
    {
        try {
            switch ($intencion['tipo']) {
                case 'consultar':
                    return $this->ejecutarConsulta($intencion, $contexto);
                case 'crear':
                    return $this->ejecutarCreacion($intencion, $contexto);
                case 'editar':
                    return $this->ejecutarEdicion($intencion, $contexto);
                case 'eliminar':
                    return $this->ejecutarEliminacion($intencion, $contexto);
                case 'listar':
                    return $this->ejecutarListado($intencion, $contexto);
                case 'ayuda':
                    return $this->ejecutarAyuda($intencion, $contexto);
                default:
                    return $this->ejecutarConsultaGeneral($intencion, $contexto);
            }
        } catch (\Exception $e) {
            Log::error('Error ejecutando comando', [
                'intencion' => $intencion,
                'error' => $e->getMessage()
            ]);

            return [
                'exito' => false,
                'error' => 'Error ejecutando el comando solicitado',
                'tipo' => 'error'
            ];
        }
    }

    /**
     * Ejecutar consulta inteligente
     */
    protected function ejecutarConsulta(array $intencion, array $contexto): array
    {
        // Consultar múltiples IAs para obtener información completa
        $resultadosIA = [];

        // OpenAI GPT-4
        if (config('services.openai.api_key')) {
            try {
                $consulta = "Información sobre: {$intencion['modulo']} - {$intencion['texto_original']}";
                $resultado = $this->servicioIA->consultarOpenAI($consulta, [
                    'modelo' => 'gpt-4',
                    'temperatura' => 0.3
                ]);
                $resultadosIA['openai'] = $resultado;
            } catch (\Exception $e) {
                Log::warning('Error consultando OpenAI', ['error' => $e->getMessage()]);
            }
        }

        // Anthropic Claude
        if (config('services.anthropic.api_key')) {
            try {
                $consulta = "Proporciona información precisa sobre: {$intencion['modulo']} - {$intencion['texto_original']}";
                $resultado = $this->servicioIA->consultarAnthropic($consulta, [
                    'modelo' => 'claude-3-sonnet-20240229',
                    'temperatura' => 0.3
                ]);
                $resultadosIA['anthropic'] = $resultado;
            } catch (\Exception $e) {
                Log::warning('Error consultando Anthropic', ['error' => $e->getMessage()]);
            }
        }

        // Google Gemini
        if (config('services.google_gemini.api_key')) {
            try {
                $consulta = "Responde de manera útil sobre: {$intencion['modulo']} - {$intencion['texto_original']}";
                $resultado = $this->servicioIA->consultarGoogleGemini($consulta, [
                    'modelo' => 'gemini-pro',
                    'temperatura' => 0.3
                ]);
                $resultadosIA['google'] = $resultado;
            } catch (\Exception $e) {
                Log::warning('Error consultando Google Gemini', ['error' => $e->getMessage()]);
            }
        }

        // Combinar resultados de todas las IAs
        $respuestaConsolidada = $this->consolidarResultadosIA($resultadosIA, $intencion);

        return [
            'exito' => true,
            'tipo' => 'consulta',
            'resultados' => $resultadosIA,
            'respuesta_consolidada' => $respuestaConsolidada,
            'modulo' => $intencion['modulo']
        ];
    }

    /**
     * Ejecutar ayuda del sistema
     */
    protected function ejecutarAyuda(array $intencion, array $contexto): array
    {
        $ayuda = [
            'mensaje' => '¡Hola! Soy tu asistente de voz inteligente del CSDT. Puedo ayudarte con:',
            'funciones' => [
                '📋 Consultas sobre PQRSFD, usuarios, documentos y reportes',
                '➕ Crear nuevos registros y expedientes',
                '✏️ Editar información existente',
                '🗑️ Eliminar registros cuando sea necesario',
                '📊 Mostrar estadísticas y métricas',
                '🎯 Navegar por diferentes módulos del sistema',
                'ℹ️ Proporcionar información del estado del sistema'
            ],
            'comandos_voz' => [
                '"Consultar PQRSFD número 12345"',
                '"Crear nueva petición sobre servicios públicos"',
                '"Mostrar estadísticas del mes"',
                '"Estado del sistema"',
                '"Ayuda" para más información'
            ],
            'idiomas' => 'Puedo responder en español, inglés y portugués'
        ];

        return [
            'exito' => true,
            'tipo' => 'ayuda',
            'ayuda' => $ayuda
        ];
    }


    /**
     * Generar respuesta inteligente usando múltiples IAs
     */
    protected function generarRespuestaIA(string $consultaUsuario, array $resultadoComando, string $idioma): array
    {
        try {
            // Crear contexto para la IA
            $contexto = [
                'consulta_usuario' => $consultaUsuario,
                'resultado_comando' => $resultadoComando,
                'idioma' => $idioma,
                'timestamp' => now()->toISOString(),
                'sistema' => 'CSDT - Control Social, Justicia y Transparencia'
            ];

            // Usar OpenAI para generar respuesta principal
            if (config('services.openai.api_key')) {
                $prompt = $this->crearPromptRespuesta($contexto);

                $respuesta = $this->servicioIA->consultarOpenAI($prompt, [
                    'modelo' => 'gpt-4',
                    'temperatura' => 0.7,
                    'max_tokens' => 500
                ]);

                if ($respuesta['exito']) {
                    return [
                        'respuesta' => $this->traducirRespuesta($respuesta['respuesta'], $idioma),
                        'tipo_respuesta' => $resultadoComando['tipo'] ?? 'general',
                        'acciones_sugeridas' => $this->generarAccionesSugeridas($resultadoComando),
                        'confianza' => $respuesta['confianza'] ?? 0.8
                    ];
                }
            }

            // Fallback a respuesta básica
            return $this->generarRespuestaBasica($resultadoComando, $idioma);

        } catch (\Exception $e) {
            Log::error('Error generando respuesta IA', ['error' => $e->getMessage()]);
            return $this->generarRespuestaBasica($resultadoComando, $idioma);
        }
    }

    /**
     * Generar respuesta de voz
     */
    protected function generarRespuestaVoz(string $texto, string $idioma): array
    {
        try {
            // Configurar voz según idioma
            $configVoz = $this->configurarVozPorIdioma($idioma);

            return $this->servicioVoz->sintetizarVoz($texto, $configVoz);
        } catch (\Exception $e) {
            Log::error('Error generando voz', ['error' => $e->getMessage()]);
            return [
                'exito' => false,
                'error' => 'Error generando respuesta de voz'
            ];
        }
    }

    /**
     * Configurar voz según idioma
     */
    protected function configurarVozPorIdioma(string $idioma): array
    {
        $configuraciones = [
            'es' => [
                'proveedor' => 'elevenlabs',
                'voz' => '21m00Tcm4TlvDq8ikWAM', // Rachel - voz española natural
                'velocidad' => 1.0,
                'idioma' => 'es-ES'
            ],
            'en' => [
                'proveedor' => 'openai_tts',
                'voz' => 'alloy',
                'velocidad' => 1.0,
                'idioma' => 'en-US'
            ],
            'pt' => [
                'proveedor' => 'google_tts',
                'voz' => 'pt-BR-Neural2-A',
                'velocidad' => 1.0,
                'idioma' => 'pt-BR'
            ]
        ];

        return $configuraciones[$idioma] ?? $configuraciones['es'];
    }

    /**
     * Crear prompt para respuesta IA
     */
    protected function crearPromptRespuesta(array $contexto): string
    {
        $tipoConsulta = $contexto['resultado_comando']['tipo'] ?? 'general';

        $prompts = [
            'consulta' => "Eres un asistente inteligente del sistema CSDT (Control Social, Justicia y Transparencia). Un usuario preguntó: '{$contexto['consulta_usuario']}'. Los resultados de la consulta son: " . json_encode($contexto['resultado_comando']) . ". Proporciona una respuesta clara, útil y concisa en español.",
            'ayuda' => "Eres un asistente del sistema CSDT. El usuario pidió ayuda. Explica brevemente las funcionalidades disponibles del sistema de manera clara y amigable.",
            'estado' => "Eres un asistente del sistema CSDT. El usuario preguntó por el estado del sistema. Resume la información del estado de manera clara y técnica.",
            'error' => "Eres un asistente del sistema CSDT. Ocurrió un error. Explica al usuario qué sucedió de manera comprensiva y sugiere alternativas."
        ];

        return $prompts[$tipoConsulta] ?? $prompts['consulta'];
    }

    /**
     * Traducir respuesta según idioma
     */
    protected function traducirRespuesta(string $respuesta, string $idioma): string
    {
        if ($idioma === 'es') {
            return $respuesta; // Ya está en español
        }

        // Aquí se podría integrar un servicio de traducción
        // Por ahora, devolver la respuesta original con nota
        return $respuesta . " (Respuesta en español - traducción automática próximamente)";
    }

    /**
     * Generar acciones sugeridas
     */
    protected function generarAccionesSugeridas(array $resultadoComando): array
    {
        $acciones = [];

        switch ($resultadoComando['tipo']) {
            case 'consulta':
                $acciones[] = '¿Quieres ver más detalles?';
                $acciones[] = '¿Necesitas ayuda con algo más?';
                break;
            case 'ayuda':
                $acciones[] = '¿Te gustaría probar algún comando específico?';
                $acciones[] = '¿Quieres saber más sobre alguna funcionalidad?';
                break;
            case 'estado':
                $acciones[] = '¿Quieres verificar algún servicio específico?';
                break;
        }

        return $acciones;
    }

    /**
     * Generar respuesta básica cuando falla la IA
     */
    protected function generarRespuestaBasica(array $resultadoComando, string $idioma): array
    {
        $respuestas = [
            'es' => [
                'consulta' => 'He consultado la información solicitada. Los detalles están disponibles en el sistema.',
                'ayuda' => 'Estoy aquí para ayudarte. Di "ayuda" para ver todas las opciones disponibles.',
                'estado' => 'El sistema CSDT está funcionando correctamente.',
                'error' => 'Lo siento, ocurrió un error procesando tu solicitud. Por favor, inténtalo nuevamente.'
            ],
            'en' => [
                'consulta' => 'I have consulted the requested information. Details are available in the system.',
                'ayuda' => 'I am here to help you. Say "help" to see all available options.',
                'estado' => 'The CSDT system is working correctly.',
                'error' => 'Sorry, an error occurred processing your request. Please try again.'
            ]
        ];

        $idiomaRespuestas = $respuestas[$idioma] ?? $respuestas['es'];
        $tipoRespuesta = $resultadoComando['tipo'] ?? 'general';

        return [
            'respuesta' => $idiomaRespuestas[$tipoRespuesta] ?? $idiomaRespuestas['consulta'],
            'tipo_respuesta' => $tipoRespuesta,
            'acciones_sugeridas' => [],
            'confianza' => 0.5
        ];
    }

    /**
     * Consolidar resultados de múltiples IAs
     */
    protected function consolidarResultadosIA(array $resultadosIA, array $intencion): string
    {
        if (empty($resultadosIA)) {
            return 'No se pudieron obtener resultados de las inteligencias artificiales.';
        }

        // Combinar respuestas de diferentes IAs
        $respuestas = [];
        foreach ($resultadosIA as $proveedor => $resultado) {
            if ($resultado['exito'] && !empty($resultado['respuesta'])) {
                $respuestas[] = "Según {$proveedor}: {$resultado['respuesta']}";
            }
        }

        if (empty($respuestas)) {
            return 'No se obtuvieron respuestas válidas de las inteligencias artificiales.';
        }

        // Retornar la primera respuesta válida
        return $respuestas[0];
    }

    /**
     * Calcular confianza de la intención
     */
    protected function calcularConfianzaIntencion(string $texto, ?string $tipoComando, ?string $modulo): float
    {
        $confianza = 0.0;

        if ($tipoComando) $confianza += 0.4;
        if ($modulo) $confianza += 0.4;

        // Bonus por palabras específicas
        $palabrasConfianza = ['por favor', 'necesito', 'quisiera', 'puedes'];
        foreach ($palabrasConfianza as $palabra) {
            if (str_contains($texto, $palabra)) {
                $confianza += 0.1;
                break;
            }
        }

        return min($confianza, 1.0);
    }


    /**
     * Registrar interacción para análisis
     */
    protected function registrarInteraccion(string $consulta, array $respuesta, array $intencion): void
    {
        try {
            Log::info('Interacción de voz registrada', [
                'consulta' => $consulta,
                'tipo_intencion' => $intencion['tipo'],
                'modulo' => $intencion['modulo'],
                'confianza' => $intencion['confianza'],
                'respuesta_tipo' => $respuesta['tipo_respuesta'] ?? 'desconocido',
                'usuario' => auth()->id(),
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            // No fallar por error de logging
        }
    }

    /**
     * Respuesta de fallback
     */
    protected function respuestaFallback(string $mensaje): array
    {
        return [
            'exito' => false,
            'error' => $mensaje,
            'respuesta_voz' => $this->generarRespuestaVoz($mensaje, 'es')
        ];
    }

    // Métodos de comandos específicos que pueden expandirse
    protected function ejecutarConsultaGeneral(array $intencion, array $contexto): array { return ['exito' => true, 'tipo' => 'general']; }
    protected function ejecutarCreacion(array $intencion, array $contexto): array { return ['exito' => false, 'error' => 'Función no implementada']; }
    protected function ejecutarEdicion(array $intencion, array $contexto): array { return ['exito' => false, 'error' => 'Función no implementada']; }
    protected function ejecutarEliminacion(array $intencion, array $contexto): array { return ['exito' => false, 'error' => 'Función no implementada']; }
    protected function ejecutarListado(array $intencion, array $contexto): array { return ['exito' => false, 'error' => 'Función no implementada']; }
}
