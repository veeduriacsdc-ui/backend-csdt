<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Veeduria;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class VeeduriaController extends Controller
{
    /**
     * Obtener lista de veedurías
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Veeduria::with(['usuario', 'operador']);

            // Filtros
            if ($request->has('est')) {
                $query->where('est', $request->est);
            }
            if ($request->has('tip')) {
                $query->where('tip', $request->tip);
            }
            if ($request->has('pri')) {
                $query->where('pri', $request->pri);
            }
            if ($request->has('cat')) {
                $query->where('cat', $request->cat);
            }
            if ($request->has('usu_id')) {
                $query->where('usu_id', $request->usu_id);
            }
            if ($request->has('ope_id')) {
                $query->where('ope_id', $request->ope_id);
            }

            // Búsqueda
            if ($request->has('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('tit', 'like', '%' . $buscar . '%')
                      ->orWhere('des', 'like', '%' . $buscar . '%')
                      ->orWhere('num_rad', 'like', '%' . $buscar . '%')
                      ->orWhere('ubi', 'like', '%' . $buscar . '%');
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'fec_reg');
            $direccion = $request->get('direccion', 'desc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $porPagina = $request->get('por_pagina', 15);
            $veedurias = $query->paginate($porPagina);

            return response()->json([
                'success' => true,
                'data' => $veedurias,
                'message' => 'Veedurías obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener veedurías: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener veeduría por ID
     */
    public function show($id): JsonResponse
    {
        try {
            $veeduria = Veeduria::with(['usuario', 'operador', 'donaciones', 'tareas', 'archivos'])->find($id);

            if (!$veeduria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veeduría no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $veeduria,
                'message' => 'Veeduría obtenida exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener veeduría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nueva veeduría
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), Veeduria::reglas(), Veeduria::mensajes());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $veeduria = Veeduria::create($request->all());

            // Log de creación
            Log::logCreacion('veedurias', $veeduria->id, $veeduria->toArray());

            return response()->json([
                'success' => true,
                'data' => $veeduria,
                'message' => 'Veeduría creada exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear veeduría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar veeduría
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $veeduria = Veeduria::find($id);

            if (!$veeduria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veeduría no encontrada'
                ], 404);
            }

            $validator = Validator::make($request->all(), Veeduria::reglas($id), Veeduria::mensajes());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $datosAnteriores = $veeduria->toArray();
            $veeduria->update($request->all());

            // Log de actualización
            Log::logActualizacion('veedurias', $veeduria->id, $datosAnteriores, $veeduria->toArray());

            return response()->json([
                'success' => true,
                'data' => $veeduria,
                'message' => 'Veeduría actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar veeduría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar veeduría (soft delete)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $veeduria = Veeduria::find($id);

            if (!$veeduria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veeduría no encontrada'
                ], 404);
            }

            $datosAnteriores = $veeduria->toArray();
            $veeduria->delete();

            // Log de eliminación
            Log::logEliminacion('veedurias', $veeduria->id, $datosAnteriores);

            return response()->json([
                'success' => true,
                'message' => 'Veeduría eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar veeduría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurar veeduría
     */
    public function restore($id): JsonResponse
    {
        try {
            $veeduria = Veeduria::withTrashed()->find($id);

            if (!$veeduria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veeduría no encontrada'
                ], 404);
            }

            $veeduria->restore();

            // Log de restauración
            Log::logRestauracion('veedurias', $veeduria->id);

            return response()->json([
                'success' => true,
                'data' => $veeduria,
                'message' => 'Veeduría restaurada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar veeduría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Radicar veeduría
     */
    public function radicar($id): JsonResponse
    {
        try {
            $veeduria = Veeduria::find($id);

            if (!$veeduria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veeduría no encontrada'
                ], 404);
            }

            $veeduria->radicar();

            // Log de radicación
            Log::crear('radicar', 'veedurias', $veeduria->id, 'Veeduría radicada');

            return response()->json([
                'success' => true,
                'data' => $veeduria,
                'message' => 'Veeduría radicada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al radicar veeduría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cerrar veeduría
     */
    public function cerrar($id): JsonResponse
    {
        try {
            $veeduria = Veeduria::find($id);

            if (!$veeduria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veeduría no encontrada'
                ], 404);
            }

            $veeduria->cerrar();

            // Log de cierre
            Log::crear('cerrar', 'veedurias', $veeduria->id, 'Veeduría cerrada');

            return response()->json([
                'success' => true,
                'data' => $veeduria,
                'message' => 'Veeduría cerrada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar veeduría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar veeduría
     */
    public function cancelar($id): JsonResponse
    {
        try {
            $veeduria = Veeduria::find($id);

            if (!$veeduria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veeduría no encontrada'
                ], 404);
            }

            $veeduria->cancelar();

            // Log de cancelación
            Log::crear('cancelar', 'veedurias', $veeduria->id, 'Veeduría cancelada');

            return response()->json([
                'success' => true,
                'data' => $veeduria,
                'message' => 'Veeduría cancelada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar veeduría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asignar operador a veeduría
     */
    public function asignarOperador(Request $request, $id): JsonResponse
    {
        try {
            $veeduria = Veeduria::find($id);

            if (!$veeduria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veeduría no encontrada'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'ope_id' => 'required|exists:usuarios,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operador inválido',
                    'errors' => $validator->errors()
                ], 422);
            }

            $veeduria->asignarOperador($request->ope_id);

            // Log de asignación
            Log::crear('asignar', 'veedurias', $veeduria->id, 'Operador asignado a veeduría');

            return response()->json([
                'success' => true,
                'data' => $veeduria,
                'message' => 'Operador asignado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar operador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de veeduría
     */
    public function estadisticas($id): JsonResponse
    {
        try {
            $veeduria = Veeduria::find($id);

            if (!$veeduria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Veeduría no encontrada'
                ], 404);
            }

            $estadisticas = [
                'total_tareas' => $veeduria->tareas()->count(),
                'tareas_completadas' => $veeduria->tareas()->where('est', 'com')->count(),
                'tareas_pendientes' => $veeduria->tareas()->where('est', 'pen')->count(),
                'total_donaciones' => $veeduria->donaciones()->count(),
                'monto_total_donado' => $veeduria->donaciones()->where('est', 'con')->sum('mon'),
                'total_archivos' => $veeduria->archivos()->count(),
                'dias_transcurridos' => $veeduria->fec_reg->diffInDays(now()),
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
     * Buscar veedurías
     */
    public function buscar(Request $request): JsonResponse
    {
        try {
            $query = Veeduria::with(['usuario', 'operador']);

            if ($request->has('termino')) {
                $termino = $request->termino;
                $query->where(function($q) use ($termino) {
                    $q->where('tit', 'like', '%' . $termino . '%')
                      ->orWhere('des', 'like', '%' . $termino . '%')
                      ->orWhere('num_rad', 'like', '%' . $termino . '%');
                });
            }

            $veedurias = $query->limit(10)->get();

            return response()->json([
                'success' => true,
                'data' => $veedurias,
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
