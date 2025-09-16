<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MovimientoPQRSFD;
use App\Models\PQRSFD;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PQRSFDController extends Controller
{
    /**
     * Obtener lista paginada de PQRSFDs con filtros
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = PQRSFD::with(['cliente', 'operador_asignado', 'documentos', 'actividades_caso']);

            // Filtros
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->filled('tipo')) {
                $query->where('tipo_pqrsfd', $request->tipo);
            }

            if ($request->filled('prioridad')) {
                $query->where('prioridad', $request->prioridad);
            }

            if ($request->filled('categoria')) {
                $query->where('categoria', $request->categoria);
            }

            if ($request->filled('operador')) {
                $query->where('operador_asignado_id', $request->operador);
            }

            if ($request->filled('cliente')) {
                $query->where('cliente_id', $request->cliente);
            }

            if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
                $query->whereBetween('created_at', [$request->fecha_inicio, $request->fecha_fin]);
            }

            if ($request->filled('es_prioritario')) {
                $query->where('es_prioritario', $request->boolean('es_prioritario'));
            }

            if ($request->filled('buscar')) {
                $buscar = $request->buscar;
                $query->where(function ($q) use ($buscar) {
                    $q->where('asunto', 'like', '%'.$buscar.'%')
                        ->orWhere('numero_radicacion', 'like', '%'.$buscar.'%')
                        ->orWhere('narracion_cliente', 'like', '%'.$buscar.'%')
                        ->orWhereHas('cliente', function ($q2) use ($buscar) {
                            $q2->where('nombres', 'like', '%'.$buscar.'%')
                                ->orWhere('apellidos', 'like', '%'.$buscar.'%')
                                ->orWhere('correo', 'like', '%'.$buscar.'%');
                        });
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'created_at');
            $direccion = $request->get('direccion', 'desc');

            // Convertir campos antiguos a nuevos
            $ordenMap = [
                'FechaRegistro' => 'created_at',
                'FechaUltimaActualizacion' => 'updated_at',
                'Estado' => 'estado',
                'TipoPQRSFD' => 'tipo_pqrsfd',
                'Prioridad' => 'prioridad',
            ];

            $orden = $ordenMap[$orden] ?? $orden;
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
                'message' => 'PQRSFDs obtenidos exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener PQRSFDs: '.$e->getMessage(),
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
                'operador_asignado',
                'documentos',
                'actividades_caso.operador_asignado',
                'movimientos',
                'donaciones_asociadas',
            ])->find($id);

            if (! $pqrsfd) {
                return response()->json([
                    'success' => false,
                    'message' => 'PQRSFD no encontrado',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $pqrsfd,
                'message' => 'PQRSFD obtenido exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener PQRSFD: '.$e->getMessage(),
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
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $data = $request->all();
            $data['estado'] = $data['estado'] ?? 'pendiente';
            $data['prioridad'] = $data['prioridad'] ?? 'media';

            // Si no hay operador asignado, asignar automáticamente uno disponible
            if (empty($data['operador_asignado_id'])) {
                $operadorDisponible = \App\Models\Operador::activos()
                    ->porRol('operador')
                    ->inRandomOrder()
                    ->first();

                if ($operadorDisponible) {
                    $data['operador_asignado_id'] = $operadorDisponible->id;
                    $data['estado'] = 'en_proceso';
                }
            }

            $pqrsfd = PQRSFD::create($data);

            // Crear registro de movimiento si existe el modelo
            if (class_exists('App\Models\MovimientoPQRSFD')) {
                \App\Models\MovimientoPQRSFD::create([
                    'pqrsfd_id' => $pqrsfd->id,
                    'tipo_movimiento' => 'creacion',
                    'descripcion' => 'PQRSFD creado por el cliente',
                    'estado_anterior' => null,
                    'estado_nuevo' => $pqrsfd->estado,
                    'usuario_id' => $request->user()?->id ?? 1,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $pqrsfd->load(['cliente', 'operador_asignado']),
                'message' => 'PQRSFD creado exitosamente',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear PQRSFD: '.$e->getMessage(),
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

            if (! $pqrsfd) {
                return response()->json([
                    'success' => false,
                    'message' => 'PQRSFD no encontrado',
                ], 404);
            }

            $validator = Validator::make($request->all(), PQRSFD::rules($id));

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $estadoAnterior = $pqrsfd->estado;
            $data = $request->all();

            $pqrsfd->update($data);

            // Crear movimiento si cambió el estado
            if ($estadoAnterior !== $pqrsfd->estado && class_exists('App\Models\MovimientoPQRSFD')) {
                \App\Models\MovimientoPQRSFD::create([
                    'pqrsfd_id' => $pqrsfd->id,
                    'tipo_movimiento' => 'cambio_estado',
                    'descripcion' => $request->comentarios ?? 'Estado actualizado',
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => $pqrsfd->estado,
                    'usuario_id' => $request->user()?->id ?? 1,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $pqrsfd->fresh(['cliente', 'operador_asignado']),
                'message' => 'PQRSFD actualizado exitosamente',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar PQRSFD: '.$e->getMessage(),
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

            if (! $pqrsfd) {
                return response()->json([
                    'success' => false,
                    'message' => 'PQRSFD no encontrado',
                ], 404);
            }

            // Solo se pueden eliminar PQRSFDs cancelados o cerrados
            if (! in_array($pqrsfd->estado, ['cancelado', 'cerrado'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden eliminar PQRSFDs cancelados o cerrados',
                ], 422);
            }

            $pqrsfd->delete();

            return response()->json([
                'success' => true,
                'message' => 'PQRSFD eliminado exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar PQRSFD: '.$e->getMessage(),
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
                'operador_asignado_id' => 'required|exists:operadores,id',
                'comentarios' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $pqrsfd = PQRSFD::find($id);

            if (! $pqrsfd) {
                return response()->json([
                    'success' => false,
                    'message' => 'PQRSFD no encontrado',
                ], 404);
            }

            if ($pqrsfd->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden asignar PQRSFDs pendientes',
                ], 422);
            }

            DB::beginTransaction();

            $pqrsfd->asignarOperador($request->operador_asignado_id);

            // Crear movimiento de asignación
            if (class_exists('App\Models\MovimientoPQRSFD')) {
                \App\Models\MovimientoPQRSFD::create([
                    'pqrsfd_id' => $pqrsfd->id,
                    'tipo_movimiento' => 'asignacion_operador',
                    'descripcion' => $request->comentarios ?? 'Operador asignado',
                    'estado_anterior' => $pqrsfd->estado,
                    'estado_nuevo' => 'en_proceso',
                    'usuario_id' => $request->user()?->id ?? 1,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $pqrsfd->fresh(['cliente', 'operador_asignado']),
                'message' => 'Operador asignado exitosamente',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al asignar operador: '.$e->getMessage(),
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

            if (! $pqrsfd) {
                return response()->json([
                    'success' => false,
                    'message' => 'PQRSFD no encontrado',
                ], 404);
            }

            if ($pqrsfd->estado !== 'en_proceso') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden radicar PQRSFDs en proceso',
                ], 422);
            }

            if (! $pqrsfd->operador_asignado_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'El PQRSFD debe tener un operador asignado para ser radicado',
                ], 422);
            }

            DB::beginTransaction();

            $estadoAnterior = $pqrsfd->estado;
            $pqrsfd->radicar();

            // Crear movimiento de radicación
            if (class_exists('App\Models\MovimientoPQRSFD')) {
                \App\Models\MovimientoPQRSFD::create([
                    'pqrsfd_id' => $pqrsfd->id,
                    'tipo_movimiento' => 'radicacion',
                    'descripcion' => $request->comentarios ?? 'PQRSFD radicado',
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => $pqrsfd->estado,
                    'usuario_id' => $request->user()?->id ?? 1,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $pqrsfd->fresh(['cliente', 'operador_asignado']),
                'message' => 'PQRSFD radicado exitosamente',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al radicar PQRSFD: '.$e->getMessage(),
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

            if (! $pqrsfd) {
                return response()->json([
                    'success' => false,
                    'message' => 'PQRSFD no encontrado',
                ], 404);
            }

            if (! in_array($pqrsfd->estado, ['radicado', 'en_proceso'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden cerrar PQRSFDs radicados o en proceso',
                ], 422);
            }

            DB::beginTransaction();

            $estadoAnterior = $pqrsfd->estado;
            $pqrsfd->cerrar();

            // Crear movimiento de cierre
            if (class_exists('App\Models\MovimientoPQRSFD')) {
                \App\Models\MovimientoPQRSFD::create([
                    'pqrsfd_id' => $pqrsfd->id,
                    'tipo_movimiento' => 'cierre',
                    'descripcion' => $request->comentarios ?? 'PQRSFD cerrado',
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => $pqrsfd->estado,
                    'usuario_id' => $request->user()?->id ?? 1,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $pqrsfd->fresh(['cliente', 'operador_asignado']),
                'message' => 'PQRSFD cerrado exitosamente',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar PQRSFD: '.$e->getMessage(),
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

            if (! $pqrsfd) {
                return response()->json([
                    'success' => false,
                    'message' => 'PQRSFD no encontrado',
                ], 404);
            }

            if (in_array($pqrsfd->estado, ['cerrado', 'cancelado'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cancelar un PQRSFD ya cerrado o cancelado',
                ], 422);
            }

            DB::beginTransaction();

            $estadoAnterior = $pqrsfd->estado;
            $pqrsfd->cancelar();

            // Crear movimiento de cancelación
            if (class_exists('App\Models\MovimientoPQRSFD')) {
                \App\Models\MovimientoPQRSFD::create([
                    'pqrsfd_id' => $pqrsfd->id,
                    'tipo_movimiento' => 'cancelacion',
                    'descripcion' => $request->comentarios ?? 'PQRSFD cancelado',
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => $pqrsfd->estado,
                    'usuario_id' => $request->user()?->id ?? 1,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $pqrsfd->fresh(['cliente', 'operador_asignado']),
                'message' => 'PQRSFD cancelado exitosamente',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar PQRSFD: '.$e->getMessage(),
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
                'comentarios' => 'required|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $pqrsfd = PQRSFD::find($id);

            if (! $pqrsfd) {
                return response()->json([
                    'success' => false,
                    'message' => 'PQRSFD no encontrado',
                ], 404);
            }

            DB::beginTransaction();

            // Crear movimiento de comentario
            if (class_exists('App\Models\MovimientoPQRSFD')) {
                \App\Models\MovimientoPQRSFD::create([
                    'pqrsfd_id' => $pqrsfd->id,
                    'tipo_movimiento' => 'comentario',
                    'descripcion' => $request->comentarios,
                    'estado_anterior' => $pqrsfd->estado,
                    'estado_nuevo' => $pqrsfd->estado,
                    'usuario_id' => $request->user()?->id ?? 1,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Comentario agregado exitosamente',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al agregar comentario: '.$e->getMessage(),
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
            $porEstado = PQRSFD::selectRaw('estado, COUNT(*) as total')
                ->groupBy('estado')
                ->get();

            $porTipo = PQRSFD::selectRaw('tipo_pqrsfd, COUNT(*) as total')
                ->groupBy('tipo_pqrsfd')
                ->get();

            $promedioTiempo = PQRSFD::whereNotNull('fecha_radicacion')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, fecha_radicacion)) as promedio_horas')
                ->first();

            $pqrsfdsRecientes = PQRSFD::with(['cliente', 'operador_asignado'])
                ->orderBy('created_at', 'desc')
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
                'message' => 'Estadísticas obtenidas exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: '.$e->getMessage(),
            ], 500);
        }
    }
}
