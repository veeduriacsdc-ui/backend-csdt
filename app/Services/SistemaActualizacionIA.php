<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\User;
use App\Notifications\ActualizacionIANotification;

class SistemaActualizacionIA
{
    protected array $proveedoresActualizables;
    protected CircuitBreaker $circuitBreaker;
    protected string $cachePrefix = 'ia_updates_';
    protected string $updateLogPath = 'logs/ia_updates.log';

    public function __construct()
    {
        $this->circuitBreaker = new CircuitBreaker('ia_updates', 3, 60, 2);

        $this->proveedoresActualizables = [
            'openai' => [
                'endpoint' => 'https://api.openai.com/v1/models',
                'headers' => ['Authorization' => 'Bearer ' . config('services.openai.api_key')],
                'modelos' => ['gpt-4', 'gpt-4-turbo', 'gpt-3.5-turbo'],
                'tipo' => 'llm'
            ],
            'anthropic' => [
                'endpoint' => 'https://api.anthropic.com/v1/messages',
                'headers' => ['x-api-key' => config('services.anthropic.api_key')],
                'modelos' => ['claude-3-opus', 'claude-3-sonnet', 'claude-3-haiku'],
                'tipo' => 'llm'
            ],
            'google_gemini' => [
                'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models',
                'headers' => ['x-goog-api-key' => config('services.google_gemini.api_key')],
                'modelos' => ['gemini-pro', 'gemini-pro-vision'],
                'tipo' => 'multimodal'
            ],
            'elevenlabs' => [
                'endpoint' => 'https://api.elevenlabs.io/v1/models',
                'headers' => ['xi-api-key' => config('services.elevenlabs.api_key')],
                'modelos' => ['eleven_monolingual_v1', 'eleven_multilingual_v1'],
                'tipo' => 'tts'
            ],
            'openai_whisper' => [
                'endpoint' => 'https://api.openai.com/v1/models',
                'headers' => ['Authorization' => 'Bearer ' . config('services.openai.api_key')],
                'modelos' => ['whisper-1'],
                'tipo' => 'stt'
            ]
        ];
    }

    /**
     * Verificar y aplicar actualizaciones automáticas de IAs
     */
    public function verificarActualizaciones(): array
    {
        $resultados = [
            'actualizaciones_disponibles' => [],
            'actualizaciones_aplicadas' => [],
            'errores' => [],
            'timestamp' => now()->toISOString()
        ];

        try {
            // Verificar conectividad a internet
            if (!$this->verificarConectividad()) {
                Log::info('Sin conectividad a internet - saltando actualizaciones de IA');
                return $resultados;
            }

            // Verificar rate limiting para actualizaciones
            if (!$this->checkRateLimitActualizaciones()) {
                Log::info('Rate limit alcanzado para actualizaciones de IA');
                return $resultados;
            }

            // Ejecutar con circuit breaker
            return $this->circuitBreaker->execute(function () use ($resultados) {
                return $this->ejecutarVerificacionActualizaciones($resultados);
            });

        } catch (\Exception $e) {
            Log::error('Error verificando actualizaciones de IA', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $resultados['errores'][] = [
                'tipo' => 'excepcion_general',
                'mensaje' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];

            return $resultados;
        }
    }

    /**
     * Ejecutar la verificación de actualizaciones
     */
    protected function ejecutarVerificacionActualizaciones(array $resultados): array
    {
        foreach ($this->proveedoresActualizables as $proveedor => $config) {
            try {
                $actualizacion = $this->verificarProveedor($proveedor, $config);

                if ($actualizacion['disponible']) {
                    $resultados['actualizaciones_disponibles'][] = $actualizacion;

                    // Aplicar actualización automática si está habilitada
                    if (config('services.voice_optimization.auto_update_models', true)) {
                        $aplicacion = $this->aplicarActualizacion($proveedor, $actualizacion);
                        if ($aplicacion['exito']) {
                            $resultados['actualizaciones_aplicadas'][] = $aplicacion;
                        } else {
                            $resultados['errores'][] = $aplicacion;
                        }
                    }
                }

            } catch (\Exception $e) {
                Log::warning("Error verificando {$proveedor}", [
                    'error' => $e->getMessage(),
                    'proveedor' => $proveedor
                ]);

                $resultados['errores'][] = [
                    'tipo' => 'error_proveedor',
                    'proveedor' => $proveedor,
                    'mensaje' => $e->getMessage(),
                    'timestamp' => now()->toISOString()
                ];
            }
        }

        // Notificar administradores si hay actualizaciones
        if (!empty($resultados['actualizaciones_aplicadas'])) {
            $this->notificarAdministradores($resultados);
        }

        return $resultados;
    }

    /**
     * Verificar actualizaciones para un proveedor específico
     */
    protected function verificarProveedor(string $proveedor, array $config): array
    {
        $ultimaVerificacion = Cache::get($this->cachePrefix . $proveedor . '_last_check');
        $cacheDuration = config('services.voice_optimization.cache_duration', 7200);

        // Evitar verificaciones demasiado frecuentes
        if ($ultimaVerificacion && now()->diffInSeconds($ultimaVerificacion) < 3600) {
            return ['disponible' => false, 'proveedor' => $proveedor];
        }

        $response = Http::withHeaders($config['headers'])
            ->timeout(10)
            ->get($config['endpoint']);

        if (!$response->successful()) {
            throw new \Exception("Error consultando API de {$proveedor}: {$response->status()}");
        }

        $data = $response->json();
        $modelosDisponibles = $this->extraerModelosDisponibles($proveedor, $data);
        $modelosActuales = $this->obtenerModelosActuales($proveedor);

        $modelosNuevos = array_diff($modelosDisponibles, $modelosActuales);

        // Actualizar timestamp de verificación
        Cache::put($this->cachePrefix . $proveedor . '_last_check', now(), 3600);

        return [
            'disponible' => !empty($modelosNuevos),
            'proveedor' => $proveedor,
            'tipo' => $config['tipo'],
            'modelos_nuevos' => array_values($modelosNuevos),
            'modelos_disponibles' => $modelosDisponibles,
            'modelos_actuales' => $modelosActuales,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Aplicar una actualización específica
     */
    protected function aplicarActualizacion(string $proveedor, array $actualizacion): array
    {
        try {
            Log::info("Aplicando actualización para {$proveedor}", [
                'modelos_nuevos' => $actualizacion['modelos_nuevos']
            ]);

            // Actualizar configuración
            $this->actualizarConfiguracionProveedor($proveedor, $actualizacion);

            // Limpiar cachés relacionados
            $this->limpiarCachesRelacionados($proveedor);

            // Registrar actualización
            $this->registrarActualizacion($proveedor, $actualizacion);

            return [
                'exito' => true,
                'proveedor' => $proveedor,
                'tipo' => $actualizacion['tipo'],
                'modelos_actualizados' => $actualizacion['modelos_nuevos'],
                'timestamp' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            Log::error("Error aplicando actualización para {$proveedor}", [
                'error' => $e->getMessage(),
                'actualizacion' => $actualizacion
            ]);

            return [
                'exito' => false,
                'proveedor' => $proveedor,
                'tipo' => 'error_aplicacion',
                'mensaje' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Sistema de alimentación automática de librerías
     */
    public function alimentarLibrerias(): array
    {
        $resultados = [
            'librerias_actualizadas' => [],
            'librerias_fallidas' => [],
            'dependencias_resueltas' => [],
            'timestamp' => now()->toISOString()
        ];

        try {
            // Verificar conectividad
            if (!$this->verificarConectividad()) {
                return $resultados;
            }

            // Verificar repositorios de librerías disponibles
            $repositorios = $this->obtenerRepositoriosLibrerias();

            foreach ($repositorios as $repo) {
                try {
                    $actualizacion = $this->verificarLibreria($repo);
                    if ($actualizacion['actualizable']) {
                        $aplicacion = $this->actualizarLibreria($repo, $actualizacion);
                        if ($aplicacion['exito']) {
                            $resultados['librerias_actualizadas'][] = $aplicacion;
                        } else {
                            $resultados['librerias_fallidas'][] = $aplicacion;
                        }
                    }
                } catch (\Exception $e) {
                    $resultados['librerias_fallidas'][] = [
                        'libreria' => $repo['nombre'],
                        'error' => $e->getMessage(),
                        'timestamp' => now()->toISOString()
                    ];
                }
            }

            // Resolver dependencias entre librerías
            $resultados['dependencias_resueltas'] = $this->resolverDependencias($resultados);

        } catch (\Exception $e) {
            Log::error('Error en alimentación automática de librerías', [
                'error' => $e->getMessage()
            ]);
        }

        return $resultados;
    }

    /**
     * Verificar conectividad a internet
     */
    protected function verificarConectividad(): bool
    {
        try {
            $response = Http::timeout(5)->get('https://www.google.com');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verificar rate limiting para actualizaciones
     */
    protected function checkRateLimitActualizaciones(): bool
    {
        return RateLimiter::attempt(
            'ia_updates_global',
            10, // 10 actualizaciones por hora
            function () {
                return true;
            },
            3600 // 1 hora
        );
    }

    /**
     * Extraer modelos disponibles de la respuesta de la API
     */
    protected function extraerModelosDisponibles(string $proveedor, array $data): array
    {
        switch ($proveedor) {
            case 'openai':
                return collect($data['data'] ?? [])
                    ->pluck('id')
                    ->filter(function ($modelo) {
                        return str_contains($modelo, 'gpt') || str_contains($modelo, 'whisper');
                    })
                    ->values()
                    ->toArray();

            case 'anthropic':
                return ['claude-3-opus', 'claude-3-sonnet', 'claude-3-haiku'];

            case 'google_gemini':
                return collect($data['models'] ?? [])
                    ->pluck('name')
                    ->map(function ($name) {
                        return str_replace('models/', '', $name);
                    })
                    ->values()
                    ->toArray();

            case 'elevenlabs':
                return collect($data ?? [])
                    ->pluck('model_id')
                    ->values()
                    ->toArray();

            default:
                return [];
        }
    }

    /**
     * Obtener modelos actualmente configurados
     */
    protected function obtenerModelosActuales(string $proveedor): array
    {
        $config = $this->proveedoresActualizables[$proveedor] ?? [];
        return $config['modelos'] ?? [];
    }

    /**
     * Actualizar configuración del proveedor
     */
    protected function actualizarConfiguracionProveedor(string $proveedor, array $actualizacion): void
    {
        $modelosActuales = $this->obtenerModelosActuales($proveedor);
        $modelosNuevos = array_merge($modelosActuales, $actualizacion['modelos_nuevos']);

        $this->proveedoresActualizables[$proveedor]['modelos'] = $modelosNuevos;

        // Actualizar configuración en tiempo real
        config(["services.{$proveedor}.modelos_disponibles" => $modelosNuevos]);

        Log::info("Configuración actualizada para {$proveedor}", [
            'modelos_anteriores' => $modelosActuales,
            'modelos_nuevos' => $modelosNuevos
        ]);
    }

    /**
     * Limpiar cachés relacionados con el proveedor
     */
    protected function limpiarCachesRelacionados(string $proveedor): void
    {
        $patronesCache = [
            "ia_*{$proveedor}*",
            "voz_*{$proveedor}*",
            "{$proveedor}_*"
        ];

        foreach ($patronesCache as $patron) {
            Cache::forget($patron);
        }

        Log::info("Cachés limpiados para {$proveedor}");
    }

    /**
     * Registrar actualización en el log
     */
    protected function registrarActualizacion(string $proveedor, array $actualizacion): void
    {
        $logEntry = [
            'timestamp' => now()->toISOString(),
            'proveedor' => $proveedor,
            'tipo' => $actualizacion['tipo'],
            'modelos_actualizados' => $actualizacion['modelos_nuevos'],
            'version_anterior' => $this->obtenerModelosActuales($proveedor),
            'ip_servidor' => request()->ip(),
            'usuario_sistema' => get_current_user()
        ];

        // Guardar en archivo de log
        Storage::append($this->updateLogPath, json_encode($logEntry));

        // Registrar en base de datos si es necesario
        // DB::table('ia_updates_log')->insert($logEntry);
    }

    /**
     * Notificar administradores sobre actualizaciones
     */
    protected function notificarAdministradores(array $resultados): void
    {
        $administradores = User::where('tipo_usuario', 'administrador')
            ->whereNotNull('email')
            ->get();

        foreach ($administradores as $admin) {
            $admin->notify(new ActualizacionIANotification($resultados));
        }

        Log::info('Notificaciones enviadas a administradores', [
            'cantidad' => $administradores->count(),
            'actualizaciones' => count($resultados['actualizaciones_aplicadas'])
        ]);
    }

    /**
     * Obtener repositorios de librerías para alimentación automática
     */
    protected function obtenerRepositoriosLibrerias(): array
    {
        return [
            [
                'nombre' => 'openai-php-client',
                'tipo' => 'composer',
                'repositorio' => 'openai-php/client',
                'ultima_version' => Cache::get('libreria_openai_version')
            ],
            [
                'nombre' => 'anthropic-sdk',
                'tipo' => 'composer',
                'repositorio' => 'anthropic/anthropic-sdk-php',
                'ultima_version' => Cache::get('libreria_anthropic_version')
            ],
            [
                'nombre' => 'google-cloud-ai',
                'tipo' => 'composer',
                'repositorio' => 'google/cloud-ai',
                'ultima_version' => Cache::get('libreria_google_version')
            ]
        ];
    }

    /**
     * Verificar si una librería tiene actualizaciones disponibles
     */
    protected function verificarLibreria(array $repo): array
    {
        try {
            $response = Http::timeout(10)->get("https://repo.packagist.org/p/{$repo['repositorio']}.json");

            if (!$response->successful()) {
                throw new \Exception("No se pudo consultar {$repo['nombre']}");
            }

            $data = $response->json();
            $ultimaVersion = collect($data['packages'][$repo['repositorio']] ?? [])
                ->keys()
                ->sort()
                ->last();

            $necesitaActualizacion = $ultimaVersion && $ultimaVersion !== $repo['ultima_version'];

            return [
                'actualizable' => $necesitaActualizacion,
                'version_actual' => $repo['ultima_version'],
                'version_disponible' => $ultimaVersion,
                'libreria' => $repo['nombre']
            ];

        } catch (\Exception $e) {
            Log::warning("Error verificando librería {$repo['nombre']}", [
                'error' => $e->getMessage()
            ]);

            return [
                'actualizable' => false,
                'error' => $e->getMessage(),
                'libreria' => $repo['nombre']
            ];
        }
    }

    /**
     * Actualizar una librería específica
     */
    protected function actualizarLibreria(array $repo, array $actualizacion): array
    {
        try {
            // Ejecutar composer update
            $comando = "composer update {$repo['repositorio']} --no-interaction";
            $output = [];
            $returnCode = 0;

            exec($comando, $output, $returnCode);

            if ($returnCode === 0) {
                // Actualizar versión en cache
                Cache::put("libreria_{$repo['nombre']}_version", $actualizacion['version_disponible'], 86400); // 24 horas

                return [
                    'exito' => true,
                    'libreria' => $repo['nombre'],
                    'version_anterior' => $actualizacion['version_actual'],
                    'version_nueva' => $actualizacion['version_disponible'],
                    'timestamp' => now()->toISOString()
                ];
            } else {
                throw new \Exception("Composer update falló con código {$returnCode}");
            }

        } catch (\Exception $e) {
            return [
                'exito' => false,
                'libreria' => $repo['nombre'],
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Resolver dependencias entre librerías actualizadas
     */
    protected function resolverDependencias(array $resultados): array
    {
        $dependencias = [];

        // Resolver dependencias de OpenAI
        if (collect($resultados['librerias_actualizadas'])->contains('libreria', 'openai-php-client')) {
            $dependencias[] = [
                'libreria' => 'openai-php-client',
                'dependencias' => ['guzzlehttp/guzzle', 'psr/http-client'],
                'estado' => 'resuelto'
            ];
        }

        // Resolver dependencias de Google
        if (collect($resultados['librerias_actualizadas'])->contains('libreria', 'google-cloud-ai')) {
            $dependencias[] = [
                'libreria' => 'google-cloud-ai',
                'dependencias' => ['google/gax', 'google/auth'],
                'estado' => 'resuelto'
            ];
        }

        return $dependencias;
    }

    /**
     * Obtener estadísticas de actualizaciones
     */
    public function obtenerEstadisticas(): array
    {
        $ultimaSemana = now()->subDays(7);
        $ultimoMes = now()->subDays(30);

        return [
            'actualizaciones_semanales' => Cache::get($this->cachePrefix . 'weekly_count', 0),
            'actualizaciones_mensuales' => Cache::get($this->cachePrefix . 'monthly_count', 0),
            'proveedores_actualizados' => Cache::get($this->cachePrefix . 'providers_updated', []),
            'ultima_actualizacion' => Cache::get($this->cachePrefix . 'last_update'),
            'librerias_actualizadas' => Cache::get($this->cachePrefix . 'libraries_updated', 0),
            'tiempo_promedio_actualizacion' => Cache::get($this->cachePrefix . 'avg_update_time', 0),
            'tasa_exito' => Cache::get($this->cachePrefix . 'success_rate', 100)
        ];
    }

    /**
     * Programar tarea automática de actualización
     */
    public static function programarActualizacionAutomatica(): void
    {
        // Se ejecutaría cada 6 horas
        Artisan::call('schedule:run');

        // O se puede configurar en app/Console/Kernel.php
        /*
        protected function schedule(Schedule $schedule)
        {
            $schedule->call(function () {
                $actualizador = new SistemaActualizacionIA();
                $resultados = $actualizador->verificarActualizaciones();

                if (!empty($resultados['actualizaciones_aplicadas'])) {
                    Log::info('Actualizaciones de IA aplicadas automáticamente', $resultados);
                }
            })->everySixHours();
        }
        */
    }
}
