<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class MonitorRendimiento
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $inicio = microtime(true);
        $memoriaInicial = memory_get_usage();

        $response = $next($request);

        $tiempoEjecucion = (microtime(true) - $inicio) * 1000; // ms
        $memoriaUsada = memory_get_usage() - $memoriaInicial;
        $codigoRespuesta = $response->getStatusCode();

        // Registrar métricas de rendimiento
        $this->registrarMetrica($request, $tiempoEjecucion, $memoriaUsada, $codigoRespuesta);

        // Alertar sobre problemas de rendimiento
        $this->verificarUmbrales($request, $tiempoEjecucion, $memoriaUsada, $codigoRespuesta);

        return $response;
    }

    private function registrarMetrica(Request $request, float $tiempo, int $memoria, int $codigo)
    {
        $metrica = [
            'ruta' => $request->getPathInfo(),
            'metodo' => $request->getMethod(),
            'tiempo_ejecucion' => round($tiempo, 2),
            'memoria_usada' => $memoria,
            'codigo_respuesta' => $codigo,
            'usuario_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString()
        ];

        // Almacenar en caché para análisis posterior
        $key = 'metricas_rendimiento_' . date('Y-m-d-H');
        $metricas = Cache::get($key, []);
        $metricas[] = $metrica;

        // Mantener solo las últimas 100 métricas por hora
        if (count($metricas) > 100) {
            array_shift($metricas);
        }

        Cache::put($key, $metricas, now()->addHours(2));

        // Log de métricas lentas
        if ($tiempo > 1000) { // Más de 1 segundo
            Log::warning('Rendimiento lento detectado', $metrica);
        }
    }

    private function verificarUmbrales(Request $request, float $tiempo, int $memoria, int $codigo)
    {
        $alertas = [];

        // Verificar tiempo de respuesta
        if ($tiempo > 5000) { // Más de 5 segundos
            $alertas[] = "Tiempo de respuesta excesivo: {$tiempo}ms en {$request->getPathInfo()}";
        }

        // Verificar uso de memoria
        $memoriaMB = $memoria / 1024 / 1024;
        if ($memoriaMB > 50) { // Más de 50MB
            $alertas[] = "Uso excesivo de memoria: {$memoriaMB}MB en {$request->getPathInfo()}";
        }

        // Verificar códigos de error
        if ($codigo >= 500) {
            $alertas[] = "Error del servidor ({$codigo}) en {$request->getPathInfo()}";
        }

        // Registrar alertas
        foreach ($alertas as $alerta) {
            Log::error('Alerta de rendimiento', [
                'alerta' => $alerta,
                'ruta' => $request->getPathInfo(),
                'metodo' => $request->getMethod(),
                'usuario_id' => $request->user()?->id,
                'ip' => $request->ip()
            ]);

            // Almacenar alerta para envío posterior
            $this->almacenarAlerta($alerta, $request);
        }
    }

    private function almacenarAlerta(string $mensaje, Request $request)
    {
        $key = 'alertas_rendimiento_' . date('Y-m-d');
        $alertas = Cache::get($key, []);

        $alertas[] = [
            'mensaje' => $mensaje,
            'ruta' => $request->getPathInfo(),
            'metodo' => $request->getMethod(),
            'usuario_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString()
        ];

        // Mantener solo las últimas 50 alertas por día
        if (count($alertas) > 50) {
            array_shift($alertas);
        }

        Cache::put($key, $alertas, now()->addDay());
    }
}
