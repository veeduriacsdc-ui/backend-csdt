<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class EncriptarDatosSensibles
{
    /**
     * Campos que deben ser encriptados
     */
    protected $camposSensibles = [
        'contrasena',
        'password',
        'token',
        'api_key',
        'secret',
        'clave_privada',
        'numero_tarjeta',
        'cvv',
        'codigo_seguridad',
        'documento_identidad',
        'telefono',
        'direccion',
        'correo', // Opcional: en algunos casos se puede encriptar
    ];

    /**
     * Campos que nunca deben ser encriptados
     */
    protected $camposExcluidos = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'email_verified_at',
        'remember_token',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Solo procesar si es una petición que modifica datos
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $this->procesarDatosEntrada($request);
        }

        $response = $next($request);

        // Procesar datos de salida si es necesario
        if ($request->expectsJson()) {
            $this->procesarDatosSalida($response);
        }

        return $response;
    }

    /**
     * Procesar datos de entrada para encriptación
     */
    protected function procesarDatosEntrada(Request $request): void
    {
        $data = $request->all();

        // Buscar y encriptar campos sensibles
        foreach ($data as $key => $value) {
            if ($this->debeEncriptarse($key, $value)) {
                try {
                    $data[$key] = Crypt::encryptString($value);
                    Log::info("Campo sensible encriptado: {$key}", [
                        'ip' => $request->ip(),
                        'url' => $request->path(),
                    ]);
                } catch (\Exception $e) {
                    Log::error("Error al encriptar campo {$key}: " . $e->getMessage());
                }
            }
        }

        // Reemplazar los datos en la request
        $request->merge($data);
    }

    /**
     * Procesar datos de salida para desencriptación
     */
    protected function procesarDatosSalida($response): void
    {
        if (method_exists($response, 'getData')) {
            $data = $response->getData(true);

            if (is_array($data)) {
                $data = $this->desencriptarArray($data);
                $response->setData($data);
            }
        }
    }

    /**
     * Desencriptar array recursivamente
     */
    protected function desencriptarArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->desencriptarArray($value);
            } elseif (is_string($value) && $this->esEncriptado($value)) {
                try {
                    $data[$key] = Crypt::decryptString($value);
                } catch (\Exception $e) {
                    // Si no se puede desencriptar, mantener el valor original
                    Log::warning("No se pudo desencriptar campo {$key}: " . $e->getMessage());
                }
            }
        }

        return $data;
    }

    /**
     * Determinar si un campo debe ser encriptado
     */
    protected function debeEncriptarse(string $key, $value): bool
    {
        // No encriptar campos excluidos
        if (in_array($key, $this->camposExcluidos)) {
            return false;
        }

        // Encriptar campos sensibles conocidos
        if (in_array($key, $this->camposSensibles)) {
            return true;
        }

        // Encriptar campos que contienen palabras clave sensibles
        $palabrasClave = ['password', 'secret', 'key', 'token', 'card', 'cvv'];
        foreach ($palabrasClave as $palabra) {
            if (stripos($key, $palabra) !== false) {
                return true;
            }
        }

        // Encriptar valores que parecen contraseñas (longitud > 8 y contienen caracteres especiales)
        if (is_string($value) && strlen($value) > 8) {
            if (preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar si un valor parece estar encriptado
     */
    protected function esEncriptado(string $value): bool
    {
        // Los valores encriptados por Laravel Crypt suelen empezar con "eyJ"
        // que es el inicio de un JSON Web Token en base64
        return strlen($value) > 100 && preg_match('/^[A-Za-z0-9+\/=]+$/', $value);
    }

    /**
     * Método helper para encriptar datos manualmente
     */
    public static function encriptarDato(string $dato): string
    {
        try {
            return Crypt::encryptString($dato);
        } catch (\Exception $e) {
            Log::error("Error al encriptar dato: " . $e->getMessage());
            return $dato; // Retornar el dato original si falla la encriptación
        }
    }

    /**
     * Método helper para desencriptar datos manualmente
     */
    public static function desencriptarDato(string $datoEncriptado): string
    {
        try {
            return Crypt::decryptString($datoEncriptado);
        } catch (\Exception $e) {
            Log::error("Error al desencriptar dato: " . $e->getMessage());
            return $datoEncriptado; // Retornar el dato encriptado si falla la desencriptación
        }
    }
}
