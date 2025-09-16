<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegistroPendiente;
use App\Models\UsuarioSistema;
use App\Models\Operador;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RegistroControlador extends Controller
{
    /**
     * Validar que la cédula no esté registrada en ninguna tabla
     */
    private function validarCedulaUnica($documento_identidad)
    {
        // Verificar en registros_pendientes
        $existeRegistro = RegistroPendiente::where('documento_identidad', $documento_identidad)->exists();
        if ($existeRegistro) {
            return 'La cédula ya tiene una solicitud de registro pendiente';
        }

        // Verificar en operadores
        $existeOperador = Operador::where('documento_identidad', $documento_identidad)->exists();
        if ($existeOperador) {
            return 'La cédula ya está registrada como operador';
        }

        // Verificar en clientes
        $existeCliente = Cliente::where('documento_identidad', $documento_identidad)->exists();
        if ($existeCliente) {
            return 'La cédula ya está registrada como cliente';
        }

        return null; // No existe en ninguna tabla
    }

    /**
     * Validar que el email no esté registrado en ninguna tabla
     */
    private function validarEmailUnico($email)
    {
        // Verificar en usuariossistema
        $existeUsuario = UsuarioSistema::where('Correo', $email)->exists();
        if ($existeUsuario) {
            return 'El email ya está registrado en el sistema de usuarios';
        }

        // Verificar en registros_pendientes
        $existeRegistro = RegistroPendiente::where('email', $email)->exists();
        if ($existeRegistro) {
            return 'El email ya tiene una solicitud de registro pendiente';
        }

        // Verificar en operadores
        $existeOperador = Operador::where('correo', $email)->exists();
        if ($existeOperador) {
            return 'El email ya está registrado como operador';
        }

        // Verificar en clientes
        $existeCliente = Cliente::where('correo', $email)->exists();
        if ($existeCliente) {
            return 'El email ya está registrado como cliente';
        }

        return null; // No existe en ninguna tabla
    }

    /**
     * Registrar nuevo usuario
     */
    public function registrar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telefono' => 'nullable|string|max:20',
            'documento_identidad' => 'required|string|max:50',
            'tipo_documento' => 'required|string|in:CC,CE,TI,RC,PA',
            'rol_solicitado' => 'required|string|in:cliente,operador,administrador',
            'motivacion' => 'nullable|string|max:1000',
            'experiencia' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de registro inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validar que el email no esté registrado en ninguna tabla
        $validacionEmail = $this->validarEmailUnico($request->email);
        if ($validacionEmail) {
            return response()->json([
                'success' => false,
                'message' => $validacionEmail,
                'errors' => [
                    'email' => [$validacionEmail]
                ]
            ], 422);
        }

        // Validar que la cédula no esté registrada en ninguna tabla
        $validacionCedula = $this->validarCedulaUnica($request->documento_identidad);
        if ($validacionCedula) {
            return response()->json([
                'success' => false,
                'message' => $validacionCedula,
                'errors' => [
                    'documento_identidad' => [$validacionCedula]
                ]
            ], 422);
        }

        try {
            // Crear registro pendiente
            $registro = RegistroPendiente::create([
                'nombre' => $request->nombre,
                'email' => $request->email,
                'telefono' => $request->telefono,
                'documento_identidad' => $request->documento_identidad,
                'tipo_documento' => $request->tipo_documento,
                'rol_solicitado' => $request->rol_solicitado,
                'motivacion' => $request->motivacion,
                'experiencia' => $request->experiencia,
                'token_verificacion' => RegistroPendiente::generarTokenVerificacion()
            ]);

            // Si es cliente, aprobación automática
            if ($registro->rol_solicitado === 'cliente') {
                $this->aprobarClienteAutomaticamente($registro);
            } else {
                // Enviar email de verificación
                $this->enviarEmailVerificacion($registro);
            }

            return response()->json([
                'success' => true,
                'message' => $registro->rol_solicitado === 'cliente' 
                    ? 'Registro exitoso. Puedes iniciar sesión con tus credenciales.'
                    : 'Registro enviado. Revisa tu email para verificar tu cuenta.',
                'data' => [
                    'id' => $registro->id,
                    'email' => $registro->email,
                    'rol_solicitado' => $registro->rol_solicitado,
                    'requiere_aprobacion' => $registro->requiereAprobacion()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el registro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar email del registro
     */
    public function verificarEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Token de verificación inválido'
            ], 422);
        }

        $registro = RegistroPendiente::where('token_verificacion', $request->token)->first();

        if (!$registro) {
            return response()->json([
                'success' => false,
                'message' => 'Token de verificación no válido o expirado'
            ], 404);
        }

        if ($registro->emailVerificado()) {
            return response()->json([
                'success' => false,
                'message' => 'El email ya ha sido verificado'
            ], 400);
        }

        $registro->marcarEmailVerificado();

        return response()->json([
            'success' => true,
            'message' => 'Email verificado exitosamente. Tu solicitud está en proceso de revisión.'
        ]);
    }

    /**
     * Obtener registros pendientes (solo administradores)
     */
    public function obtenerPendientes(Request $request)
    {
        $user = $request->user();
        
        if (!in_array($user->rol, ['administrador', 'adming'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para acceder a esta información'
            ], 403);
        }

        $registros = RegistroPendiente::with('aprobadoPor')
            ->verificados()
            ->pendientes()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $registros
        ]);
    }

    /**
     * Aprobar registro (solo administradores)
     */
    public function aprobar(Request $request, $id)
    {
        $user = $request->user();
        
        if (!in_array($user->rol, ['administrador', 'adming'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para aprobar registros'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'observaciones' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $registro = RegistroPendiente::find($id);

        if (!$registro) {
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado'
            ], 404);
        }

        if ($registro->estado !== 'pendiente') {
            return response()->json([
                'success' => false,
                'message' => 'Este registro ya ha sido procesado'
            ], 400);
        }

        if (!$registro->emailVerificado()) {
            return response()->json([
                'success' => false,
                'message' => 'El email debe estar verificado antes de aprobar'
            ], 400);
        }

        try {
            // Crear usuario en el sistema
            $usuario = UsuarioSistema::create([
                'Nombre' => $registro->nombre,
                'Correo' => $registro->email,
                'Contrasena' => Hash::make('temporal123'), // Contraseña temporal
                'CorreoVerificadoEn' => now()
            ]);

            // Marcar registro como aprobado
            $registro->aprobar($user->id, $request->observaciones);

            // Enviar email con credenciales
            $this->enviarCredenciales($usuario);

            return response()->json([
                'success' => true,
                'message' => 'Registro aprobado exitosamente',
                'data' => [
                    'usuario_id' => $usuario->id,
                    'email' => $usuario->email,
                    'rol' => $usuario->rol
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar el registro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar registro (solo administradores)
     */
    public function rechazar(Request $request, $id)
    {
        $user = $request->user();
        
        if (!in_array($user->rol, ['administrador', 'adming'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para rechazar registros'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'observaciones' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Las observaciones son requeridas para rechazar un registro',
                'errors' => $validator->errors()
            ], 422);
        }

        $registro = RegistroPendiente::find($id);

        if (!$registro) {
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado'
            ], 404);
        }

        if ($registro->estado !== 'pendiente') {
            return response()->json([
                'success' => false,
                'message' => 'Este registro ya ha sido procesado'
            ], 400);
        }

        try {
            $registro->rechazar($user->id, $request->observaciones);

            // Enviar email de rechazo
            $this->enviarNotificacionRechazo($registro);

            return response()->json([
                'success' => true,
                'message' => 'Registro rechazado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar el registro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aprobar cliente automáticamente
     */
    private function aprobarClienteAutomaticamente(RegistroPendiente $registro)
    {
        // Marcar email como verificado
        $registro->marcarEmailVerificado();

        // Crear usuario directamente
        $usuario = UsuarioSistema::create([
            'Nombre' => $registro->nombre,
            'Correo' => $registro->email,
            'Contrasena' => Hash::make('cliente123'), // Contraseña por defecto
            'CorreoVerificadoEn' => now()
        ]);

        // Marcar registro como aprobado automáticamente
        $registro->aprobar(0, 'Aprobación automática para clientes');

        // Enviar credenciales
        $this->enviarCredenciales($usuario);
    }

    /**
     * Enviar email de verificación
     */
    private function enviarEmailVerificacion(RegistroPendiente $registro)
    {
        // Aquí se implementaría el envío de email
        // Por ahora solo log
        \Log::info("Email de verificación enviado a: {$registro->email}");
    }

    /**
     * Enviar credenciales por email
     */
    private function enviarCredenciales(UsuarioSistema $usuario)
    {
        // Aquí se implementaría el envío de email con credenciales
        // Por ahora solo log
        \Log::info("Credenciales enviadas a: {$usuario->email}");
    }

    /**
     * Enviar notificación de rechazo
     */
    private function enviarNotificacionRechazo(RegistroPendiente $registro)
    {
        // Aquí se implementaría el envío de email de rechazo
        // Por ahora solo log
        \Log::info("Notificación de rechazo enviada a: {$registro->email}");
    }
}
