<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UsuarioController extends Controller
{
    /**
     * Obtener lista de usuarios
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Usuario::query();

            // Filtros
            if ($request->has('rol')) {
                $query->where('rol', $request->rol);
            }
            if ($request->has('est')) {
                $query->where('est', $request->est);
            }
            if ($request->has('ciu')) {
                $query->where('ciu', 'like', '%' . $request->ciu . '%');
            }
            if ($request->has('dep')) {
                $query->where('dep', 'like', '%' . $request->dep . '%');
            }

            // Búsqueda
            if ($request->has('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('nom', 'like', '%' . $buscar . '%')
                      ->orWhere('ape', 'like', '%' . $buscar . '%')
                      ->orWhere('cor', 'like', '%' . $buscar . '%')
                      ->orWhere('doc', 'like', '%' . $buscar . '%');
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'id');
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
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener usuario por ID
     */
    public function show($id): JsonResponse
    {
        try {
            $usuario = Usuario::with(['veedurias', 'donaciones', 'archivos', 'roles'])->find($id);

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

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
     * Crear nuevo usuario
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), Usuario::reglas(), Usuario::mensajes());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $datos = $request->all();
            $datos['con'] = Hash::make($datos['con']);

            $usuario = Usuario::create($datos);

            // Log de creación
            Log::logCreacion('usuarios', $usuario->id, $usuario->toArray());

            return response()->json([
                'success' => true,
                'data' => $usuario,
                'message' => 'Usuario creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar usuario
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $usuario = Usuario::find($id);

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), Usuario::reglas($id), Usuario::mensajes());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $datosAnteriores = $usuario->toArray();
            $datos = $request->all();

            // Si se proporciona contraseña, hashearla
            if (isset($datos['con'])) {
                $datos['con'] = Hash::make($datos['con']);
            }

            $usuario->update($datos);

            // Log de actualización
            Log::logActualizacion('usuarios', $usuario->id, $datosAnteriores, $usuario->toArray());

            return response()->json([
                'success' => true,
                'data' => $usuario,
                'message' => 'Usuario actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar usuario (soft delete)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $usuario = Usuario::find($id);

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $datosAnteriores = $usuario->toArray();
            $usuario->delete();

            // Log de eliminación
            Log::logEliminacion('usuarios', $usuario->id, $datosAnteriores);

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurar usuario
     */
    public function restore($id): JsonResponse
    {
        try {
            $usuario = Usuario::withTrashed()->find($id);

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $usuario->restore();

            // Log de restauración
            Log::logRestauracion('usuarios', $usuario->id);

            return response()->json([
                'success' => true,
                'data' => $usuario,
                'message' => 'Usuario restaurado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar estado de usuario
     */
    public function cambiarEstado(Request $request, $id): JsonResponse
    {
        try {
            $usuario = Usuario::find($id);

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'est' => 'required|in:act,ina,sus,pen'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estado inválido',
                    'errors' => $validator->errors()
                ], 422);
            }

            $estadoAnterior = $usuario->est;
            $usuario->cambiarEstado($request->est, $request->motivo);

            return response()->json([
                'success' => true,
                'data' => $usuario,
                'message' => 'Estado cambiado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar correo de usuario
     */
    public function verificarCorreo($id): JsonResponse
    {
        try {
            $usuario = Usuario::find($id);

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $usuario->verificarCorreo();

            // Log de verificación
            Log::crear('verificar_correo', 'usuarios', $usuario->id, 'Correo verificado');

            return response()->json([
                'success' => true,
                'data' => $usuario,
                'message' => 'Correo verificado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar correo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de usuario
     */
    public function estadisticas($id): JsonResponse
    {
        try {
            $usuario = Usuario::find($id);

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $estadisticas = [
                'total_veedurias' => $usuario->veedurias()->count(),
                'veedurias_activas' => $usuario->veedurias()->where('est', '!=', 'cer')->count(),
                'total_donaciones' => $usuario->donaciones()->count(),
                'monto_total_donado' => $usuario->donaciones()->where('est', 'con')->sum('mon'),
                'total_archivos' => $usuario->archivos()->count(),
                'ultimo_acceso' => $usuario->ult_acc,
                'fecha_registro' => $usuario->created_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar usuarios
     */
    public function buscar(Request $request): JsonResponse
    {
        try {
            $query = Usuario::query();

            if ($request->has('termino')) {
                $termino = $request->termino;
                $query->where(function($q) use ($termino) {
                    $q->where('nom', 'like', '%' . $termino . '%')
                      ->orWhere('ape', 'like', '%' . $termino . '%')
                      ->orWhere('cor', 'like', '%' . $termino . '%')
                      ->orWhere('doc', 'like', '%' . $termino . '%');
                });
            }

            $usuarios = $query->limit(10)->get();

            return response()->json([
                'success' => true,
                'data' => $usuarios,
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
