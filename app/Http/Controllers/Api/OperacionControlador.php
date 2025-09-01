<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Operacion;
use App\Models\Operador;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OperacionControlador extends Controller
{
    /**
     * Obtener lista paginada de operaciones con filtros
     */
    public function ObtenerLista(Request $request): JsonResponse
    {
        try {
            $query = Operacion::with(['cliente', 'operador', 'tareas']);

            // Filtros
            if ($request->filled('estado')) {
                $query->where('Estado', $request->estado);
            }

            if ($request->filled('tipo')) {
                $query->where('Tipo', $request->tipo);
            }

            if ($request->filled('cliente_id')) {
                $query->where('ClienteId', $request->cliente_id);
            }

            if ($request->filled('operador_id')) {
                $query->where('OperadorId', $request->operador_id);
            }

            if ($request->filled('fecha_inicio')) {
                $query->where('FechaCreacion', '>=', $request->fecha_inicio);
            }

            if ($request->filled('fecha_fin')) {
                $query->where('FechaCreacion', '<=', $request->fecha_fin);
            }

            if ($request->filled('buscar')) {
                $buscar = $request->buscar;
                $query->where(function ($q) use ($buscar) {
                    $q->where('Titulo', 'like', '%' . $buscar . '%')
                      ->orWhere('Descripcion', 'like', '%' . $buscar . '%')
                      ->orWhere('Ubicacion', 'like', '%' . $buscar . '%');
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'FechaCreacion');
            $direccion = $request->get('direccion', 'desc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $operaciones = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $operaciones->items(),
                'pagination' => [
                    'current_page' => $operaciones->currentPage(),
                    'last_page' => $operaciones->lastPage(),
                    'per_page' => $operaciones->perPage(),
                    'total' => $operaciones->total(),
                ],
                'message' => 'Operaciones obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener operaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una operación específica
     */
    public function ObtenerPorId($id): JsonResponse
    {
        try {
            $operacion = Operacion::with(['cliente', 'operador', 'tareas', 'documentos'])->find($id);

            if (!$operacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operación no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $operacion,
                'message' => 'Operación obtenida exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener operación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear una nueva operación
     */
    public function Crear(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'titulo' => 'required|string|max:255',
                'descripcion' => 'required|string',
                'tipo' => 'required|string|max:100',
                'ubicacion' => 'required|string|max:255',
                'cliente_id' => 'required|exists:clientes,id',
                'operador_id' => 'required|exists:operadores,id',
                'prioridad' => 'required|in:baja,media,alta',
                'fecha_limite' => 'required|date|after:today'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $operacion = Operacion::create([
                'Titulo' => $request->titulo,
                'Descripcion' => $request->descripcion,
                'Tipo' => $request->tipo,
                'Ubicacion' => $request->ubicacion,
                'ClienteId' => $request->cliente_id,
                'OperadorId' => $request->operador_id,
                'Prioridad' => $request->prioridad,
                'Estado' => 'pendiente',
                'FechaCreacion' => now(),
                'FechaLimite' => $request->fecha_limite
            ]);

            return response()->json([
                'success' => true,
                'data' => $operacion,
                'message' => 'Operación creada exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear operación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar una operación existente
     */
    public function Actualizar(Request $request, $id): JsonResponse
    {
        try {
            $operacion = Operacion::find($id);

            if (!$operacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operación no encontrada'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'titulo' => 'sometimes|string|max:255',
                'descripcion' => 'sometimes|string',
                'tipo' => 'sometimes|string|max:100',
                'ubicacion' => 'sometimes|string|max:255',
                'prioridad' => 'sometimes|in:baja,media,alta',
                'estado' => 'sometimes|in:pendiente,en_proceso,completada,cancelada',
                'fecha_limite' => 'sometimes|date|after:today'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $operacion->update($request->only([
                'titulo', 'descripcion', 'tipo', 'ubicacion', 
                'prioridad', 'estado', 'fecha_limite'
            ]));

            return response()->json([
                'success' => true,
                'data' => $operacion,
                'message' => 'Operación actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar operación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una operación
     */
    public function Eliminar($id): JsonResponse
    {
        try {
            $operacion = Operacion::find($id);

            if (!$operacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operación no encontrada'
                ], 404);
            }

            // Verificar que no tenga tareas activas
            if ($operacion->tareas()->where('estado', '!=', 'completada')->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar una operación con tareas activas'
                ], 400);
            }

            $operacion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Operación eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar operación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar estado de una operación
     */
    public function CambiarEstado(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'estado' => 'required|in:pendiente,en_proceso,completada,cancelada'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estado no válido',
                    'errors' => $validator->errors()
                ], 422);
            }

            $operacion = Operacion::find($id);

            if (!$operacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operación no encontrada'
                ], 404);
            }

            $operacion->update([
                'Estado' => $request->estado,
                'FechaActualizacion' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => $operacion,
                'message' => 'Estado de operación actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de operaciones
     */
    public function ObtenerEstadisticas(): JsonResponse
    {
        try {
            $estadisticas = [
                'total' => Operacion::count(),
                'pendientes' => Operacion::where('Estado', 'pendiente')->count(),
                'en_proceso' => Operacion::where('Estado', 'en_proceso')->count(),
                'completadas' => Operacion::where('Estado', 'completada')->count(),
                'canceladas' => Operacion::where('Estado', 'cancelada')->count(),
                'por_prioridad' => [
                    'baja' => Operacion::where('Prioridad', 'baja')->count(),
                    'media' => Operacion::where('Prioridad', 'media')->count(),
                    'alta' => Operacion::where('Prioridad', 'alta')->count()
                ],
                'por_tipo' => Operacion::selectRaw('Tipo, count(*) as total')
                    ->groupBy('Tipo')
                    ->get()
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
     * Asignar operador a una operación
     */
    public function AsignarOperador(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'operador_id' => 'required|exists:operadores,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operador no válido',
                    'errors' => $validator->errors()
                ], 422);
            }

            $operacion = Operacion::find($id);

            if (!$operacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operación no encontrada'
                ], 404);
            }

            $operacion->update([
                'OperadorId' => $request->operador_id,
                'FechaActualizacion' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => $operacion,
                'message' => 'Operador asignado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar operador: ' . $e->getMessage()
            ], 500);
        }
    }
}
