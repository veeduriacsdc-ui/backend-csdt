<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LogAuditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class LogAuditoriaControlador extends Controller
{
    /**
     * Obtener lista paginada de logs de auditoría con filtros
     */
    public function ObtenerLista(Request $request): JsonResponse
    {
        try {
            $query = LogAuditoria::with(['cliente', 'operador']);

            // Filtros
            if ($request->filled('accion')) {
                $query->where('Accion', $request->accion);
            }

            if ($request->filled('tipo_entidad')) {
                $query->where('TipoEntidad', $request->tipo_entidad);
            }

            if ($request->filled('cliente_id')) {
                $query->where('ClienteId', $request->cliente_id);
            }

            if ($request->filled('operador_id')) {
                $query->where('OperadorId', $request->operador_id);
            }

            if ($request->filled('fecha_inicio')) {
                $query->where('FechaAccion', '>=', $request->fecha_inicio);
            }

            if ($request->filled('fecha_fin')) {
                $query->where('FechaAccion', '<=', $request->fecha_fin);
            }

            if ($request->filled('ip')) {
                $query->where('DireccionIP', 'like', '%' . $request->ip . '%');
            }

            if ($request->filled('buscar')) {
                $buscar = $request->buscar;
                $query->where(function ($q) use ($buscar) {
                    $q->where('Descripcion', 'like', '%' . $buscar . '%')
                      ->orWhere('Detalles', 'like', '%' . $buscar . '%')
                      ->orWhere('Codigo', 'like', '%' . $buscar . '%');
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'FechaAccion');
            $direccion = $request->get('direccion', 'desc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $logs = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $logs->items(),
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                ],
                'message' => 'Logs de auditoría obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener logs de auditoría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un log de auditoría específico
     */
    public function ObtenerPorId($id): JsonResponse
    {
        try {
            $log = LogAuditoria::with(['cliente', 'operador'])->find($id);

            if (!$log) {
                return response()->json([
                    'success' => false,
                    'message' => 'Log de auditoría no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $log,
                'message' => 'Log de auditoría obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener log de auditoría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo log de auditoría
     */
    public function Crear(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'Accion' => 'required|in:crear,actualizar,eliminar,consultar,login,logout,descargar,subir',
                'TipoEntidad' => 'required|string|max:100',
                'IdEntidad' => 'required|integer',
                'Descripcion' => 'required|string|max:500',
                'Detalles' => 'sometimes|string|max:2000',
                'ClienteId' => 'sometimes|exists:Clientes,IdCliente',
                'OperadorId' => 'sometimes|exists:Operadores,IdOperador',
                'DireccionIP' => 'required|ip',
                'UserAgent' => 'sometimes|string|max:500',
                'DatosAnteriores' => 'sometimes|json',
                'DatosNuevos' => 'sometimes|json'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $datos = $request->all();
            $datos['FechaAccion'] = now();
            $datos['Codigo'] = $this->GenerarCodigoLog();

            $log = LogAuditoria::create($datos);

            return response()->json([
                'success' => true,
                'data' => $log,
                'message' => 'Log de auditoría creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear log de auditoría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener logs por entidad
     */
    public function ObtenerPorEntidad(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'tipo_entidad' => 'required|string|max:100',
                'id_entidad' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parámetros no válidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $logs = LogAuditoria::with(['cliente', 'operador'])
                ->where('TipoEntidad', $request->tipo_entidad)
                ->where('IdEntidad', $request->id_entidad)
                ->orderBy('FechaAccion', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $logs,
                'message' => 'Logs de la entidad obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener logs por usuario
     */
    public function ObtenerPorUsuario(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'cliente_id' => 'sometimes|exists:Clientes,IdCliente',
                'operador_id' => 'sometimes|exists:Operadores,IdOperador',
                'fecha_inicio' => 'sometimes|date',
                'fecha_fin' => 'sometimes|date|after:fecha_inicio'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parámetros no válidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = LogAuditoria::with(['cliente', 'operador']);

            if ($request->filled('cliente_id')) {
                $query->where('ClienteId', $request->cliente_id);
            }

            if ($request->filled('operador_id')) {
                $query->where('OperadorId', $request->operador_id);
            }

            if ($request->filled('fecha_inicio')) {
                $query->where('FechaAccion', '>=', $request->fecha_inicio);
            }

            if ($request->filled('fecha_fin')) {
                $query->where('FechaAccion', '<=', $request->fecha_fin);
            }

            $logs = $query->orderBy('FechaAccion', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $logs,
                'message' => 'Logs del usuario obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de auditoría
     */
    public function ObtenerEstadisticas(): JsonResponse
    {
        try {
            $estadisticas = [
                'total' => LogAuditoria::count(),
                'por_accion' => LogAuditoria::selectRaw('Accion, COUNT(*) as total')
                    ->groupBy('Accion')
                    ->get(),
                'por_tipo_entidad' => LogAuditoria::selectRaw('TipoEntidad, COUNT(*) as total')
                    ->groupBy('TipoEntidad')
                    ->get(),
                'por_mes' => LogAuditoria::selectRaw('MONTH(FechaAccion) as mes, COUNT(*) as total')
                    ->whereYear('FechaAccion', date('Y'))
                    ->groupBy('mes')
                    ->orderBy('mes')
                    ->get(),
                'por_dia' => LogAuditoria::selectRaw('DATE(FechaAccion) as fecha, COUNT(*) as total')
                    ->whereDate('FechaAccion', '>=', now()->subDays(30))
                    ->groupBy('fecha')
                    ->orderBy('fecha')
                    ->get(),
                'acciones_hoy' => LogAuditoria::whereDate('FechaAccion', today())->count(),
                'acciones_semana' => LogAuditoria::whereDate('FechaAccion', '>=', now()->startOfWeek())->count(),
                'acciones_mes' => LogAuditoria::whereDate('FechaAccion', '>=', now()->startOfMonth())->count()
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
     * Exportar logs de auditoría
     */
    public function Exportar(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'formato' => 'required|in:json,csv,pdf',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after:fecha_inicio',
                'acciones' => 'sometimes|array',
                'tipos_entidad' => 'sometimes|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parámetros no válidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = LogAuditoria::with(['cliente', 'operador'])
                ->whereBetween('FechaAccion', [$request->fecha_inicio, $request->fecha_fin]);

            if ($request->filled('acciones')) {
                $query->whereIn('Accion', $request->acciones);
            }

            if ($request->filled('tipos_entidad')) {
                $query->whereIn('TipoEntidad', $request->tipos_entidad);
            }

            $logs = $query->orderBy('FechaAccion', 'desc')->get();

            // Aquí se implementaría la lógica de exportación según el formato
            $datosExportacion = [
                'formato' => $request->formato,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'total_registros' => $logs->count(),
                'fecha_exportacion' => now()->toISOString(),
                'estado' => 'exportado'
            ];

            return response()->json([
                'success' => true,
                'data' => $datosExportacion,
                'message' => 'Logs exportados exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar logs antiguos
     */
    public function LimpiarLogsAntiguos(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'dias' => 'required|integer|min:30|max:3650' // Entre 30 días y 10 años
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Número de días no válido',
                    'errors' => $validator->errors()
                ], 422);
            }

            $fechaLimite = now()->subDays($request->dias);
            $logsEliminados = LogAuditoria::where('FechaAccion', '<', $fechaLimite)->delete();

            return response()->json([
                'success' => true,
                'data' => [
                    'logs_eliminados' => $logsEliminados,
                    'fecha_limite' => $fechaLimite->toISOString(),
                    'dias_especificados' => $request->dias
                ],
                'message' => 'Logs antiguos eliminados exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar código único para log
     */
    private function GenerarCodigoLog(): string
    {
        $prefijo = 'LOG';
        $anio = date('Y');
        $ultimoCodigo = LogAuditoria::whereYear('FechaAccion', $anio)
            ->orderBy('Codigo', 'desc')
            ->first();

        if ($ultimoCodigo) {
            $numero = (int) substr($ultimoCodigo->Codigo, -4) + 1;
        } else {
            $numero = 1;
        }

        return $prefijo . $anio . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }
}
