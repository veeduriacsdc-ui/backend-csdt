<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Operador;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Validar si el email ya existe en cualquier tabla
     */
    private function validarEmailUnico($email)
    {
        $existeEnClientes = Cliente::where('correo', $email)->exists();
        $existeEnOperadores = Operador::where('correo', $email)->exists();
        
        if ($existeEnClientes) {
            return [
                'existe' => true,
                'tabla' => 'clientes',
                'mensaje' => 'El email ya está registrado como cliente'
            ];
        }
        
        if ($existeEnOperadores) {
            $operador = Operador::where('correo', $email)->first();
            return [
                'existe' => true,
                'tabla' => 'operadores',
                'mensaje' => "El email ya está registrado como {$operador->rol}"
            ];
        }
        
        return ['existe' => false];
    }

    /**
     * Validar si el usuario ya existe en cualquier tabla
     */
    private function validarUsuarioUnico($usuario)
    {
        $existeEnClientes = Cliente::where('usuario', $usuario)->exists();
        $existeEnOperadores = Operador::where('usuario', $usuario)->exists();
        
        if ($existeEnClientes) {
            return [
                'existe' => true,
                'tabla' => 'clientes',
                'mensaje' => 'El nombre de usuario ya está registrado como cliente'
            ];
        }
        
        if ($existeEnOperadores) {
            $operador = Operador::where('usuario', $usuario)->first();
            return [
                'existe' => true,
                'tabla' => 'operadores',
                'mensaje' => "El nombre de usuario ya está registrado como {$operador->rol}"
            ];
        }
        
        return ['existe' => false];
    }

    /**
     * Validar si el documento ya existe en cualquier tabla
     */
    private function validarDocumentoUnico($numeroDocumento, $tipoDocumento)
    {
        $existeEnClientes = Cliente::where('documento_identidad', $numeroDocumento)
            ->where('tipo_documento', $tipoDocumento)
            ->exists();
            
        $existeEnOperadores = Operador::where('documento_identidad', $numeroDocumento)
            ->where('tipo_documento', $tipoDocumento)
            ->exists();
        
        if ($existeEnClientes) {
            return [
                'existe' => true,
                'tabla' => 'clientes',
                'mensaje' => "El documento {$tipoDocumento} {$numeroDocumento} ya está registrado como cliente"
            ];
        }
        
        if ($existeEnOperadores) {
            $operador = Operador::where('documento_identidad', $numeroDocumento)
                ->where('tipo_documento', $tipoDocumento)
                ->first();
            return [
                'existe' => true,
                'tabla' => 'operadores',
                'mensaje' => "El documento {$tipoDocumento} {$numeroDocumento} ya está registrado como {$operador->rol}"
            ];
        }
        
        return ['existe' => false];
    }

    /**
     * Validar todos los campos únicos antes del registro
     */
    private function validarCamposUnicos($request)
    {
        $errores = [];

        // Validar email único
        $validacionEmail = $this->validarEmailUnico($request->email);
        if ($validacionEmail['existe']) {
            $errores['email'] = $validacionEmail['mensaje'];
        }

        // Validar usuario único
        $validacionUsuario = $this->validarUsuarioUnico($request->usuario);
        if ($validacionUsuario['existe']) {
            $errores['usuario'] = $validacionUsuario['mensaje'];
        }

        // Validar documento único
        $validacionDocumento = $this->validarDocumentoUnico($request->numeroDocumento, $request->tipoDocumento);
        if ($validacionDocumento['existe']) {
            $errores['documento'] = $validacionDocumento['mensaje'];
        }

        return $errores;
    }
    /**
     * Login simplificado para clientes y operadores
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'usuario' => 'required|string',
                'contrasena' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Buscar usuario en clientes primero
            $usuario = Cliente::where('usuario', $request->usuario)->first();
            $tipoUsuario = 'cliente';

            // Si no se encuentra en clientes, buscar en operadores
            if (!$usuario) {
                $usuario = Operador::where('usuario', $request->usuario)->first();
                $tipoUsuario = 'operador';
            }

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas',
                ], 401);
            }

            if (!Hash::check($request->contrasena, $usuario->contrasena)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales incorrectas',
                ], 401);
            }

            // Verificar estado del usuario
            if ($usuario->estado !== 'activo' && $usuario->estado !== 'verificado') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cuenta no activa. Contacte al administrador.',
                ], 403);
            }

            // Generar token
            $token = $usuario->createToken('auth_token')->plainTextToken;

            // Actualizar último acceso
            $usuario->update(['ultimo_acceso' => now()]);

            // Preparar datos del usuario
            $usuarioData = [
                'id' => $usuario->id,
                'nombre' => $usuario->nombres,
                'email' => $usuario->correo,
                'usuario' => $usuario->usuario,
                'rol' => $tipoUsuario === 'cliente' ? 'cliente' : $usuario->rol,
                'estado' => $usuario->estado,
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'usuario' => $usuarioData,
                    'token' => $token,
                    'tipo_usuario' => $tipoUsuario,
                    'token_type' => 'Bearer',
                ],
                'message' => 'Inicio de sesión exitoso',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en el inicio de sesión: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Registro simplificado de usuario (cliente, operador, administrador)
     */
    public function registerCliente(Request $request): JsonResponse
    {
        try {
            // Validación mejorada
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:200|min:2',
                'email' => 'required|email|max:255',
                'usuario' => 'required|string|max:50|min:3|regex:/^[a-zA-Z0-9_]+$/',
                'contrasena' => 'required|string|min:6|max:255',
                'rol' => 'required|in:cliente,operador,administrador',
                'tipoDocumento' => 'required|string|in:cc,ce,ti,pp,nit',
                'numeroDocumento' => 'required|string|max:20|min:6',
            ], [
                'nombre.required' => 'El nombre es obligatorio',
                'nombre.min' => 'El nombre debe tener al menos 2 caracteres',
                'email.required' => 'El email es obligatorio',
                'email.email' => 'El email debe tener un formato válido',
                'usuario.required' => 'El nombre de usuario es obligatorio',
                'usuario.min' => 'El nombre de usuario debe tener al menos 3 caracteres',
                'usuario.regex' => 'El nombre de usuario solo puede contener letras, números y guiones bajos',
                'contrasena.required' => 'La contraseña es obligatoria',
                'contrasena.min' => 'La contraseña debe tener al menos 6 caracteres',
                'rol.required' => 'El rol es obligatorio',
                'tipoDocumento.required' => 'El tipo de documento es obligatorio',
                'numeroDocumento.required' => 'El número de documento es obligatorio',
                'numeroDocumento.min' => 'El número de documento debe tener al menos 6 caracteres',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $rol = $request->rol;

            // Validar campos únicos usando los métodos específicos
            $erroresValidacion = $this->validarCamposUnicos($request);
            
            if (!empty($erroresValidacion)) {
                $mensajeError = implode('. ', array_values($erroresValidacion));
                return response()->json([
                    'success' => false,
                    'message' => $mensajeError,
                    'errors' => $erroresValidacion,
                ], 422);
            }

            // Crear usuario según el rol
            if ($rol === 'cliente') {
                $usuario = Cliente::create([
                    'nombres' => trim($request->nombre),
                    'apellidos' => '',
                    'correo' => strtolower(trim($request->email)),
                    'usuario' => trim($request->usuario),
                    'contrasena' => Hash::make($request->contrasena),
                    'documento_identidad' => trim($request->numeroDocumento),
                    'tipo_documento' => $request->tipoDocumento,
                    'estado' => 'activo',
                    'acepto_terminos' => true,
                    'acepto_politicas' => true,
                ]);
            } else {
                $usuario = Operador::create([
                    'nombres' => trim($request->nombre),
                    'apellidos' => '',
                    'correo' => strtolower(trim($request->email)),
                    'usuario' => trim($request->usuario),
                'contrasena' => Hash::make($request->contrasena),
                    'documento_identidad' => trim($request->numeroDocumento),
                    'tipo_documento' => $request->tipoDocumento,
                    'rol' => $rol,
                    'profesion' => 'Profesional',
                    'especializacion' => 'Sistema CSDT',
                    'estado' => $rol === 'administrador' ? 'activo' : 'pendiente',
                    'acepto_terminos' => true,
                    'acepto_politicas' => true,
                ]);
            }

            // Log del registro exitoso
            \Log::info('Usuario registrado exitosamente', [
                'rol' => $rol,
                'email' => $request->email,
                'usuario' => $request->usuario,
                'tipo_documento' => $request->tipoDocumento,
                'numero_documento' => $request->numeroDocumento
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'usuario' => [
                        'id' => $usuario->id,
                        'nombre' => $usuario->nombres,
                        'email' => $usuario->correo,
                        'usuario' => $usuario->usuario,
                        'rol' => $rol,
                        'estado' => $usuario->estado,
                    ],
                ],
                'message' => $rol === 'cliente' ? 
                    'Cliente registrado exitosamente. Ya puedes iniciar sesión.' : 
                    'Usuario registrado exitosamente. Pendiente de aprobación por un administrador.',
            ], 201);

        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Error de base de datos en registro', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error en la base de datos. Intenta nuevamente.',
            ], 500);
        } catch (\Exception $e) {
            \Log::error('Error general en registro', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor. Intenta nuevamente.',
            ], 500);
        }
    }

    /**
     * Registro de operador (solo administradores)
     */
    public function registerOperador(Request $request): JsonResponse
    {
        try {
            // Verificar permisos de administrador
            $admin = Auth::guard('sanctum')->user();
            if (!$admin || !($admin instanceof Operador) || $admin->rol !== 'administrador') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para registrar operadores',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nombres' => 'required|string|max:100',
                'apellidos' => 'required|string|max:100',
                'correo' => 'required|email|unique:operadores,correo',
                'contrasena' => 'required|string|min:8',
                'telefono' => 'nullable|string|max:20',
                'documento_identidad' => 'nullable|string|max:20|unique:operadores,documento_identidad',
                'tipo_documento' => 'nullable|in:cc,ce,ti,pp',
                'fecha_nacimiento' => 'nullable|date|before:today',
                'ciudad' => 'nullable|string|max:100',
                'departamento' => 'nullable|string|max:100',
                'genero' => 'nullable|in:masculino,femenino,otro,no_especificado',
                'profesion' => 'required|string|max:100',
                'especializacion' => 'nullable|string|max:100',
                'numero_matricula' => 'nullable|string|max:50',
                'entidad_matricula' => 'nullable|string|max:100',
                'anos_experiencia' => 'nullable|integer|min:0',
                'perfil_profesional' => 'nullable|string',
                'areas_expertise' => 'nullable|array',
                'linkedin' => 'nullable|url',
                'sitio_web' => 'nullable|url',
                'rol' => 'required|in:operador,administrador',
                'nivel_administracion' => 'nullable|in:super,gestión,operativo',
                'administrador_supervisor_id' => 'nullable|exists:operadores,id',
                'acepto_terminos' => 'required|boolean|accepted',
                'acepto_politicas' => 'required|boolean|accepted',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $operador = Operador::create([
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'correo' => $request->correo,
                'contrasena' => Hash::make($request->contrasena),
                'telefono' => $request->telefono,
                'documento_identidad' => $request->documento_identidad,
                'tipo_documento' => $request->tipo_documento,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'ciudad' => $request->ciudad,
                'departamento' => $request->departamento,
                'genero' => $request->genero,
                'profesion' => $request->profesion,
                'especializacion' => $request->especializacion,
                'numero_matricula' => $request->numero_matricula,
                'entidad_matricula' => $request->entidad_matricula,
                'anos_experiencia' => $request->anos_experiencia,
                'perfil_profesional' => $request->perfil_profesional,
                'areas_expertise' => $request->areas_expertise,
                'linkedin' => $request->linkedin,
                'sitio_web' => $request->sitio_web,
                'rol' => $request->rol,
                'nivel_administracion' => $request->nivel_administracion,
                'administrador_supervisor_id' => $request->administrador_supervisor_id,
                'acepto_terminos' => $request->acepto_terminos,
                'acepto_politicas' => $request->acepto_politicas,
                'estado' => 'pendiente_aprobacion',
            ]);

            return response()->json([
                'success' => true,
                'data' => $operador,
                'message' => 'Operador registrado exitosamente. Pendiente de aprobación.',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en el registro: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener información del usuario autenticado
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            // Determinar tipo de usuario
            $tipoUsuario = $user instanceof Cliente ? 'cliente' : 'operador';

            // Cargar relaciones según tipo
            if ($tipoUsuario === 'cliente') {
                $user->load([]);
            } else {
                $user->load(['operador_supervisor', 'operadores_supervisados']);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'usuario' => $user,
                    'tipo_usuario' => $tipoUsuario,
                ],
                'message' => 'Información del usuario obtenida exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información del usuario: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user) {
                // Revocar todos los tokens del usuario
                $user->tokens()->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar sesión: '.$e->getMessage(),
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
                'contrasena_nueva' => 'required|string|min:8|confirmed|different:contrasena_actual',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            // Verificar contraseña actual
            if (!Hash::check($request->contrasena_actual, $user->contrasena)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta',
                ], 422);
            }

            // Actualizar contraseña
            $user->update([
                'contrasena' => Hash::make($request->contrasena_nueva),
            ]);

            // Revocar todos los tokens excepto el actual
            $currentToken = $request->bearerToken();
            $user->tokens()->where('token', '!=', hash('sha256', $currentToken))->delete();

            return response()->json([
                'success' => true,
                'message' => 'Contraseña cambiada exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar contraseña: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verificar correo electrónico
     */
    public function verificarEmail(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de verificación requerido',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Buscar usuario por token
            $user = null;
            $tipoUsuario = null;

            // Buscar en clientes
            $cliente = Cliente::whereHas('tokens', function ($query) use ($request) {
                $query->where('name', 'email_verification')
                      ->where('token', hash('sha256', $request->token));
            })->first();

            if ($cliente) {
                $user = $cliente;
                $tipoUsuario = 'cliente';
            } else {
                // Buscar en operadores
                $operador = Operador::whereHas('tokens', function ($query) use ($request) {
                    $query->where('name', 'email_verification')
                          ->where('token', hash('sha256', $request->token));
                })->first();

                if ($operador) {
                    $user = $operador;
                    $tipoUsuario = 'operador';
                }
            }

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de verificación inválido o expirado',
                ], 422);
            }

            // Verificar email
            $user->update([
                'correo_verificado' => true,
                'correo_verificado_en' => now(),
                'estado' => $user->estado === 'pendiente_verificacion' ? 'activo' : $user->estado,
            ]);

            // Eliminar token de verificación
            $user->tokens()->where('name', 'email_verification')->delete();

            return response()->json([
                'success' => true,
                'message' => 'Correo electrónico verificado exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar correo electrónico: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recuperar contraseña - enviar enlace
     */
    public function recuperarContrasena(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'correo' => 'required|email',
                'tipo_usuario' => 'required|in:cliente,operador',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $tipoUsuario = $request->tipo_usuario;
            $usuario = null;

            if ($tipoUsuario === 'cliente') {
                $usuario = Cliente::where('correo', $request->correo)->first();
            } else {
                $usuario = Operador::where('correo', $request->correo)->first();
            }

            if (!$usuario) {
                // No revelar si el email existe o no por seguridad
                return response()->json([
                    'success' => true,
                    'message' => 'Si el correo existe, se ha enviado un enlace de recuperación',
                ]);
            }

            // Generar token de recuperación
            $token = $usuario->createToken('password_reset')->plainTextToken;

            // Aquí se enviaría el email con el token
            // Por ahora solo retornamos el token para desarrollo
            return response()->json([
                'success' => true,
                'message' => 'Se ha enviado un enlace de recuperación a su correo electrónico',
                'token_reset' => $token, // Solo para desarrollo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud: '.$e->getMessage(),
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
                'token' => 'required|string',
                'contrasena' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Buscar usuario por token de reset
            $user = null;
            $tipoUsuario = null;

            // Buscar en clientes
            $cliente = Cliente::whereHas('tokens', function ($query) use ($request) {
                $query->where('name', 'password_reset')
                      ->where('token', hash('sha256', $request->token));
            })->first();

            if ($cliente) {
                $user = $cliente;
                $tipoUsuario = 'cliente';
            } else {
                // Buscar en operadores
                $operador = Operador::whereHas('tokens', function ($query) use ($request) {
                    $query->where('name', 'password_reset')
                          ->where('token', hash('sha256', $request->token));
                })->first();

                if ($operador) {
                    $user = $operador;
                    $tipoUsuario = 'operador';
                }
            }

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de recuperación inválido o expirado',
                ], 422);
            }

            // Actualizar contraseña
            $user->update([
                'contrasena' => Hash::make($request->contrasena),
            ]);

            // Eliminar token de reset
            $user->tokens()->where('name', 'password_reset')->delete();

            return response()->json([
                'success' => true,
                'message' => 'Contraseña restablecida exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restablecer contraseña: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validar campos únicos antes del registro (para validación en tiempo real)
     */
    public function validarCampos(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'nullable|email',
                'usuario' => 'nullable|string|max:50',
                'numeroDocumento' => 'nullable|string|max:20',
                'tipoDocumento' => 'nullable|string|in:CC,CE,TI,RC,PA,NIT',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $errores = [];

            // Validar email si se proporciona
            if ($request->has('email') && $request->email) {
                $validacionEmail = $this->validarEmailUnico($request->email);
                if ($validacionEmail['existe']) {
                    $errores['email'] = $validacionEmail['mensaje'];
                }
            }

            // Validar usuario si se proporciona
            if ($request->has('usuario') && $request->usuario) {
                $validacionUsuario = $this->validarUsuarioUnico($request->usuario);
                if ($validacionUsuario['existe']) {
                    $errores['usuario'] = $validacionUsuario['mensaje'];
                }
            }

            // Validar documento si se proporciona
            if ($request->has('numeroDocumento') && $request->numeroDocumento && 
                $request->has('tipoDocumento') && $request->tipoDocumento) {
                $validacionDocumento = $this->validarDocumentoUnico($request->numeroDocumento, $request->tipoDocumento);
                if ($validacionDocumento['existe']) {
                    $errores['documento'] = $validacionDocumento['mensaje'];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'valido' => empty($errores),
                    'errores' => $errores,
                ],
                'message' => empty($errores) ? 'Todos los campos son válidos' : 'Se encontraron errores de validación',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la validación: '.$e->getMessage(),
            ], 500);
        }
    }
}