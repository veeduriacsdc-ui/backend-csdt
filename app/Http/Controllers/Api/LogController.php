<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class LogController extends Controller
{
    /**
     * Obtener lista de logs
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Log::with(['usuario']);

            // Filtros
            if ($request->has('usu_id')) {
                $query->where('usu_id', $request->usu_id);
            }
            if ($request->has('acc')) {
                $query->where('acc', $request->acc);
            }
            if ($request->has('tab')) {
                $query->where('tab', $request->tab);
            }
            if ($request->has('reg_id')) {
                $query->where('reg_id', $request->reg_id);
            }

            // Filtros de fecha
            if ($request->has('fec_ini') && $request->has('fec_fin')) {
                $query->whereBetween('fec', [$request->fec_ini, $request->fec_fin]);
            } elseif ($request->has('fec_ini')) {
                $query->whereDate('fec', $request->fec_ini);
            }

            // Filtros especiales
            if ($request->has('recientes')) {
                $dias = $request->get('dias', 7);
                $query->recientes($dias);
            }

            // Búsqueda
            if ($request->has('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('acc', 'like', '%' . $buscar . '%')
                      ->orWhere('tab', 'like', '%' . $buscar . '%')
                      ->orWhere('des', 'like', '%' . $buscar . '%')
                      ->orWhere('ip', 'like', '%' . $buscar . '%');
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'fec');
            $direccion = $request->get('direccion', 'desc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $porPagina = $request->get('por_pagina', 15);
            $logs = $query->paginate($porPagina);

            return response()->json([
                'success' => true,
                'data' => $logs,
                'message' => 'Logs obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener log por ID
     */
    public function show($id): JsonResponse
    {
        try {
            $log = Log::with(['usuario'])->find($id);

            if (!$log) {
                return response()->json([
                    'success' => false,
                    'message' => 'Log no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $log,
                'message' => 'Log obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener log: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener logs por usuario
     */
    public function porUsuario($usuarioId): JsonResponse
    {
        try {
            $logs = Log::with(['usuario'])
                ->where('usu_id', $usuarioId)
                ->orderBy('fec', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $logs,
                'message' => 'Logs del usuario obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener logs del usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener logs por acción
     */
    public function porAccion($accion): JsonResponse
    {
        try {
            $logs = Log::with(['usuario'])
                ->where('acc', $accion)
                ->orderBy('fec', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $logs,
                'message' => 'Logs de la acción obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener logs de la acción: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener logs por tabla
     */
    public function porTabla($tabla): JsonResponse
    {
        try {
            $logs = Log::with(['usuario'])
                ->where('tab', $tabla)
                ->orderBy('fec', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $logs,
                'message' => 'Logs de la tabla obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener logs de la tabla: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener logs por registro específico
     */
    public function porRegistro($tabla, $registroId): JsonResponse
    {
        try {
            $logs = Log::with(['usuario'])
                ->where('tab', $tabla)
                ->where('reg_id', $registroId)
                ->orderBy('fec', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $logs,
                'message' => 'Logs del registro obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener logs del registro: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener logs por fecha
     */
    public function porFecha($fechaInicio, $fechaFin = null): JsonResponse
    {
        try {
            $query = Log::with(['usuario']);

            if ($fechaFin) {
                $query->whereBetween('fec', [$fechaInicio, $fechaFin]);
            } else {
                $query->whereDate('fec', $fechaInicio);
            }

            $logs = $query->orderBy('fec', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $logs,
                'message' => 'Logs de la fecha obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener logs de la fecha: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener logs recientes
     */
    public function recientes($dias = 7): JsonResponse
    {
        try {
            $logs = Log::with(['usuario'])
                ->recientes($dias)
                ->orderBy('fec', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $logs,
                'message' => 'Logs recientes obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener logs recientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de logs
     */
    public function estadisticas(Request $request): JsonResponse
    {
        try {
            $query = Log::query();

            // Filtros de fecha
            if ($request->has('fec_ini') && $request->has('fec_fin')) {
                $query->whereBetween('fec', [$request->fec_ini, $request->fec_fin]);
            }

            $estadisticas = [
                'total_logs' => $query->count(),
                'por_accion' => $query->selectRaw('acc, COUNT(*) as total')
                    ->groupBy('acc')
                    ->orderBy('total', 'desc')
                    ->get(),
                'por_tabla' => $query->selectRaw('tab, COUNT(*) as total')
                    ->groupBy('tab')
                    ->orderBy('total', 'desc')
                    ->get(),
                'por_usuario' => $query->selectRaw('usu_id, COUNT(*) as total')
                    ->whereNotNull('usu_id')
                    ->groupBy('usu_id')
                    ->orderBy('total', 'desc')
                    ->limit(10)
                    ->get(),
                'por_dia' => $query->selectRaw('DATE(fec) as fecha, COUNT(*) as total')
                    ->groupBy('fecha')
                    ->orderBy('fecha', 'desc')
                    ->limit(30)
                    ->get(),
                'por_hora' => $query->selectRaw('HOUR(fec) as hora, COUNT(*) as total')
                    ->groupBy('hora')
                    ->orderBy('hora')
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
     * Buscar logs
     */
    public function buscar(Request $request): JsonResponse
    {
        try {
            $query = Log::with(['usuario']);

            if ($request->has('termino')) {
                $termino = $request->termino;
                $query->where(function($q) use ($termino) {
                    $q->where('acc', 'like', '%' . $termino . '%')
                      ->orWhere('tab', 'like', '%' . $termino . '%')
                      ->orWhere('des', 'like', '%' . $termino . '%')
                      ->orWhere('ip', 'like', '%' . $termino . '%');
                });
            }

            $logs = $query->orderBy('fec', 'desc')->limit(10)->get();

            return response()->json([
                'success' => true,
                'data' => $logs,
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
