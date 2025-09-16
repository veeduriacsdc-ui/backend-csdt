<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SanitizarInputs
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Sanitizar todos los inputs
        $inputs = $request->all();

        // Log de actividad sospechosa
        $this->detectarActividadSospechosa($inputs);

        // Sanitizar inputs
        $inputsSanitizados = $this->sanitizarArray($inputs);

        // Reemplazar los inputs en la request
        $request->merge($inputsSanitizados);

        return $next($request);
    }

    /**
     * Sanitizar un array recursivamente
     */
    private function sanitizarArray(array $data): array
    {
        $sanitizado = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitizado[$key] = $this->sanitizarArray($value);
            } elseif (is_string($value)) {
                $sanitizado[$key] = $this->sanitizarString($value);
            } else {
                $sanitizado[$key] = $value;
            }
        }

        return $sanitizado;
    }

    /**
     * Sanitizar una cadena de texto
     */
    private function sanitizarString(string $value): string
    {
        // Remover caracteres de control excepto saltos de línea y tabulaciones
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

        // Convertir caracteres especiales HTML
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remover scripts potencialmente peligrosos
        $value = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '', $value);

        // Remover tags HTML peligrosos
        $dangerousTags = ['iframe', 'object', 'embed', 'form', 'input', 'script', 'style'];
        foreach ($dangerousTags as $tag) {
            $value = preg_replace('/<' . $tag . '\b[^>]*>.*?<\/' . $tag . '>/is', '', $value);
        }

        // Limitar longitud máxima
        if (strlen($value) > 10000) {
            $value = substr($value, 0, 10000);
        }

        return $value;
    }

    /**
     * Detectar actividad sospechosa
     */
    private function detectarActividadSospechosa(array $inputs): void
    {
        $patronesSospechosos = [
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
            '/eval\(/i',
            '/base64,/i',
            '/data:text/i',
            '/union.*select/i',
            '/drop.*table/i',
            '/--/',
            '/#/',
            '/\/\*.*\*\//',
        ];

        $actividadSospechosa = [];

        foreach ($inputs as $key => $value) {
            if (is_string($value)) {
                foreach ($patronesSospechosos as $patron) {
                    if (preg_match($patron, $value)) {
                        $actividadSospechosa[] = [
                            'campo' => $key,
                            'valor' => substr($value, 0, 100) . (strlen($value) > 100 ? '...' : ''),
                            'patron' => $patron,
                            'ip' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ];
                        break;
                    }
                }
            }
        }

        if (!empty($actividadSospechosa)) {
            Log::warning('Actividad sospechosa detectada', [
                'actividad' => $actividadSospechosa,
                'url' => request()->fullUrl(),
                'metodo' => request()->method(),
            ]);

            // Aquí podrías implementar bloqueo automático o alertas adicionales
        }
    }
}
