<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Operador;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login de usuario (cliente u operador)
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
                'tipo_usuario' => 'required|in:cliente,operador'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $email = $request->email;
            $password = $request->password;
            $tipoUsuario = $request->tipo_usuario;

            // Buscar usuario según el tipo
            if ($tipoUsuario === 'cliente') {
                $usuario = Cliente::where('Correo', $email)->first();
            } else {
                $usuario = Operador::where('Correo', $email)->first();
            }

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales inválidas'
                ], 401);
            }

            // Verificar contraseña
            if (!Hash::check($password, $usuario->Contrasena)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales inválidas'
                ], 401);
            }

            // Verificar estado del usuario
            if ($usuario->Estado !== 'Activo') {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario inactivo. Contacte al administrador.'
                ], 403);
            }

            // Actualizar último acceso
            $usuario->actualizarUltimoAcceso();

            // Crear token
            $token = $usuario->createToken('auth-token')->plainTextToken;

            // Preparar datos de respuesta
            $datosUsuario = [
                'id' => $usuario->getKey(),
                'nombres' => $usuario->Nombres,
                'apellidos' => $usuario->Apellidos,
                'correo' => $usuario->Correo,
                'tipo_usuario' => $tipoUsuario,
                'estado' => $usuario->Estado,
                'ultimo_acceso' => $usuario->UltimoAcceso
            ];

            // Agregar datos específicos según el tipo de usuario
            if ($tipoUsuario === 'operador') {
                $datosUsuario['rol'] = $usuario->Rol;
                $datosUsuario['profesion'] = $usuario->Profesion;
                $datosUsuario['especializacion'] = $usuario->Especializacion;
            } else {
                $datosUsuario['correo_verificado'] = $usuario->CorreoVerificado;
                $datosUsuario['ciudad'] = $usuario->Ciudad;
                $datosUsuario['departamento'] = $usuario->Departamento;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'usuario' => $datosUsuario,
                    'token' => $token,
                    'token_type' => 'Bearer'
                ],
                'message' => 'Login exitoso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en el login: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registro de cliente
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'Nombres' => 'required|string|max:255',
                'Apellidos' => 'required|string|max:255',
                'Correo' => 'required|email|unique:Clientes,Correo',
                'Contrasena' => 'required|string|min:8|confirmed',
                'Telefono' => 'nullable|string|max:20',
                'DocumentoIdentidad' => 'nullable|string|max:20|unique:Clientes,DocumentoIdentidad',
                'TipoDocumento' => 'nullable|in:CC,CE,TI,PP',
                'FechaNacimiento' => 'nullable|date|before:today',
                'Direccion' => 'nullable|string|max:500',
                'Ciudad' => 'nullable|string|max:100',
                'Departamento' => 'nullable|string|max:100',
                'Genero' => 'nullable|in:Masculino,Femenino,Otro',
                'AceptoTerminos' => 'required|boolean|accepted',
                'AceptoPoliticas' => 'required|boolean|accepted'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->all();
            $data['Contrasena'] = Hash::make($data['Contrasena']);
            $data['Estado'] = 'Activo';
            $data['CorreoVerificado'] = false;

            $cliente = Cliente::create($data);

            // Crear token para el usuario registrado
            $token = $cliente->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'data' => [
                    'usuario' => [
                        'id' => $cliente->IdCliente,
                        'nombres' => $cliente->Nombres,
                        'apellidos' => $cliente->Apellidos,
                        'correo' => $cliente->Correo,
                        'tipo_usuario' => 'cliente',
                        'estado' => $cliente->Estado,
                        'correo_verificado' => $cliente->CorreoVerificado
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer'
                ],
                'message' => 'Cliente registrado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en el registro: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout del usuario
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $usuario = $request->user();
            
            if ($usuario) {
                // Revocar todos los tokens del usuario
                $usuario->tokens()->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Logout exitoso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en el logout: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refrescar token
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $usuario = $request->user();
            
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Revocar token actual
            $request->user()->currentAccessToken()->delete();

            // Crear nuevo token
            $token = $usuario->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'token_type' => 'Bearer'
                ],
                'message' => 'Token refrescado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al refrescar token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información del usuario autenticado
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $usuario = $request->user();
            
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Determinar tipo de usuario
            $tipoUsuario = $usuario instanceof Cliente ? 'cliente' : 'operador';

            $datosUsuario = [
                'id' => $usuario->getKey(),
                'nombres' => $usuario->Nombres,
                'apellidos' => $usuario->Apellidos,
                'correo' => $usuario->Correo,
                'tipo_usuario' => $tipoUsuario,
                'estado' => $usuario->Estado,
                'ultimo_acceso' => $usuario->UltimoAcceso
            ];

            // Agregar datos específicos según el tipo de usuario
            if ($tipoUsuario === 'operador') {
                $datosUsuario['rol'] = $usuario->Rol;
                $datosUsuario['profesion'] = $usuario->Profesion;
                $datosUsuario['especializacion'] = $usuario->Especializacion;
            } else {
                $datosUsuario['correo_verificado'] = $usuario->CorreoVerificado;
                $datosUsuario['ciudad'] = $usuario->Ciudad;
                $datosUsuario['departamento'] = $usuario->Departamento;
            }

            return response()->json([
                'success' => true,
                'data' => $datosUsuario,
                'message' => 'Información del usuario obtenida exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información del usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar contraseña del usuario autenticado
     */
    public function cambiarPassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'password_actual' => 'required|string',
                'password_nuevo' => 'required|string|min:8|confirmed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $usuario = $request->user();
            
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Verificar contraseña actual
            if (!Hash::check($request->password_actual, $usuario->Contrasena)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta'
                ], 422);
            }

            // Actualizar contraseña
            $usuario->update([
                'Contrasena' => Hash::make($request->password_nuevo)
            ]);

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
     * Verificar si el email está disponible
     */
    public function verificarEmail(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'tipo_usuario' => 'required|in:cliente,operador'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email inválido',
                    'errors' => $validator->errors()
                ], 422);
            }

            $email = $request->email;
            $tipoUsuario = $request->tipo_usuario;

            // Verificar disponibilidad según el tipo de usuario
            if ($tipoUsuario === 'cliente') {
                $existe = Cliente::where('Correo', $email)->exists();
            } else {
                $existe = Operador::where('Correo', $email)->exists();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'email' => $email,
                    'disponible' => !$existe
                ],
                'message' => $existe ? 'Email ya está en uso' : 'Email disponible'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar email: ' . $e->getMessage()
            ], 500);
        }
    }
}
