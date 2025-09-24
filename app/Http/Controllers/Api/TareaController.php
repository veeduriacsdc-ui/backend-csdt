<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tarea;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TareaController extends Controller
{
    /**
     * Obtener lista de tareas
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Tarea::with(['veeduria', 'asignadoPor', 'asignadoA']);

            // Filtros
            if ($request->has('est')) {
                $query->where('est', $request->est);
            }
            if ($request->has('pri')) {
                $query->where('pri', $request->pri);
            }
            if ($request->has('vee_id')) {
                $query->where('vee_id', $request->vee_id);
            }
            if ($request->has('asig_a')) {
                $query->where('asig_a', $request->asig_a);
            }
            if ($request->has('asig_por')) {
                $query->where('asig_por', $request->asig_por);
            }

            // Filtros especiales
            if ($request->has('vencidas')) {
                $query->vencidas();
            }
            if ($request->has('por_vencer')) {
                $dias = $request->get('dias', 3);
                $query->porVencer($dias);
            }

            // Búsqueda
            if ($request->has('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('tit', 'like', '%' . $buscar . '%')
                      ->orWhere('des', 'like', '%' . $buscar . '%')
                      ->orWhere('not', 'like', '%' . $buscar . '%');
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'fec_ven');
            $direccion = $request->get('direccion', 'asc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $porPagina = $request->get('por_pagina', 15);
            $tareas = $query->paginate($porPagina);

            return response()->json([
                'success' => true,
                'data' => $tareas,
                'message' => 'Tareas obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tareas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tarea por ID
     */
    public function show($id): JsonResponse
    {
        try {
            $tarea = Tarea::with(['veeduria', 'asignadoPor', 'asignadoA', 'archivos'])->find($id);

            if (!$tarea) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarea no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $tarea,
                'message' => 'Tarea obtenida exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nueva tarea
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), Tarea::reglas(), Tarea::mensajes());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $tarea = Tarea::create($request->all());

            // Log de creación
            Log::logCreacion('tareas', $tarea->id, $tarea->toArray());

            return response()->json([
                'success' => true,
                'data' => $tarea,
                'message' => 'Tarea creada exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar tarea
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $tarea = Tarea::find($id);

            if (!$tarea) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarea no encontrada'
                ], 404);
            }

            $validator = Validator::make($request->all(), Tarea::reglas($id), Tarea::mensajes());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $datosAnteriores = $tarea->toArray();
            $tarea->update($request->all());

            // Log de actualización
            Log::logActualizacion('tareas', $tarea->id, $datosAnteriores, $tarea->toArray());

            return response()->json([
                'success' => true,
                'data' => $tarea,
                'message' => 'Tarea actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar tarea (soft delete)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $tarea = Tarea::find($id);

            if (!$tarea) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarea no encontrada'
                ], 404);
            }

            $datosAnteriores = $tarea->toArray();
            $tarea->delete();

            // Log de eliminación
            Log::logEliminacion('tareas', $tarea->id, $datosAnteriores);

            return response()->json([
                'success' => true,
                'message' => 'Tarea eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurar tarea
     */
    public function restore($id): JsonResponse
    {
        try {
            $tarea = Tarea::withTrashed()->find($id);

            if (!$tarea) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarea no encontrada'
                ], 404);
            }

            $tarea->restore();

            // Log de restauración
            Log::logRestauracion('tareas', $tarea->id);

            return response()->json([
                'success' => true,
                'data' => $tarea,
                'message' => 'Tarea restaurada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Iniciar tarea
     */
    public function iniciar($id): JsonResponse
    {
        try {
            $tarea = Tarea::find($id);

            if (!$tarea) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarea no encontrada'
                ], 404);
            }

            $tarea->iniciar();

            // Log de inicio
            Log::crear('iniciar', 'tareas', $tarea->id, 'Tarea iniciada');

            return response()->json([
                'success' => true,
                'data' => $tarea,
                'message' => 'Tarea iniciada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Completar tarea
     */
    public function completar($id): JsonResponse
    {
        try {
            $tarea = Tarea::find($id);

            if (!$tarea) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarea no encontrada'
                ], 404);
            }

            $tarea->completar();

            // Log de completación
            Log::crear('completar', 'tareas', $tarea->id, 'Tarea completada');

            return response()->json([
                'success' => true,
                'data' => $tarea,
                'message' => 'Tarea completada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al completar tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar tarea
     */
    public function cancelar(Request $request, $id): JsonResponse
    {
        try {
            $tarea = Tarea::find($id);

            if (!$tarea) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarea no encontrada'
                ], 404);
            }

            $tarea->cancelar($request->motivo);

            // Log de cancelación
            Log::crear('cancelar', 'tareas', $tarea->id, 'Tarea cancelada: ' . $request->motivo);

            return response()->json([
                'success' => true,
                'data' => $tarea,
                'message' => 'Tarea cancelada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Suspender tarea
     */
    public function suspender(Request $request, $id): JsonResponse
    {
        try {
            $tarea = Tarea::find($id);

            if (!$tarea) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarea no encontrada'
                ], 404);
            }

            $tarea->suspender($request->motivo);

            // Log de suspensión
            Log::crear('suspender', 'tareas', $tarea->id, 'Tarea suspendida: ' . $request->motivo);

            return response()->json([
                'success' => true,
                'data' => $tarea,
                'message' => 'Tarea suspendida exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al suspender tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reanudar tarea
     */
    public function reanudar($id): JsonResponse
    {
        try {
            $tarea = Tarea::find($id);

            if (!$tarea) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarea no encontrada'
                ], 404);
            }

            $tarea->reanudar();

            // Log de reanudación
            Log::crear('reanudar', 'tareas', $tarea->id, 'Tarea reanudada');

            return response()->json([
                'success' => true,
                'data' => $tarea,
                'message' => 'Tarea reanudada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reanudar tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asignar tarea
     */
    public function asignar(Request $request, $id): JsonResponse
    {
        try {
            $tarea = Tarea::find($id);

            if (!$tarea) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarea no encontrada'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'asig_a' => 'required|exists:usuarios,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario inválido',
                    'errors' => $validator->errors()
                ], 422);
            }

            $tarea->asignar($request->asig_a, auth()->id());

            // Log de asignación
            Log::crear('asignar', 'tareas', $tarea->id, 'Tarea asignada a usuario');

            return response()->json([
                'success' => true,
                'data' => $tarea,
                'message' => 'Tarea asignada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de tareas
     */
    public function estadisticas(Request $request): JsonResponse
    {
        try {
            $query = Tarea::query();

            // Filtros por veeduría
            if ($request->has('vee_id')) {
                $query->where('vee_id', $request->vee_id);
            }

            // Filtros por usuario asignado
            if ($request->has('asig_a')) {
                $query->where('asig_a', $request->asig_a);
            }

            $estadisticas = [
                'total_tareas' => $query->count(),
                'tareas_pendientes' => $query->where('est', 'pen')->count(),
                'tareas_en_proceso' => $query->where('est', 'pro')->count(),
                'tareas_completadas' => $query->where('est', 'com')->count(),
                'tareas_canceladas' => $query->where('est', 'can')->count(),
                'tareas_suspendidas' => $query->where('est', 'sus')->count(),
                'tareas_vencidas' => $query->vencidas()->count(),
                'tareas_por_vencer' => $query->porVencer(3)->count(),
                'por_prioridad' => $query->selectRaw('pri, COUNT(*) as total')
                    ->groupBy('pri')
                    ->get(),
                'por_estado' => $query->selectRaw('est, COUNT(*) as total')
                    ->groupBy('est')
                    ->get(),
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
     * Buscar tareas
     */
    public function buscar(Request $request): JsonResponse
    {
        try {
            $query = Tarea::with(['veeduria', 'asignadoA']);

            if ($request->has('termino')) {
                $termino = $request->termino;
                $query->where(function($q) use ($termino) {
                    $q->where('tit', 'like', '%' . $termino . '%')
                      ->orWhere('des', 'like', '%' . $termino . '%')
                      ->orWhere('not', 'like', '%' . $termino . '%');
                });
            }

            $tareas = $query->limit(10)->get();

            return response()->json([
                'success' => true,
                'data' => $tareas,
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
