<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Implementación de Circuit Breaker para servicios de IA
 * Protege contra fallos en cascada y mejora la resiliencia
 */
class CircuitBreaker
{
    protected string $serviceName;
    protected int $failureThreshold;
    protected int $recoveryTimeout;
    protected int $successThreshold;

    const STATE_CLOSED = 'closed';     // Normal operation
    const STATE_OPEN = 'open';         // Circuit is open, failing fast
    const STATE_HALF_OPEN = 'half_open'; // Testing if service recovered

    public function __construct(
        string $serviceName,
        int $failureThreshold = 5,
        int $recoveryTimeout = 60,
        int $successThreshold = 3
    ) {
        $this->serviceName = $serviceName;
        $this->failureThreshold = $failureThreshold;
        $this->recoveryTimeout = $recoveryTimeout;
        $this->successThreshold = $successThreshold;
    }

    /**
     * Ejecuta una operación protegida por circuit breaker
     */
    public function execute(callable $operation)
    {
        $state = $this->getState();

        if ($state === self::STATE_OPEN) {
            if ($this->shouldAttemptReset()) {
                $this->setState(self::STATE_HALF_OPEN);
                $state = self::STATE_HALF_OPEN;
            } else {
                throw new \RuntimeException("Circuit breaker is OPEN for service: {$this->serviceName}");
            }
        }

        try {
            $result = $operation();

            if ($state === self::STATE_HALF_OPEN) {
                $this->recordSuccess();
            } else {
                $this->resetFailures();
            }

            return $result;

        } catch (\Exception $e) {
            $this->recordFailure();

            if ($state === self::STATE_HALF_OPEN) {
                $this->setState(self::STATE_OPEN);
            }

            Log::warning("Circuit breaker failure for {$this->serviceName}", [
                'error' => $e->getMessage(),
                'state' => $state,
                'failure_count' => $this->getFailureCount()
            ]);

            throw $e;
        }
    }

    /**
     * Obtiene el estado actual del circuit breaker
     */
    protected function getState(): string
    {
        return Cache::get("circuit_breaker_{$this->serviceName}_state", self::STATE_CLOSED);
    }

    /**
     * Establece el estado del circuit breaker
     */
    protected function setState(string $state): void
    {
        Cache::put("circuit_breaker_{$this->serviceName}_state", $state, 3600);
    }

    /**
     * Registra una falla
     */
    protected function recordFailure(): void
    {
        $failures = $this->getFailureCount() + 1;
        Cache::put("circuit_breaker_{$this->serviceName}_failures", $failures, 3600);

        if ($failures >= $this->failureThreshold) {
            $this->setState(self::STATE_OPEN);
            Cache::put("circuit_breaker_{$this->serviceName}_last_failure", now()->timestamp, 3600);
        }
    }

    /**
     * Registra un éxito
     */
    protected function recordSuccess(): void
    {
        $successes = Cache::get("circuit_breaker_{$this->serviceName}_successes", 0) + 1;
        Cache::put("circuit_breaker_{$this->serviceName}_successes", $successes, 3600);

        if ($successes >= $this->successThreshold) {
            $this->resetCircuit();
        }
    }

    /**
     * Resetea el contador de fallas
     */
    protected function resetFailures(): void
    {
        Cache::forget("circuit_breaker_{$this->serviceName}_failures");
        Cache::forget("circuit_breaker_{$this->serviceName}_last_failure");
    }

    /**
     * Resetea completamente el circuit breaker
     */
    protected function resetCircuit(): void
    {
        $this->setState(self::STATE_CLOSED);
        $this->resetFailures();
        Cache::forget("circuit_breaker_{$this->serviceName}_successes");
    }

    /**
     * Obtiene el número de fallas actuales
     */
    protected function getFailureCount(): int
    {
        return Cache::get("circuit_breaker_{$this->serviceName}_failures", 0);
    }

    /**
     * Verifica si debe intentar resetear el circuito
     */
    protected function shouldAttemptReset(): bool
    {
        $lastFailure = Cache::get("circuit_breaker_{$this->serviceName}_last_failure");

        if (!$lastFailure) {
            return false;
        }

        return (now()->timestamp - $lastFailure) >= $this->recoveryTimeout;
    }

    /**
     * Obtiene métricas del circuit breaker
     */
    public function getMetrics(): array
    {
        return [
            'service' => $this->serviceName,
            'state' => $this->getState(),
            'failure_count' => $this->getFailureCount(),
            'last_failure' => Cache::get("circuit_breaker_{$this->serviceName}_last_failure"),
            'success_count' => Cache::get("circuit_breaker_{$this->serviceName}_successes", 0),
            'threshold' => $this->failureThreshold,
            'recovery_timeout' => $this->recoveryTimeout,
        ];
    }

    /**
     * Fuerza el reset manual del circuit breaker
     */
    public function forceReset(): void
    {
        $this->resetCircuit();
        Log::info("Circuit breaker manually reset for service: {$this->serviceName}");
    }
}
