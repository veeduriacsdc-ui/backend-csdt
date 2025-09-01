<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PQRSFD;
use App\Models\MovimientoPQRSFD;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PQRSFDController extends Controller
{
    /**
     * Obtener lista paginada de PQRSFDs con filtros
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = PQRSFD::with(['cliente', 'operadorAsignado', 'documentos']);

            // Filtros
            if ($request->filled('estado')) {
                $query->where('Estado', $request->estado);
            }

            if ($request->filled('tipo')) {
                $query->where('TipoPQRSFD', $request->tipo);
            }

            if ($request->filled('operador')) {
                $query->where('IdOperadorAsignado', $request->operador);
            }

            if ($request->filled('cliente')) {
                $query->where('IdCliente', $request->cliente);
            }

            if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
                $query->whereBetween('FechaRegistro', [$request->fecha_inicio, $request->fecha_fin]);
            }

            if ($request->filled('buscar')) {
                $buscar = $request->buscar;
                $query->where(function ($q) use ($buscar) {
                    $q->where('Asunto', 'like', '%' . $buscar . '%')
                      ->orWhere('NumeroRadicacion', 'like', '%' . $buscar . '%')
                      ->orWhereHas('cliente', function ($q2) use ($buscar) {
                          $q2->where('Nombres', 'like', '%' . $buscar . '%')
                              ->orWhere('Apellidos', 'like', '%' . $buscar . '%');
                      });
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'FechaRegistro');
            $direccion = $request->get('direccion', 'desc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $pqrsfds = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $pqrsfds->items(),
                'pagination' => [
                    'current_page' => $pqrsfds->currentPage(),
                    'last_page' => $pqrsfds->lastPage(),
                    'per_page' => $pqrsfds->perPage(),
                    'total' => $pqrsfds->total(),
                ],
                'message' => 'PQRSFDs obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener PQRSFDs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un PQRSFD específico
     */
    public function show($id): JsonResponse
    {
        try {
            $pqrsfd = PQRSFD::with([
                'cliente', 
                'operadorAsignado', 
                'documentos', 
                'actividades.operadorResponsable',
                'movimientos.operador'
            ])->find($id);

            if (!$pqrsfd) {
                return response()->json([
                    'success' => false,
                    'message' => 'PQRSFD no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $pqrsfd,
                'message' => 'PQRSFD obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener PQRSFD: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo PQRSFD
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), PQRSFD::rules());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $data = $request->all();
            $data['FechaRegistro'] = now();
            $data['FechaUltimaActualizacion'] = now();

            $pqrsfd = PQRSFD::create($data);

            // Crear movimiento de creación
            MovimientoPQRSFD::crearMovimiento(
                $pqrsfd->IdPQRSFD,
                $request->user()->IdOperador ?? 1, // Usuario autenticado o default
                'Creacion',
                'PQRSFD creado por el cliente',
                null,
                'Pendiente'
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $pqrsfd->load(['cliente', 'operadorAsignado']),
                'message' => 'PQRSFD creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear PQRSFD: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un PQRSFD existente
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $pqrsfd = PQRSFD::find($id);

            if (!$pqrsfd) {
                return response()->json([
                    'success' => false,
                    'message' => 'PQRSFD no encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), PQRSFD::rules($id));

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $estadoAnterior = $pqrsfd->Estado;
            $data = $request->all();
            $data['FechaUltimaActualizacion'] = now();

            $pqrsfd->update($data);

            // Crear movimiento si cambió el estado
            if ($estadoAnterior !== $pqrsfd->Estado) {
                MovimientoPQRSFD::crearCambioEstado(
                    $pqrsfd->IdPQRSFD,
                    $request->user()->IdOperador ?? 1,
                    $estadoAnterior,
                    $pqrsfd->Estado,
                    $request->comentarios ?? null
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $pqrsfd->fresh(['cliente', 'operadorAsignado']),
                'message' => 'PQRSFD actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar PQRSFD: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un PQRSFD (soft delete)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $pqrsfd = PQRSFD::find($id);

            if (!$pqrsfd) {
                return response()->json([
                    'success' => false,
                    'message' => 'PQRSFD no encontrado'
                ], 404);
            }

            // Solo se pueden eliminar PQRSFDs cancelados o cerrados
            if (!in_array($pqrsfd->Estado, ['Cancelado', 'Cerrado'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden eliminar PQRSFDs cancelados o cerrados'
                ], 422);
            }

            $pqrsfd->delete();

            return response()->json([
                'success' => true,
                'message' => 'PQRSFD eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar PQRSFD: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asignar operador a un PQRSFD
     */
    public function asignarOperador(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'IdOperadorAsignado' => 'required|exists:Operadores,IdOperador',
                'comentarios' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $pqrsfd = PQRSFD::find($id);

            if (!$pqrsfd) {
                return response()->json([
                    'success' => false,
                    'message' => 'PQRSFD no encontrado'
                ], 404);
            }

            if ($pqrsfd->Estado !== 'Pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden asignar PQRSFDs pendientes'
                ], 422);
            }

            DB::beginTransaction();

            $pqrsfd->asignarOperador($request->IdOperadorAsignado);

            // Crear movimiento de asignación
            MovimientoPQRSFD::crearAsignacion(
                $pqrsfd->IdPQRSFD,
                $request->user()->IdOperador ?? 1,
                $request->IdOperadorAsignado,
                $request->comentarios
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $pqrsfd->fresh(['cliente', 'operadorAsignado']),
                'message' => 'Operador asignado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar operador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Radicar un PQRSFD
     */
    public function radicar(Request $request, $id): JsonResponse
    {
        try {
            $pqrsfd = PQRSFD::find($id);

            if (!$pqrsfd) {
                return response()->json([
                    'success' => false,
                    'message' => 'PQRSFD no encontrado'
                ], 404);
            }

            if ($pqrsfd->Estado !== 'EnProceso') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden radicar PQRSFDs en proceso'
                ], 422);
            }

            if (!$pqrsfd->IdOperadorAsignado) {
                return response()->json([
                    'success' => false,
                    'message' => 'El PQRSFD debe tener un operador asignado para ser radicado'
                ], 422);
            }

            DB::beginTransaction();

            $estadoAnterior = $pqrsfd->Estado;
            $pqrsfd->radicar();

            // Crear movimiento de radicación
            MovimientoPQRSFD::crearCambioEstado(
                $pqrsfd->IdPQRSFD,
                $request->user()->IdOperador ?? 1,
                $estadoAnterior,
                $pqrsfd->Estado,
                $request->comentarios ?? null
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $pqrsfd->fresh(['cliente', 'operadorAsignado']),
                'message' => 'PQRSFD radicado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al radicar PQRSFD: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cerrar un PQRSFD
     */
    public function cerrar(Request $request, $id): JsonResponse
    {
        try {
            $pqrsfd = PQRSFD::find($id);

            if (!$pqrsfd) {
                return response()->json([
                    'success' => false,
                    'message' => 'PQRSFD no encontrado'
                ], 404);
            }

            if (!in_array($pqrsfd->Estado, ['Radicado', 'EnProceso'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden cerrar PQRSFDs radicados o en proceso'
                ], 422);
            }

            DB::beginTransaction();

            $estadoAnterior = $pqrsfd->Estado;
            $pqrsfd->cerrar();

            // Crear movimiento de cierre
            MovimientoPQRSFD::crearCambioEstado(
                $pqrsfd->IdPQRSFD,
                $request->user()->IdOperador ?? 1,
                $estadoAnterior,
                $pqrsfd->Estado,
                $request->comentarios ?? null
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $pqrsfd->fresh(['cliente', 'operadorAsignado']),
                'message' => 'PQRSFD cerrado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar PQRSFD: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar un PQRSFD
     */
    public function cancelar(Request $request, $id): JsonResponse
    {
        try {
            $pqrsfd = PQRSFD::find($id);

            if (!$pqrsfd) {
                return response()->json([
                    'success' => false,
                    'message' => 'PQRSFD no encontrado'
                ], 404);
            }

            if (in_array($pqrsfd->Estado, ['Cerrado', 'Cancelado'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cancelar un PQRSFD ya cerrado o cancelado'
                ], 422);
            }

            DB::beginTransaction();

            $estadoAnterior = $pqrsfd->Estado;
            $pqrsfd->cancelar();

            // Crear movimiento de cancelación
            MovimientoPQRSFD::crearCambioEstado(
                $pqrsfd->IdPQRSFD,
                $request->user()->IdOperador ?? 1,
                $estadoAnterior,
                $pqrsfd->Estado,
                $request->comentarios ?? null
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $pqrsfd->fresh(['cliente', 'operadorAsignado']),
                'message' => 'PQRSFD cancelado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar PQRSFD: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agregar comentario a un PQRSFD
     */
    public function agregarComentario(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'comentarios' => 'required|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $pqrsfd = PQRSFD::find($id);

            if (!$pqrsfd) {
                return response()->json([
                    'success' => false,
                    'message' => 'PQRSFD no encontrado'
                ], 404);
            }

            DB::beginTransaction();

            // Crear movimiento de comentario
            MovimientoPQRSFD::crearComentario(
                $pqrsfd->IdPQRSFD,
                $request->user()->IdOperador ?? 1,
                $request->comentarios
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Comentario agregado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar comentario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de PQRSFDs
     */
    public function estadisticas(): JsonResponse
    {
        try {
            $totalPQRSFDs = PQRSFD::count();
            $porEstado = PQRSFD::selectRaw('Estado, COUNT(*) as total')
                ->groupBy('Estado')
                ->get();
            
            $porTipo = PQRSFD::selectRaw('TipoPQRSFD, COUNT(*) as total')
                ->groupBy('TipoPQRSFD')
                ->get();

            $promedioTiempo = PQRSFD::whereNotNull('FechaRadicacion')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, FechaRegistro, FechaRadicacion)) as promedio_horas')
                ->first();

            $pqrsfdsRecientes = PQRSFD::with(['cliente', 'operadorAsignado'])
                ->orderBy('FechaRegistro', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_pqrsfds' => $totalPQRSFDs,
                    'por_estado' => $porEstado,
                    'por_tipo' => $porTipo,
                    'promedio_tiempo_radicacion' => round($promedioTiempo->promedio_horas ?? 0, 2),
                    'pqrsfds_recientes' => $pqrsfdsRecientes,
                ],
                'message' => 'Estadísticas obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }
}
