<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class RolController extends Controller
{
    /**
     * Obtener lista de roles
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Rol::with(['usuarios']);

            // Filtros
            if ($request->has('est')) {
                $query->where('est', $request->est);
            }

            // Búsqueda
            if ($request->has('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('nom', 'like', '%' . $buscar . '%')
                      ->orWhere('des', 'like', '%' . $buscar . '%');
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'nom');
            $direccion = $request->get('direccion', 'asc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $porPagina = $request->get('por_pagina', 15);
            $roles = $query->paginate($porPagina);

            return response()->json([
                'success' => true,
                'data' => $roles,
                'message' => 'Roles obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener roles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener rol por ID
     */
    public function show($id): JsonResponse
    {
        try {
            $rol = Rol::with(['usuarios'])->find($id);

            if (!$rol) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rol no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $rol,
                'message' => 'Rol obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo rol
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), Rol::reglas(), Rol::mensajes());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $rol = Rol::create($request->all());

            // Log de creación
            Log::logCreacion('roles', $rol->id, $rol->toArray());

            return response()->json([
                'success' => true,
                'data' => $rol,
                'message' => 'Rol creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar rol
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $rol = Rol::find($id);

            if (!$rol) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rol no encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), Rol::reglas($id), Rol::mensajes());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $datosAnteriores = $rol->toArray();
            $rol->update($request->all());

            // Log de actualización
            Log::logActualizacion('roles', $rol->id, $datosAnteriores, $rol->toArray());

            return response()->json([
                'success' => true,
                'data' => $rol,
                'message' => 'Rol actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar rol
     */
    public function destroy($id): JsonResponse
    {
        try {
            $rol = Rol::find($id);

            if (!$rol) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rol no encontrado'
                ], 404);
            }

            // Verificar si el rol tiene usuarios asignados
            if ($rol->usuarios()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el rol porque tiene usuarios asignados'
                ], 422);
            }

            $datosAnteriores = $rol->toArray();
            $rol->delete();

            // Log de eliminación
            Log::logEliminacion('roles', $rol->id, $datosAnteriores);

            return response()->json([
                'success' => true,
                'message' => 'Rol eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar rol
     */
    public function activar($id): JsonResponse
    {
        try {
            $rol = Rol::find($id);

            if (!$rol) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rol no encontrado'
                ], 404);
            }

            $rol->activar();

            // Log de activación
            Log::crear('activar', 'roles', $rol->id, 'Rol activado');

            return response()->json([
                'success' => true,
                'data' => $rol,
                'message' => 'Rol activado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al activar rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Desactivar rol
     */
    public function desactivar($id): JsonResponse
    {
        try {
            $rol = Rol::find($id);

            if (!$rol) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rol no encontrado'
                ], 404);
            }

            $rol->desactivar();

            // Log de desactivación
            Log::crear('desactivar', 'roles', $rol->id, 'Rol desactivado');

            return response()->json([
                'success' => true,
                'data' => $rol,
                'message' => 'Rol desactivado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agregar permiso a rol
     */
    public function agregarPermiso(Request $request, $id): JsonResponse
    {
        try {
            $rol = Rol::find($id);

            if (!$rol) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rol no encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'permiso' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permiso requerido',
                    'errors' => $validator->errors()
                ], 422);
            }

            $rol->agregarPermiso($request->permiso);

            // Log de agregar permiso
            Log::crear('agregar_permiso', 'roles', $rol->id, 'Permiso agregado: ' . $request->permiso);

            return response()->json([
                'success' => true,
                'data' => $rol,
                'message' => 'Permiso agregado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar permiso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Quitar permiso de rol
     */
    public function quitarPermiso(Request $request, $id): JsonResponse
    {
        try {
            $rol = Rol::find($id);

            if (!$rol) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rol no encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'permiso' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permiso requerido',
                    'errors' => $validator->errors()
                ], 422);
            }

            $rol->quitarPermiso($request->permiso);

            // Log de quitar permiso
            Log::crear('quitar_permiso', 'roles', $rol->id, 'Permiso quitado: ' . $request->permiso);

            return response()->json([
                'success' => true,
                'data' => $rol,
                'message' => 'Permiso quitado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al quitar permiso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar roles
     */
    public function buscar(Request $request): JsonResponse
    {
        try {
            $query = Rol::with(['usuarios']);

            if ($request->has('termino')) {
                $termino = $request->termino;
                $query->where(function($q) use ($termino) {
                    $q->where('nom', 'like', '%' . $termino . '%')
                      ->orWhere('des', 'like', '%' . $termino . '%');
                });
            }

            $roles = $query->limit(10)->get();

            return response()->json([
                'success' => true,
                'data' => $roles,
                'message' => 'Búsqueda completada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la búsqueda: ' . $e->getMessage()
            ], 500);
        }
    }
}
