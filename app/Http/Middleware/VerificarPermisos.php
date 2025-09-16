<?php

namespace App\Http\Middleware;

use App\Models\LogUsuario;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificarPermisos
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $recurso, string $funcion)
    {
        // Verificar si el usuario está autenticado
        if (! auth()->check()) {
            return $this->respuestaNoAutorizado('Usuario no autenticado');
        }

        $usuario = auth()->user();

        // Verificar si el usuario tiene el permiso
        if (! $usuario->tienePermiso($recurso, $funcion)) {
            // Log del intento de acceso denegado
            LogUsuario::registrar(
                $usuario,
                'AccesoDenegado',
                'Acceso Denegado',
                "Intento de acceso denegado a {$recurso}/{$funcion}",
                null,
                [
                    'recurso' => $recurso,
                    'funcion' => $funcion,
                    'url' => $request->fullUrl(),
                    'metodo' => $request->method(),
                ],
                'Critico'
            );

            return $this->respuestaNoAutorizado("No tienes permisos para {$funcion} {$recurso}");
        }

        // Log del acceso exitoso
        LogUsuario::registrar(
            $usuario,
            'AccesoAutorizado',
            'Acceso Autorizado',
            "Acceso autorizado a {$recurso}/{$funcion}",
            null,
            [
                'recurso' => $recurso,
                'funcion' => $funcion,
                'url' => $request->fullUrl(),
                'metodo' => $request->method(),
            ],
            'Normal'
        );

        return $next($request);
    }

    /**
     * Generar respuesta de no autorizado
     */
    private function respuestaNoAutorizado(string $mensaje): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $mensaje,
            'error' => 'PERMISSION_DENIED',
        ], 403);
    }
}
