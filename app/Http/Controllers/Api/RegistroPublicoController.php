<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class RegistroPublicoController extends Controller
{
    /**
     * Registrar cliente
     */
    public function registrarCliente(Request $request): JsonResponse
    {
        return $this->registrarUsuario($request, 'cli');
    }

    /**
     * Registrar operador
     */
    public function registrarOperador(Request $request): JsonResponse
    {
        return $this->registrarUsuario($request, 'ope');
    }

    /**
     * Registrar administrador
     */
    public function registrarAdministrador(Request $request): JsonResponse
    {
        return $this->registrarUsuario($request, 'adm');
    }

    /**
     * Método común para registrar usuarios
     */
    private function registrarUsuario(Request $request, string $rol): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nom' => 'required|string|max:100',
                'ape' => 'required|string|max:100',
                'cor' => 'required|email|max:150|unique:usu,cor',
                'con' => 'required|string|min:8',
                'con_confirmation' => 'required_with:con|same:con',
                'tel' => 'nullable|string|max:20',
                'doc' => 'required|string|max:20|unique:usu,doc',
                'tip_doc' => 'required|in:cc,ce,ti,pp,nit',
                'fec_nac' => 'nullable|date',
                'dir' => 'nullable|string|max:200',
                'ciu' => 'nullable|string|max:100',
                'dep' => 'nullable|string|max:100',
                'gen' => 'nullable|in:m,f,o,n',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $usuario = Usuario::create([
                'nom' => $request->nom,
                'ape' => $request->ape,
                'cor' => $request->cor,
                'con' => Hash::make($request->con),
                'tel' => $request->tel,
                'doc' => $request->doc,
                'tip_doc' => $request->tip_doc,
                'fec_nac' => $request->fec_nac,
                'dir' => $request->dir,
                'ciu' => $request->ciu,
                'dep' => $request->dep,
                'gen' => $request->gen,
                'rol' => $rol,
                'est' => 'pen', // Pendiente de aprobación
            ]);

            // Asignar rol correspondiente
            $rolModel = Rol::where('nom', $this->obtenerNombreRol($rol))->first();
            if ($rolModel) {
                $usuario->roles()->attach($rolModel->id, [
                    'asig_por' => 1, // Administrador general
                    'asig_en' => now(),
                    'act' => true
                ]);
            }

            // Log de registro
            Log::crear('registro_publico', 'usuarios', $usuario->id, 
                      "Usuario {$rol} registrado públicamente: {$usuario->nombre_completo}");

            return response()->json([
                'success' => true,
                'data' => $usuario->load('roles'),
                'message' => "Usuario {$rol} registrado exitosamente. Pendiente de aprobación."
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener nombre del rol
     */
    private function obtenerNombreRol(string $rol): string
    {
        $nombres = [
            'cli' => 'Cliente',
            'ope' => 'Operador',
            'adm' => 'Administrador',
            'adm_gen' => 'Administrador General'
        ];

        return $nombres[$rol] ?? 'Cliente';
    }

    /**
     * Validar campos de registro
     */
    public function validarCampos(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cor' => 'required|email|unique:usu,cor',
            'doc' => 'required|string|unique:usu,doc',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Campos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Campos válidos'
        ]);
    }
}
