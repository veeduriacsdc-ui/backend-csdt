<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UsuarioController extends Controller
{
    /**
     * Listar usuarios con filtros y paginación
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Usuario::with(['roles']);

            // Filtros
            if ($request->has('rol')) {
                $query->where('rol', $request->rol);
            }

            if ($request->has('est')) {
                $query->where('est', $request->est);
            }

            if ($request->has('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('nom', 'like', "%{$buscar}%")
                      ->orWhere('ape', 'like', "%{$buscar}%")
                      ->orWhere('cor', 'like', "%{$buscar}%")
                      ->orWhere('doc', 'like', "%{$buscar}%");
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'created_at');
            $direccion = $request->get('direccion', 'desc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $porPagina = $request->get('por_pagina', 15);
            $usuarios = $query->paginate($porPagina);

            return response()->json([
                'success' => true,
                'data' => $usuarios,
                'message' => 'Usuarios obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al listar usuarios: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo usuario
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nom' => 'required|string|max:100',
                'ape' => 'required|string|max:100',
                'cor' => 'required|email|max:150|unique:usu,cor',
                'con' => 'required|string|min:8',
                'tel' => 'nullable|string|max:20',
                'doc' => 'nullable|string|max:20|unique:usu,doc',
                'tip_doc' => 'nullable|in:cc,ce,ti,pp,nit',
                'fec_nac' => 'nullable|date',
                'dir' => 'nullable|string|max:200',
                'ciu' => 'nullable|string|max:100',
                'dep' => 'nullable|string|max:100',
                'gen' => 'nullable|in:m,f,o,n',
                'rol' => 'required|in:cli,ope,adm',
                'est' => 'nullable|in:act,ina,sus,pen'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

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
                'rol' => $request->rol,
                'est' => $request->est ?? 'pen',
                'not' => $request->not
            ]);

            // Asignar roles si se proporcionan
            if ($request->has('roles')) {
                $usuario->roles()->attach($request->roles);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $usuario->load('roles'),
                'message' => 'Usuario creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar usuario específico
     */
    public function show($id): JsonResponse
    {
        try {
            $usuario = Usuario::with(['roles', 'veedurias', 'donaciones', 'tareasAsignadas'])
                             ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $usuario,
                'message' => 'Usuario obtenido exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error al obtener usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar usuario
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $usuario = Usuario::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'nom' => 'sometimes|string|max:100',
                'ape' => 'sometimes|string|max:100',
                'cor' => 'sometimes|email|max:150|unique:usu,cor,' . $id,
                'con' => 'sometimes|string|min:8',
                'tel' => 'nullable|string|max:20',
                'doc' => 'nullable|string|max:20|unique:usu,doc,' . $id,
                'tip_doc' => 'nullable|in:cc,ce,ti,pp,nit',
                'fec_nac' => 'nullable|date',
                'dir' => 'nullable|string|max:200',
                'ciu' => 'nullable|string|max:100',
                'dep' => 'nullable|string|max:100',
                'gen' => 'nullable|in:m,f,o,n',
                'rol' => 'sometimes|in:cli,ope,adm',
                'est' => 'sometimes|in:act,ina,sus,pen'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $datos = $request->only([
                'nom', 'ape', 'cor', 'tel', 'doc', 'tip_doc', 
                'fec_nac', 'dir', 'ciu', 'dep', 'gen', 'rol', 'est', 'not'
            ]);

            if ($request->has('con')) {
                $datos['con'] = Hash::make($request->con);
            }

            $usuario->update($datos);

            // Actualizar roles si se proporcionan
            if ($request->has('roles')) {
                $usuario->roles()->sync($request->roles);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $usuario->load('roles'),
                'message' => 'Usuario actualizado exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar usuario (soft delete)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $usuario = Usuario::findOrFail($id);
            $usuario->delete();

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error al eliminar usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar usuarios
     */
    public function buscar(Request $request): JsonResponse
    {
        try {
            $termino = $request->get('q', '');
            $limite = $request->get('limite', 10);

            $usuarios = Usuario::where(function($query) use ($termino) {
                $query->where('nom', 'like', "%{$termino}%")
                      ->orWhere('ape', 'like', "%{$termino}%")
                      ->orWhere('cor', 'like', "%{$termino}%")
                      ->orWhere('doc', 'like', "%{$termino}%");
            })
            ->where('est', 'act')
            ->limit($limite)
            ->get(['id', 'nom', 'ape', 'cor', 'rol', 'est']);

            return response()->json([
                'success' => true,
                'data' => $usuarios,
                'message' => 'Búsqueda completada'
            ]);

        } catch (\Exception $e) {
            Log::error('Error en búsqueda de usuarios: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error en la búsqueda',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar datos de usuario
     */
    public function validar(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'cor' => 'required|email|unique:usu,cor',
                'doc' => 'nullable|string|unique:usu,doc'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos no válidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Datos válidos'
            ]);

        } catch (\Exception $e) {
            Log::error('Error en validación de usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error en validación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar usuario
     */
    public function activar($id): JsonResponse
    {
        try {
            $usuario = Usuario::findOrFail($id);
            $usuario->update(['est' => 'act']);

            return response()->json([
                'success' => true,
                'message' => 'Usuario activado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al activar usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al activar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Desactivar usuario
     */
    public function desactivar($id): JsonResponse
    {
        try {
            $usuario = Usuario::findOrFail($id);
            $usuario->update(['est' => 'ina']);

            return response()->json([
                'success' => true,
                'message' => 'Usuario desactivado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al desactivar usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar correo
     */
    public function verificarCorreo($id): JsonResponse
    {
        try {
            $usuario = Usuario::findOrFail($id);
            $usuario->verificarCorreo();

            return response()->json([
                'success' => true,
                'message' => 'Correo verificado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al verificar correo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar correo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de usuarios
     */
    public function estadisticas(): JsonResponse
    {
        try {
            $estadisticas = [
                'total' => Usuario::count(),
                'activos' => Usuario::where('est', 'act')->count(),
                'inactivos' => Usuario::where('est', 'ina')->count(),
                'pendientes' => Usuario::where('est', 'pen')->count(),
                'suspendidos' => Usuario::where('est', 'sus')->count(),
                'clientes' => Usuario::where('rol', 'cli')->count(),
                'operadores' => Usuario::where('rol', 'ope')->count(),
                'administradores' => Usuario::where('rol', 'adm')->count(),
                'verificados' => Usuario::where('cor_ver', true)->count(),
                'no_verificados' => Usuario::where('cor_ver', false)->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}