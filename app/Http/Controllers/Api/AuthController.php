<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Iniciar sesión
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Manejar tanto campos nuevos como antiguos para compatibilidad
            $email = $request->cor ?? $request->email;
            $password = $request->con ?? $request->password;

            $validator = Validator::make([
                'cor' => $email,
                'con' => $password
            ], [
                'cor' => 'required|email',
                'con' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $usuario = Usuario::where('cor', $email)->first();

            if (!$usuario || !Hash::check($password, $usuario->con)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales inválidas'
                ], 401);
            }

            if ($usuario->est !== 'act') {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario inactivo o suspendido'
                ], 401);
            }

            $token = $usuario->createToken('auth_token')->plainTextToken;
            $usuario->actualizarUltimoAcceso();

            // Log de login
            Log::crear('login', 'usuarios', $usuario->id, 'Usuario inició sesión');

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $usuario,
                    'token' => $token,
                    'token_type' => 'Bearer'
                ],
                'message' => 'Inicio de sesión exitoso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar sesión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registrar usuario
     */
    public function register(Request $request): JsonResponse
    {
        try {
            // Mapear campos del frontend a campos de la base de datos
            $datos = [
                'nom' => $request->nom ?? $request->nombre ?? '',
                'ape' => $request->ape ?? $request->apellido ?? '',
                'cor' => $request->cor ?? $request->email ?? '',
                'con' => $request->con ?? $request->password ?? $request->pass ?? '',
                'con_confirmation' => $request->con_confirmation ?? $request->password_confirmation ?? $request->pass_confirmation ?? '',
                'tel' => $request->tel ?? $request->telefono ?? '',
                'doc' => $request->doc ?? $request->numeroDocumento ?? $request->documento ?? '',
                'tip_doc' => $request->tip_doc ?? $request->tipoDocumento ?? 'cc',
                'rol' => $request->rol ?? 'cli',
                'est' => 'act', // Activo por defecto
            ];

            $validator = Validator::make($datos, Usuario::reglas(), Usuario::mensajes());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Remover confirmation del array de datos
            unset($datos['con_confirmation']);

            $usuario = Usuario::create($datos);

            // Log de registro
            Log::crear('registro', 'usuarios', $usuario->id, 'Usuario registrado');

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $usuario,
                    'message' => 'Usuario registrado exitosamente. Verifique su correo electrónico.'
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $usuario = $request->user();

            // Log de logout
            Log::crear('logout', 'usuarios', $usuario->id, 'Usuario cerró sesión');

            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar sesión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener usuario autenticado
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $usuario = $request->user();
            $usuario->load(['roles', 'veedurias', 'donaciones']);

            return response()->json([
                'success' => true,
                'data' => $usuario,
                'message' => 'Usuario obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuario: ' . $e->getMessage()
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
                'con_actual' => 'required|string',
                'con_nueva' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $usuario = $request->user();

            if (!Hash::check($request->con_actual, $usuario->con)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contraseña actual incorrecta'
                ], 422);
            }

            $usuario->update(['con' => $request->con_nueva]); // El mutator hashea automáticamente

            // Log de cambio de contraseña
            Log::crear('cambiar_contrasena', 'usuarios', $usuario->id, 'Usuario cambió su contraseña');

            return response()->json([
                'success' => true,
                'message' => 'Contraseña cambiada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar contraseña: ' . $e->getMessage()
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
                'cor' => 'required|email|exists:usu,cor'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $usuario = Usuario::where('cor', $request->cor)->first();

            // Aquí se enviaría un email con el enlace de recuperación
            // Por ahora solo logueamos la acción

            // Log de recuperación de contraseña
            Log::crear('recuperar_contrasena', 'usuarios', $usuario->id, 'Usuario solicitó recuperación de contraseña');

            return response()->json([
                'success' => true,
                'message' => 'Se ha enviado un enlace de recuperación a su correo electrónico'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al recuperar contraseña: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resetear contraseña
     */
    public function resetearContrasena(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'cor' => 'required|email|exists:usu,cor',
                'token' => 'required|string',
                'con_nueva' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $usuario = Usuario::where('cor', $request->cor)->first();

            // Aquí se validaría el token de recuperación
            // Por ahora solo actualizamos la contraseña

            $usuario->update(['con' => $request->con_nueva]); // El mutator hashea automáticamente

            // Log de reseteo de contraseña
            Log::crear('resetear_contrasena', 'usuarios', $usuario->id, 'Usuario reseteó su contraseña');

            return response()->json([
                'success' => true,
                'message' => 'Contraseña reseteada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al resetear contraseña: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar email
     */
    public function verificarEmail(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'cor' => 'required|email|exists:usu,cor',
                'token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $usuario = Usuario::where('cor', $request->cor)->first();

            // Aquí se validaría el token de verificación
            // Por ahora solo verificamos el correo

            $usuario->verificarCorreo();
            $usuario->activarCuenta();

            // Log de verificación de email
            Log::crear('verificar_email', 'usuarios', $usuario->id, 'Usuario verificó su correo electrónico');

            return response()->json([
                'success' => true,
                'data' => $usuario,
                'message' => 'Correo electrónico verificado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar correo: ' . $e->getMessage()
            ], 500);
        }
    }
}