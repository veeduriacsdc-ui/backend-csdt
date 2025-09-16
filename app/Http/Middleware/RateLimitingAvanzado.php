<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RateLimitingAvanzado
{
    protected $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $tipo = 'api')
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = $this->getMaxAttempts($tipo);
        $decayMinutes = $this->getDecayMinutes($tipo);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $this->logRateLimitExceeded($request, $key, $maxAttempts);

            return $this->buildRateLimitResponse($request, $key, $maxAttempts, $decayMinutes);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        // Añadir headers de rate limiting a la respuesta
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $this->limiter->remaining($key, $maxAttempts));
        $response->headers->set('X-RateLimit-Reset', $this->limiter->availableIn($key));

        return $response;
    }

    /**
     * Resolve request signature for rate limiting
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $signature = $request->ip();

        // Si el usuario está autenticado, usar su ID en lugar de IP
        if ($request->user()) {
            $signature = $request->user()->id;
        }

        // Añadir ruta y método para mayor granularidad
        $signature .= '|' . $request->route()?->getName() ?: $request->path();
        $signature .= '|' . $request->method();

        return sha1($signature);
    }

    /**
     * Get maximum attempts based on request type
     */
    protected function getMaxAttempts(string $tipo): int
    {
        return match ($tipo) {
            'login' => 5,           // 5 intentos de login por hora
            'registro' => 3,        // 3 registros por hora
            'api' => 100,           // 100 requests por minuto
            'uploads' => 10,        // 10 uploads por minuto
            'consultas' => 50,      // 50 consultas por minuto
            default => 60,          // 60 requests por minuto por defecto
        };
    }

    /**
     * Get decay minutes based on request type
     */
    protected function getDecayMinutes(string $tipo): int
    {
        return match ($tipo) {
            'login' => 60,          // 1 hora
            'registro' => 60,       // 1 hora
            'api' => 1,             // 1 minuto
            'uploads' => 1,         // 1 minuto
            'consultas' => 1,       // 1 minuto
            default => 1,           // 1 minuto por defecto
        };
    }

    /**
     * Log rate limit exceeded
     */
    protected function logRateLimitExceeded(Request $request, string $key, int $maxAttempts): void
    {
        Log::warning('Rate limit exceeded', [
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'max_attempts' => $maxAttempts,
            'key' => $key,
        ]);
    }

    /**
     * Build rate limit response
     */
    protected function buildRateLimitResponse(Request $request, string $key, int $maxAttempts, int $decayMinutes): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        // Si es una petición AJAX/JSON, devolver respuesta JSON
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Demasiadas peticiones',
                'message' => 'Has excedido el límite de peticiones. Por favor, espera antes de intentar nuevamente.',
                'retry_after' => $retryAfter,
                'max_attempts' => $maxAttempts,
            ], 429, [
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Reset' => $retryAfter,
            ]);
        }

        // Para peticiones web normales, devolver vista de error
        return response()->view('errors.429', [
            'retry_after' => $retryAfter,
            'max_attempts' => $maxAttempts,
        ], 429)->header('Retry-After', $retryAfter);
    }
}
