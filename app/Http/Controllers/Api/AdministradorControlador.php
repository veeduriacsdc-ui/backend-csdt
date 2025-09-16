<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Donacion;
use App\Models\Operador;
use App\Models\Tarea;
use App\Models\Veeduria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class AdministradorControlador extends Controller
{
    /**
     * Obtener dashboard administrativo
     */
    public function ObtenerDashboard(): JsonResponse
    {
        try {
            $estadisticas = Cache::remember('dashboard_admin', 300, function () {
                return [
                    'usuarios' => [
                        'total_clientes' => Cliente::count(),
                        'total_operadores' => Operador::count(),
                        'operadores_activos' => Operador::where('Estado', 'activo')->count(),
                        'operadores_inactivos' => Operador::where('Estado', 'inactivo')->count(),
                        'operadores_suspendidos' => Operador::where('Estado', 'suspendido')->count(),
                    ],
                    'veedurias' => [
                        'total' => Veeduria::count(),
                        'pendientes' => Veeduria::where('Estado', 'pendiente')->count(),
                        'en_proceso' => Veeduria::where('Estado', 'en_proceso')->count(),
                        'completadas' => Veeduria::where('Estado', 'completada')->count(),
                        'canceladas' => Veeduria::where('Estado', 'cancelada')->count(),
                    ],
                    'donaciones' => [
                        'total' => Donacion::count(),
                        'pendientes' => Donacion::where('Estado', 'pendiente')->count(),
                        'validadas' => Donacion::where('Estado', 'validada')->count(),
                        'rechazadas' => Donacion::where('Estado', 'rechazada')->count(),
                        'monto_total' => Donacion::where('Estado', 'validada')->sum('Monto'),
                    ],
                    'tareas' => [
                        'total' => Tarea::count(),
                        'pendientes' => Tarea::where('Estado', 'pendiente')->count(),
                        'en_proceso' => Tarea::where('Estado', 'en_proceso')->count(),
                        'completadas' => Tarea::where('Estado', 'completada')->count(),
                        'atrasadas' => Tarea::where('FechaVencimiento', '<', now())->where('Estado', '!=', 'completada')->count(),
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Dashboard administrativo obtenido exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener dashboard: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener lista de operadores para administración
     */
    public function ObtenerOperadores(Request $request): JsonResponse
    {
        try {
            $query = Operador::with(['veedurias', 'tareas']);

            // Filtros
            if ($request->filled('estado')) {
                $query->where('Estado', $request->estado);
            }

            if ($request->filled('profesion')) {
                $query->where('Profesion', 'like', '%'.$request->profesion.'%');
            }

            if ($request->filled('buscar')) {
                $buscar = $request->buscar;
                $query->where(function ($q) use ($buscar) {
                    $q->where('Nombres', 'like', '%'.$buscar.'%')
                        ->orWhere('Apellidos', 'like', '%'.$buscar.'%')
                        ->orWhere('Correo', 'like', '%'.$buscar.'%');
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'Nombres');
            $direccion = $request->get('direccion', 'asc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $operadores = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $operadores->items(),
                'pagination' => [
                    'current_page' => $operadores->currentPage(),
                    'last_page' => $operadores->lastPage(),
                    'per_page' => $operadores->perPage(),
                    'total' => $operadores->total(),
                ],
                'message' => 'Operadores obtenidos exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener operadores: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cambiar estado de operador (administrativo)
     */
    public function CambiarEstadoOperador(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'Estado' => 'required|in:activo,inactivo,suspendido',
                'Motivo' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $operador = Operador::find($id);

            if (! $operador) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operador no encontrado',
                ], 404);
            }

            $estadoAnterior = $operador->Estado;
            $operador->update([
                'Estado' => $request->Estado,
                'MotivoCambioEstado' => $request->Motivo,
                'FechaCambioEstado' => now(),
            ]);

            // Limpiar cache del dashboard
            Cache::forget('dashboard_admin');

            return response()->json([
                'success' => true,
                'data' => $operador,
                'message' => "Estado del operador cambiado de '{$estadoAnterior}' a '{$request->Estado}' exitosamente",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener estadísticas detalladas
     */
    public function ObtenerEstadisticasDetalladas(Request $request): JsonResponse
    {
        try {
            $tipo = $request->get('tipo', 'general');
            $fechaInicio = $request->get('fecha_inicio');
            $fechaFin = $request->get('fecha_fin');

            $query = null;
            switch ($tipo) {
                case 'veedurias':
                    $query = Veeduria::query();
                    break;
                case 'donaciones':
                    $query = Donacion::query();
                    break;
                case 'tareas':
                    $query = Tarea::query();
                    break;
                default:
                    $query = Veeduria::query();
            }

            if ($fechaInicio && $fechaFin) {
                $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
            }

            $estadisticas = [
                'total' => $query->count(),
                'por_estado' => $query->selectRaw('Estado, COUNT(*) as total')
                    ->groupBy('Estado')
                    ->get(),
                'por_fecha' => $query->selectRaw('DATE(created_at) as fecha, COUNT(*) as total')
                    ->groupBy('fecha')
                    ->orderBy('fecha')
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas detalladas obtenidas exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generar reporte administrativo
     */
    public function GenerarReporte(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'tipo' => 'required|in:veedurias,donaciones,tareas,usuarios,general',
                'formato' => 'required|in:json,pdf,excel',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after:fecha_inicio',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Aquí se implementaría la lógica de generación de reportes
            // Por ahora retornamos un mensaje de éxito
            $reporte = [
                'tipo' => $request->tipo,
                'formato' => $request->formato,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'generado_en' => now()->toISOString(),
                'estado' => 'generado',
            ];

            return response()->json([
                'success' => true,
                'data' => $reporte,
                'message' => 'Reporte generado exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener configuración del sistema
     */
    public function ObtenerConfiguracion(): JsonResponse
    {
        try {
            $configuracion = [
                'sistema' => [
                    'nombre' => 'CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL',
                    'version' => '1.0.0',
                    'ambiente' => config('app.env'),
                    'debug' => config('app.debug'),
                ],
                'base_datos' => [
                    'conexion' => config('database.default'),
                    'driver' => config('database.connections.'.config('database.default').'.driver'),
                ],
                'cache' => [
                    'driver' => config('cache.default'),
                    'ttl_dashboard' => 300, // 5 minutos
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $configuracion,
                'message' => 'Configuración del sistema obtenida exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener configuración: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Limpiar cache del sistema
     */
    public function LimpiarCache(): JsonResponse
    {
        try {
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Cache del sistema limpiado exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar cache: '.$e->getMessage(),
            ], 500);
        }
    }
}
