<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Operador;
use App\Models\Tarea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TareaControlador extends Controller
{
    /**
     * Obtener lista paginada de tareas con filtros
     */
    public function ObtenerLista(Request $request): JsonResponse
    {
        try {
            $query = Tarea::with(['veeduria', 'operador']);

            // Filtros
            if ($request->filled('estado')) {
                $query->where('Estado', $request->estado);
            }

            if ($request->filled('prioridad')) {
                $query->where('Prioridad', $request->prioridad);
            }

            if ($request->filled('veeduria_id')) {
                $query->where('VeeduriaId', $request->veeduria_id);
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
                    $q->where('Titulo', 'like', '%'.$buscar.'%')
                        ->orWhere('Descripcion', 'like', '%'.$buscar.'%');
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'FechaVencimiento');
            $direccion = $request->get('direccion', 'asc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $tareas = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $tareas->items(),
                'pagination' => [
                    'current_page' => $tareas->currentPage(),
                    'last_page' => $tareas->lastPage(),
                    'per_page' => $tareas->perPage(),
                    'total' => $tareas->total(),
                ],
                'message' => 'Tareas obtenidas exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tareas: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener una tarea específica
     */
    public function ObtenerPorId($id): JsonResponse
    {
        try {
            $tarea = Tarea::with(['veeduria', 'operador', 'documentos'])->find($id);

            if (! $tarea) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarea no encontrada',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $tarea,
                'message' => 'Tarea obtenida exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tarea: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear una nueva tarea
     */
    public function Crear(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'Titulo' => 'required|string|max:255',
                'Descripcion' => 'required|string|max:1000',
                'VeeduriaId' => 'required|exists:Veedurias,IdVeeduria',
                'OperadorId' => 'required|exists:Operadores,IdOperador',
                'Prioridad' => 'required|in:baja,media,alta,urgente',
                'Estado' => 'required|in:pendiente,en_proceso,completada,cancelada',
                'FechaVencimiento' => 'required|date|after:today',
                'TiempoEstimado' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $datos = $request->all();
            $datos['FechaCreacion'] = now();
            $datos['Codigo'] = $this->GenerarCodigoTarea();

            $tarea = Tarea::create($datos);

            return response()->json([
                'success' => true,
                'data' => $tarea,
                'message' => 'Tarea creada exitosamente',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear tarea: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar una tarea existente
     */
    public function Actualizar(Request $request, $id): JsonResponse
    {
        try {
            $tarea = Tarea::find($id);

            if (! $tarea) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarea no encontrada',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'Titulo' => 'sometimes|required|string|max:255',
                'Descripcion' => 'sometimes|required|string|max:1000',
                'Prioridad' => 'sometimes|required|in:baja,media,alta,urgente',
                'Estado' => 'sometimes|required|in:pendiente,en_proceso,completada,cancelada',
                'FechaVencimiento' => 'sometimes|required|date|after:today',
                'TiempoEstimado' => 'sometimes|required|integer|min:1',
                'Comentarios' => 'sometimes|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $tarea->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $tarea,
                'message' => 'Tarea actualizada exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar tarea: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar una tarea
     */
    public function Eliminar($id): JsonResponse
    {
        try {
            $tarea = Tarea::find($id);

            if (! $tarea) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarea no encontrada',
                ], 404);
            }

            // Verificar si la tarea está en proceso o completada
            if (in_array($tarea->Estado, ['en_proceso', 'completada'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar una tarea en proceso o completada',
                ], 400);
            }

            $tarea->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tarea eliminada exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar tarea: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Asignar operador a tarea
     */
    public function AsignarOperador(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'OperadorId' => 'required|exists:Operadores,IdOperador',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operador no válido',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $tarea = Tarea::find($id);

            if (! $tarea) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarea no encontrada',
                ], 404);
            }

            $tarea->update([
                'OperadorId' => $request->OperadorId,
                'Estado' => 'en_proceso',
                'FechaAsignacion' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $tarea,
                'message' => 'Operador asignado exitosamente a la tarea',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar operador: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cambiar estado de tarea
     */
    public function CambiarEstado(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'Estado' => 'required|in:pendiente,en_proceso,completada,cancelada',
                'Comentarios' => 'sometimes|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estado no válido',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $tarea = Tarea::find($id);

            if (! $tarea) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarea no encontrada',
                ], 404);
            }

            $estadoAnterior = $tarea->Estado;
            $datosActualizacion = [
                'Estado' => $request->Estado,
                'FechaCambioEstado' => now(),
            ];

            if ($request->Estado === 'completada') {
                $datosActualizacion['FechaCompletado'] = now();
                $datosActualizacion['TiempoReal'] = $tarea->FechaCreacion->diffInHours(now());
            }

            if ($request->Comentarios) {
                $datosActualizacion['Comentarios'] = $request->Comentarios;
            }

            $tarea->update($datosActualizacion);

            return response()->json([
                'success' => true,
                'data' => $tarea,
                'message' => "Estado de la tarea cambiado de '{$estadoAnterior}' a '{$request->Estado}' exitosamente",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener tareas por veeduría
     */
    public function ObtenerPorVeeduria(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'veeduria_id' => 'required|exists:Veedurias,IdVeeduria',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de veeduría no válido',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $tareas = Tarea::with(['operador'])
                ->where('VeeduriaId', $request->veeduria_id)
                ->orderBy('FechaVencimiento', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tareas,
                'message' => 'Tareas de la veeduría obtenidas exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tareas: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de tareas
     */
    public function ObtenerEstadisticas(): JsonResponse
    {
        try {
            $estadisticas = [
                'total' => Tarea::count(),
                'por_estado' => Tarea::selectRaw('Estado, COUNT(*) as total')
                    ->groupBy('Estado')
                    ->get(),
                'por_prioridad' => Tarea::selectRaw('Prioridad, COUNT(*) as total')
                    ->groupBy('Prioridad')
                    ->get(),
                'por_mes' => Tarea::selectRaw('MONTH(FechaCreacion) as mes, COUNT(*) as total')
                    ->whereYear('FechaCreacion', date('Y'))
                    ->groupBy('mes')
                    ->orderBy('mes')
                    ->get(),
                'atrasadas' => Tarea::where('FechaVencimiento', '<', now())
                    ->where('Estado', '!=', 'completada')
                    ->count(),
                'promedio_completado' => Tarea::where('Estado', 'completada')
                    ->whereNotNull('TiempoReal')
                    ->avg('TiempoReal'),
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas obtenidas exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generar código único para tarea
     */
    private function GenerarCodigoTarea(): string
    {
        $prefijo = 'TAR';
        $anio = date('Y');
        $ultimoCodigo = Tarea::whereYear('FechaCreacion', $anio)
            ->orderBy('Codigo', 'desc')
            ->first();

        if ($ultimoCodigo) {
            $numero = (int) substr($ultimoCodigo->Codigo, -4) + 1;
        } else {
            $numero = 1;
        }

        return $prefijo.$anio.str_pad($numero, 4, '0', STR_PAD_LEFT);
    }
}
