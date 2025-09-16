<?php

namespace App\Http\Middleware;

use App\Models\Cliente;
use App\Models\Operador;
use App\Models\Sesion;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerificarRol
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ?string $rol = null)
    {
        try {
            // Verificar token de autenticación
            if (! $request->bearerToken()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de autenticación requerido',
                    'error' => 'UNAUTHORIZED',
                ], 401);
            }

            // Obtener usuario autenticado
            $usuario = $request->user();

            if (! $usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado',
                    'error' => 'UNAUTHENTICATED',
                ], 401);
            }

            // Obtener sesión activa
            $sesion = Sesion::where('usuario_id', $usuario->id)
                ->where('estado_sesion', 'activa')
                ->first();

            if (! $sesion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesión no válida o expirada',
                    'error' => 'INVALID_SESSION',
                ], 401);
            }

            // Verificar que la sesión no haya expirado
            if ($sesion->fecha_expiracion && now()->isAfter($sesion->fecha_expiracion)) {
                $sesion->cerrarSesion();

                return response()->json([
                    'success' => false,
                    'message' => 'Sesión expirada',
                    'error' => 'SESSION_EXPIRED',
                ], 401);
            }

            // Si no se especifica rol, solo verificar autenticación
            if (! $rol) {
                return $next($request);
            }

            // Verificar rol específico
            $rolUsuario = $sesion->rol;

            if (! $this->tieneRol($rolUsuario, $rol)) {
                Log::warning('Acceso denegado por rol', [
                    'usuario_id' => $usuario->id,
                    'rol_requerido' => $rol,
                    'rol_usuario' => $rolUsuario,
                    'ruta' => $request->path(),
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado. Rol insuficiente.',
                    'error' => 'INSUFFICIENT_ROLE',
                    'rol_requerido' => $rol,
                    'rol_actual' => $rolUsuario,
                ], 403);
            }

            // Verificar permisos específicos si se requieren
            if ($request->has('permiso_requerido')) {
                $permisoRequerido = $request->input('permiso_requerido');

                if (! $sesion->tienePermiso($permisoRequerido)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Permiso insuficiente para esta acción',
                        'error' => 'INSUFFICIENT_PERMISSION',
                        'permiso_requerido' => $permisoRequerido,
                    ], 403);
                }
            }

            // Actualizar actividad de la sesión
            $sesion->actualizarActividad();

            // Agregar información del usuario y sesión a la request
            $request->merge([
                'usuario_autenticado' => [
                    'id' => $usuario->id,
                    'rol' => $rolUsuario,
                    'tipo_usuario' => $sesion->tipo_usuario,
                    'permisos' => $sesion->permisos,
                    'nivel_acceso' => $sesion->nivel_acceso ?? 1,
                ],
            ]);

            return $next($request);

        } catch (\Exception $e) {
            Log::error('Error en middleware VerificarRol: '.$e->getMessage(), [
                'request_path' => $request->path(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno de autenticación',
                'error' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    /**
     * Verificar si el usuario tiene el rol requerido
     */
    private function tieneRol(string $rolUsuario, string $rolRequerido): bool
    {
        // Jerarquía de roles (de mayor a menor privilegio)
        $jerarquiaRoles = [
            'administrador' => 3,
            'operador' => 2,
            'cliente' => 1,
        ];

        $nivelUsuario = $jerarquiaRoles[$rolUsuario] ?? 0;
        $nivelRequerido = $jerarquiaRoles[$rolRequerido] ?? 0;

        // El usuario debe tener al menos el nivel requerido
        return $nivelUsuario >= $nivelRequerido;
    }

    /**
     * Verificar si el usuario tiene un rol específico
     */
    public static function tieneRolEspecifico(string $rolUsuario, string $rolRequerido): bool
    {
        $instancia = new self;

        return $instancia->tieneRol($rolUsuario, $rolRequerido);
    }

    /**
     * Verificar si el usuario tiene múltiples roles
     */
    public static function tieneAlgunRol(string $rolUsuario, array $rolesRequeridos): bool
    {
        foreach ($rolesRequeridos as $rol) {
            if (self::tieneRolEspecifico($rolUsuario, $rol)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar si el usuario es administrador
     */
    public static function esAdministrador(string $rolUsuario): bool
    {
        return $rolUsuario === 'administrador';
    }

    /**
     * Verificar si el usuario es operador o administrador
     */
    public static function esOperadorOAdmin(string $rolUsuario): bool
    {
        return in_array($rolUsuario, ['operador', 'administrador']);
    }

    /**
     * Verificar si el usuario es cliente
     */
    public static function esCliente(string $rolUsuario): bool
    {
        return $rolUsuario === 'cliente';
    }
}
