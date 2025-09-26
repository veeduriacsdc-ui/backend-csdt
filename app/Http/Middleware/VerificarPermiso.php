<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermiso
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado'
            ], 401);
        }

        // El administrador general tiene todos los permisos
        if ($usuario->rol === 'adm_gen') {
            return $next($request);
        }

        // Verificar si el usuario tiene el permiso específico
        if (!$usuario->tienePermiso($permiso)) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para realizar esta acción'
            ], 403);
        }

        return $next($request);
    }
}
