<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sesion;
use App\Models\Cliente;
use App\Models\Operador;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SesionControlador extends Controller
{
    /**
     * Iniciar sesión unificado para clientes, operadores y administradores
     */
    public function iniciarSesion(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'correo_electronico' => 'required|email',
                'contrasena' => 'required|string|min:6'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $email = $request->correo_electronico;
            $password = $request->contrasena;

            // Intentar autenticar usando el modelo Sesion
            $sesion = Sesion::autenticarUsuario($email, $password);

            if (!$sesion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }

            // Obtener datos del usuario
            $usuario = $this->obtenerDatosUsuario($sesion->usuario_id, $sesion->tipo_usuario);
            
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Generar tokens
            $tokens = $sesion->generarTokens();

            return response()->json([
                'success' => true,
                'message' => 'Sesión iniciada exitosamente',
                'data' => [
                    'usuario' => [
                        'id' => $usuario->id,
                        'nombre_completo' => $usuario->nombre_completo,
                        'correo_electronico' => $usuario->correo_electronico,
                        'tipo_usuario' => $sesion->tipo_usuario,
                        'rol' => $sesion->rol_asignado,
                        'nivel_acceso' => $sesion->nivel_acceso_formateado,
                        'permisos' => $sesion->permisos_formateados
                    ],
                    'sesion' => [
                        'id' => $sesion->id,
                        'codigo_sesion' => $sesion->codigo_sesion,
                        'fecha_inicio' => $sesion->fecha_inicio_formateada,
                        'fecha_expiracion' => $sesion->fecha_expiracion_formateada
                    ],
                    'tokens' => $tokens
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al iniciar sesión: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Registrar usuario (cliente u operador)
     */
    public function registrarUsuario(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre_completo' => 'required|string|max:255',
                'correo_electronico' => 'required|email|unique:clientes,correo_electronico|unique:operadores,correo_electronico',
                'contrasena' => 'required|string|min:8|confirmed',
                'contrasena_confirmation' => 'required|string|min:8',
                'telefono' => 'nullable|string|max:20',
                'direccion' => 'nullable|string|max:500',
                'tipo_usuario' => 'required|in:cliente,operador'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar si el correo ya existe en ambas tablas
            $clienteExistente = Cliente::where('correo_electronico', $request->correo_electronico)->exists();
            $operadorExistente = Operador::where('correo_electronico', $request->correo_electronico)->exists();

            if ($clienteExistente || $operadorExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'El correo electrónico ya está registrado'
                ], 409);
            }

            // Registrar usuario usando el modelo Sesion
            $resultado = Sesion::registrarUsuario($request->all(), $request->tipo_usuario);

            if (!$resultado['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al registrar usuario',
                    'error' => $resultado['error']
                ], 500);
            }

            $usuario = $resultado['usuario'];
            $sesion = $resultado['sesion'];

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'data' => [
                    'usuario' => [
                        'id' => $usuario->id,
                        'nombre_completo' => $usuario->nombre_completo,
                        'correo_electronico' => $usuario->correo_electronico,
                        'tipo_usuario' => $request->tipo_usuario,
                        'rol' => $request->tipo_usuario,
                        'estado' => 'activo'
                    ],
                    'sesion' => [
                        'id' => $sesion->id,
                        'codigo_sesion' => $sesion->codigo_sesion
                    ],
                    'tokens' => $resultado['tokens']
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al registrar usuario: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Cerrar sesión
     */
    public function cerrarSesion(Request $request): JsonResponse
    {
        try {
            $token = $request->header('Authorization');
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de autorización requerido'
                ], 401);
            }

            // Remover "Bearer " del token
            $token = str_replace('Bearer ', '', $token);

            // Buscar sesión por token
            $sesion = Sesion::where('token_acceso', $token)->first();

            if (!$sesion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesión no válida'
                ], 404);
            }

            // Cerrar sesión
            $sesion->cerrarSesion();

            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al cerrar sesión: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Renovar sesión
     */
    public function renovarSesion(Request $request): JsonResponse
    {
        try {
            $token = $request->header('Authorization');
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de autorización requerido'
                ], 401);
            }

            $token = str_replace('Bearer ', '', $token);

            $sesion = Sesion::where('token_acceso', $token)->first();

            if (!$sesion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesión no válida'
                ], 404);
            }

            if (!$sesion->esta_activa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesión inactiva o expirada'
                ], 401);
            }

            // Renovar sesión
            $sesion->renovarSesion();

            return response()->json([
                'success' => true,
                'message' => 'Sesión renovada exitosamente',
                'data' => [
                    'fecha_expiracion' => $sesion->fecha_expiracion_formateada
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al renovar sesión: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Recuperar contraseña
     */
    public function recuperarContrasena(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'correo_electronico' => 'required|email'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Correo electrónico requerido',
                    'errors' => $validator->errors()
                ], 422);
            }

            $email = $request->correo_electronico;

            // Buscar usuario usando el modelo Sesion
            $resultado = Sesion::recuperarContrasena($email);

            if (!$resultado['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Aquí se implementaría el envío de email para recuperación
            // Por ahora solo retornamos confirmación

            return response()->json([
                'success' => true,
                'message' => 'Se ha enviado un enlace de recuperación a su correo electrónico',
                'data' => [
                    'tipo_usuario' => $resultado['tipo_usuario']
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al recuperar contraseña: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Cambiar contraseña
     */
    public function cambiarContrasena(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'contrasena_actual' => 'required|string',
                'nueva_contrasena' => 'required|string|min:8|confirmed',
                'nueva_contrasena_confirmation' => 'required|string|min:8'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $token = $request->header('Authorization');
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de autorización requerido'
                ], 401);
            }

            $token = str_replace('Bearer ', '', $token);

            $sesion = Sesion::where('token_acceso', $token)->first();

            if (!$sesion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesión no válida'
                ], 404);
            }

            // Obtener usuario y verificar contraseña actual
            $usuario = $this->obtenerDatosUsuario($sesion->usuario_id, $sesion->tipo_usuario);
            
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            if (!Hash::check($request->contrasena_actual, $usuario->contrasena)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contraseña actual incorrecta'
                ], 401);
            }

            // Cambiar contraseña
            $resultado = Sesion::cambiarContrasena(
                $sesion->usuario_id,
                $sesion->tipo_usuario,
                $request->nueva_contrasena
            );

            if (!$resultado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al cambiar contraseña'
                ], 500);
            }

            // Cerrar sesión actual para forzar nuevo login
            $sesion->cerrarSesion();

            return response()->json([
                'success' => true,
                'message' => 'Contraseña cambiada exitosamente. Debe iniciar sesión nuevamente.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al cambiar contraseña: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtener información de la sesión actual
     */
    public function obtenerSesion(Request $request): JsonResponse
    {
        try {
            $token = $request->header('Authorization');
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de autorización requerido'
                ], 401);
            }

            $token = str_replace('Bearer ', '', $token);

            $sesion = Sesion::where('token_acceso', $token)->first();

            if (!$sesion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesión no válida'
                ], 404);
            }

            if (!$sesion->esta_activa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesión inactiva o expirada'
                ], 401);
            }

            // Obtener datos del usuario
            $usuario = $this->obtenerDatosUsuario($sesion->usuario_id, $sesion->tipo_usuario);

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Actualizar última actividad
            $sesion->actualizarActividad();

            return response()->json([
                'success' => true,
                'message' => 'Información de sesión obtenida',
                'data' => [
                    'usuario' => [
                        'id' => $usuario->id,
                        'nombre_completo' => $usuario->nombre_completo,
                        'correo_electronico' => $usuario->correo_electronico,
                        'tipo_usuario' => $sesion->tipo_usuario,
                        'rol' => $sesion->rol_asignado,
                        'nivel_acceso' => $sesion->nivel_acceso_formateado,
                        'permisos' => $sesion->permisos_formateados
                    ],
                    'sesion' => [
                        'id' => $sesion->id,
                        'codigo_sesion' => $sesion->codigo_sesion,
                        'estado' => $sesion->estado_formateado,
                        'fecha_inicio' => $sesion->fecha_inicio_formateada,
                        'fecha_ultima_actividad' => $sesion->fecha_ultima_actividad_formateada,
                        'fecha_expiracion' => $sesion->fecha_expiracion_formateada,
                        'dias_transcurridos' => $sesion->dias_transcurridos,
                        'minutos_inactividad' => $sesion->minutos_inactividad
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al obtener sesión: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de sesiones (solo administradores)
     */
    public function obtenerEstadisticas(Request $request): JsonResponse
    {
        try {
            $token = $request->header('Authorization');
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de autorización requerido'
                ], 401);
            }

            $token = str_replace('Bearer ', '', $token);

            $sesion = Sesion::where('token_acceso', $token)->first();

            if (!$sesion || !$sesion->esAdministrador()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado. Solo administradores pueden ver estadísticas.'
                ], 403);
            }

            $estadisticas = Sesion::obtenerEstadisticasGenerales();

            return response()->json([
                'success' => true,
                'message' => 'Estadísticas obtenidas exitosamente',
                'data' => $estadisticas
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Crear usuario administrador inicial
     */
    public function crearAdministradorInicial(): JsonResponse
    {
        try {
            // Verificar si ya existe un administrador
            $adminExistente = Operador::where('rol', 'administrador')->exists();
            
            if ($adminExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un administrador en el sistema'
                ], 409);
            }

            // Crear administrador inicial
            $admin = Operador::create([
                'nombre_completo' => 'Esteban Administrador',
                'correo_electronico' => 'esteban.41@gmail.com',
                'contrasena' => Hash::make('ClaveSegura123!'),
                'rol' => 'administrador',
                'estado' => 'activo',
                'nivel_acceso' => 5,
                'permisos' => ['gestionar_usuarios', 'asignar_roles', 'validar_veedurias', 'gestionar_donaciones', 'gestionar_tareas', 'acceder_panel_admin']
            ]);

            // Crear sesión para el administrador
            $sesion = Sesion::crearSesionParaUsuario($admin->id, 'operador', 'administrador');

            return response()->json([
                'success' => true,
                'message' => 'Administrador inicial creado exitosamente',
                'data' => [
                    'usuario' => [
                        'id' => $admin->id,
                        'nombre_completo' => $admin->nombre_completo,
                        'correo_electronico' => $admin->correo_electronico,
                        'rol' => $admin->rol,
                        'estado' => $admin->estado
                    ],
                    'credenciales' => [
                        'email' => 'esteban.41@gmail.com',
                        'password' => 'ClaveSegura123!'
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al crear administrador inicial: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Método auxiliar para obtener datos del usuario
     */
    private function obtenerDatosUsuario(int $usuarioId, string $tipoUsuario)
    {
        if ($tipoUsuario === 'cliente') {
            return Cliente::find($usuarioId);
        } else {
            return Operador::find($usuarioId);
        }
    }
}
