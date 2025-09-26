<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donacion;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class DonacionController extends Controller
{
    /**
     * Obtener lista de donaciones
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Donacion::with(['usuario', 'veeduria', 'validadoPor']);

            // Filtros
            if ($request->has('est')) {
                $query->where('est', $request->est);
            }
            if ($request->has('met_pag')) {
                $query->where('met_pag', $request->met_pag);
            }
            if ($request->has('tip_don')) {
                $query->where('tip_don', $request->tip_don);
            }
            if ($request->has('usu_id')) {
                $query->where('usu_id', $request->usu_id);
            }
            if ($request->has('vee_id')) {
                $query->where('vee_id', $request->vee_id);
            }
            if ($request->has('anon')) {
                $query->where('anon', $request->anon);
            }

            // Rango de fechas
            if ($request->has('fec_ini') && $request->has('fec_fin')) {
                $query->whereBetween('fec_don', [$request->fec_ini, $request->fec_fin]);
            }

            // Rango de montos
            if ($request->has('mon_ini') && $request->has('mon_fin')) {
                $query->whereBetween('mon', [$request->mon_ini, $request->mon_fin]);
            }

            // Búsqueda
            if ($request->has('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('ref_pag', 'like', '%' . $buscar . '%')
                      ->orWhere('mot', 'like', '%' . $buscar . '%')
                      ->orWhere('cam', 'like', '%' . $buscar . '%')
                      ->orWhere('cod_pro', 'like', '%' . $buscar . '%');
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'fec_don');
            $direccion = $request->get('direccion', 'desc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $porPagina = $request->get('por_pagina', 15);
            $donaciones = $query->paginate($porPagina);

            return response()->json([
                'success' => true,
                'data' => $donaciones,
                'message' => 'Donaciones obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener donaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener donación por ID
     */
    public function show($id): JsonResponse
    {
        try {
            $donacion = Donacion::with(['usuario', 'veeduria', 'validadoPor'])->find($id);

            if (!$donacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donación no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $donacion,
                'message' => 'Donación obtenida exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener donación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nueva donación
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), Donacion::reglas(), Donacion::mensajes());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $donacion = Donacion::create($request->all());

            // Log de creación
            Log::logCreacion('donaciones', $donacion->id, $donacion->toArray());

            return response()->json([
                'success' => true,
                'data' => $donacion,
                'message' => 'Donación creada exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear donación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar donación
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $donacion = Donacion::find($id);

            if (!$donacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donación no encontrada'
                ], 404);
            }

            $validator = Validator::make($request->all(), Donacion::reglas($id), Donacion::mensajes());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $datosAnteriores = $donacion->toArray();
            $donacion->update($request->all());

            // Log de actualización
            Log::logActualizacion('donaciones', $donacion->id, $datosAnteriores, $donacion->toArray());

            return response()->json([
                'success' => true,
                'data' => $donacion,
                'message' => 'Donación actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar donación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar donación (soft delete)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $donacion = Donacion::find($id);

            if (!$donacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donación no encontrada'
                ], 404);
            }

            $datosAnteriores = $donacion->toArray();
            $donacion->delete();

            // Log de eliminación
            Log::logEliminacion('donaciones', $donacion->id, $datosAnteriores);

            return response()->json([
                'success' => true,
                'message' => 'Donación eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar donación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurar donación
     */
    public function restore($id): JsonResponse
    {
        try {
            $donacion = Donacion::withTrashed()->find($id);

            if (!$donacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donación no encontrada'
                ], 404);
            }

            $donacion->restore();

            // Log de restauración
            Log::logRestauracion('donaciones', $donacion->id);

            return response()->json([
                'success' => true,
                'data' => $donacion,
                'message' => 'Donación restaurada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar donación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirmar donación
     */
    public function confirmar(Request $request, $id): JsonResponse
    {
        try {
            $donacion = Donacion::find($id);

            if (!$donacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donación no encontrada'
                ], 404);
            }

            $donacion->confirmar(auth()->id());

            // Log de confirmación
            Log::crear('confirmar', 'donaciones', $donacion->id, 'Donación confirmada');

            return response()->json([
                'success' => true,
                'data' => $donacion,
                'message' => 'Donación confirmada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar donación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar donación
     */
    public function rechazar(Request $request, $id): JsonResponse
    {
        try {
            $donacion = Donacion::find($id);

            if (!$donacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donación no encontrada'
                ], 404);
            }

            $donacion->rechazar($request->motivo);

            // Log de rechazo
            Log::crear('rechazar', 'donaciones', $donacion->id, 'Donación rechazada: ' . $request->motivo);

            return response()->json([
                'success' => true,
                'data' => $donacion,
                'message' => 'Donación rechazada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar donación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar donación
     */
    public function cancelar(Request $request, $id): JsonResponse
    {
        try {
            $donacion = Donacion::find($id);

            if (!$donacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donación no encontrada'
                ], 404);
            }

            $donacion->cancelar($request->motivo);

            // Log de cancelación
            Log::crear('cancelar', 'donaciones', $donacion->id, 'Donación cancelada: ' . $request->motivo);

            return response()->json([
                'success' => true,
                'data' => $donacion,
                'message' => 'Donación cancelada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar donación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar donación
     */
    public function procesar($id): JsonResponse
    {
        try {
            $donacion = Donacion::find($id);

            if (!$donacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donación no encontrada'
                ], 404);
            }

            $donacion->procesar();

            // Log de procesamiento
            Log::crear('procesar', 'donaciones', $donacion->id, 'Donación en proceso');

            return response()->json([
                'success' => true,
                'data' => $donacion,
                'message' => 'Donación en proceso exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar donación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de donaciones
     */
    public function estadisticas(Request $request): JsonResponse
    {
        try {
            $query = Donacion::query();

            // Filtros por fecha
            if ($request->has('fec_ini') && $request->has('fec_fin')) {
                $query->whereBetween('fec_don', [$request->fec_ini, $request->fec_fin]);
            }

            $estadisticas = [
                'total_donaciones' => Donacion::count(),
                'donaciones_activas' => Donacion::where('est', 'act')->count(),
                'donaciones_inactivas' => Donacion::where('est', 'ina')->count(),
                'donaciones_suspendidas' => Donacion::where('est', 'sus')->count(),
                'monto_total' => Donacion::where('est', 'act')->sum('mon'),
                'monto_promedio' => Donacion::where('est', 'act')->avg('mon'),
                'monto_maximo' => Donacion::where('est', 'act')->max('mon'),
                'monto_minimo' => Donacion::where('est', 'act')->min('mon'),
                'por_tipo' => Donacion::selectRaw('tip, COUNT(*) as total')
                    ->groupBy('tip')
                    ->get(),
                'por_estado' => Donacion::selectRaw('est, COUNT(*) as total')
                    ->groupBy('est')
                    ->get(),
                'por_usuario' => Donacion::with('usuario')
                    ->selectRaw('usu_id, COUNT(*) as total, SUM(mon) as monto_total')
                    ->groupBy('usu_id')
                    ->orderBy('total', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function($item) {
                        return [
                            'usuario_id' => $item->usu_id,
                            'usuario_nombre' => $item->usuario ? $item->usuario->nom . ' ' . $item->usuario->ape : 'Usuario no encontrado',
                            'total_donaciones' => $item->total,
                            'monto_total' => $item->monto_total
                        ];
                    }),
                'por_mes' => Donacion::selectRaw('DATE_FORMAT(fec_don, "%Y-%m") as mes, COUNT(*) as total, SUM(mon) as monto')
                    ->groupBy('mes')
                    ->orderBy('mes', 'desc')
                    ->limit(12)
                    ->get(),
                'estadisticas_generales' => [
                    'donaciones_hoy' => Donacion::whereDate('fec_don', today())->count(),
                    'donaciones_esta_semana' => Donacion::whereBetween('fec_don', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    'donaciones_este_mes' => Donacion::whereMonth('fec_don', now()->month)->count(),
                    'donaciones_este_ano' => Donacion::whereYear('fec_don', now()->year)->count()
                ]
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
     * Buscar donaciones
     */
    public function buscar(Request $request): JsonResponse
    {
        try {
            $query = Donacion::with(['usuario', 'veeduria']);

            if ($request->has('termino')) {
                $termino = $request->termino;
                $query->where(function($q) use ($termino) {
                    $q->where('ref_pag', 'like', '%' . $termino . '%')
                      ->orWhere('mot', 'like', '%' . $termino . '%')
                      ->orWhere('cam', 'like', '%' . $termino . '%');
                });
            }

            $donaciones = $query->limit(10)->get();

            return response()->json([
                'success' => true,
                'data' => $donaciones,
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