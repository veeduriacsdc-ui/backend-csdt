<?php

namespace App\Http\Middleware;

use App\Models\LogUsuario;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificarNivelAcceso
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, int $nivelMinimo)
    {
        // Verificar si el usuario está autenticado
        if (! auth()->check()) {
            return $this->respuestaNoAutorizado('Usuario no autenticado');
        }

        $usuario = auth()->user();

        // Obtener el nivel de acceso del usuario
        $nivelUsuario = $this->obtenerNivelAcceso($usuario);

        if ($nivelUsuario < $nivelMinimo) {
            // Log del intento de acceso denegado
            LogUsuario::registrar(
                $usuario,
                'AccesoDenegado',
                'Acceso Denegado por Nivel',
                "Intento de acceso denegado - Nivel requerido: {$nivelMinimo}, Nivel usuario: {$nivelUsuario}",
                null,
                [
                    'nivel_requerido' => $nivelMinimo,
                    'nivel_usuario' => $nivelUsuario,
                    'url' => $request->fullUrl(),
                    'metodo' => $request->method(),
                ],
                'Critico'
            );

            return $this->respuestaNoAutorizado("Nivel de acceso insuficiente. Requerido: {$nivelMinimo}, Actual: {$nivelUsuario}");
        }

        // Log del acceso exitoso
        LogUsuario::registrar(
            $usuario,
            'AccesoAutorizado',
            'Acceso Autorizado por Nivel',
            "Acceso autorizado con nivel: {$nivelUsuario}",
            null,
            [
                'nivel_requerido' => $nivelMinimo,
                'nivel_usuario' => $nivelUsuario,
                'url' => $request->fullUrl(),
                'metodo' => $request->method(),
            ],
            'Normal'
        );

        return $next($request);
    }

    /**
     * Obtener el nivel de acceso del usuario
     */
    private function obtenerNivelAcceso($usuario): int
    {
        // Verificar si el usuario tiene roles asignados
        if (method_exists($usuario, 'obtenerRolesActivos')) {
            $rolesActivos = $usuario->obtenerRolesActivos();
            if ($rolesActivos->isNotEmpty()) {
                return $rolesActivos->max('rol.NivelAcceso');
            }
        }

        // Verificar rol del sistema (compatibilidad)
        if (method_exists($usuario, 'Rol')) {
            switch ($usuario->Rol) {
                case 'Administrador':
                    return 3;
                case 'Supervisor':
                    return 2;
                case 'Operador':
                    return 2;
                default:
                    return 1;
            }
        }

        // Por defecto, nivel de cliente
        return 1;
    }

    /**
     * Generar respuesta de no autorizado
     */
    private function respuestaNoAutorizado(string $mensaje): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $mensaje,
            'error' => 'ACCESS_LEVEL_DENIED',
        ], 403);
    }
}
