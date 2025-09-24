<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ConfiguracionController extends Controller
{
    /**
     * Obtener lista de configuraciones
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Configuracion::query();

            // Filtros
            if ($request->has('est')) {
                $query->where('est', $request->est);
            }
            if ($request->has('cat')) {
                $query->where('cat', $request->cat);
            }
            if ($request->has('tip')) {
                $query->where('tip', $request->tip);
            }

            // Búsqueda
            if ($request->has('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('cla', 'like', '%' . $buscar . '%')
                      ->orWhere('des', 'like', '%' . $buscar . '%')
                      ->orWhere('cat', 'like', '%' . $buscar . '%');
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'cla');
            $direccion = $request->get('direccion', 'asc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $porPagina = $request->get('por_pagina', 15);
            $configuraciones = $query->paginate($porPagina);

            return response()->json([
                'success' => true,
                'data' => $configuraciones,
                'message' => 'Configuraciones obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener configuraciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener configuración por ID
     */
    public function show($id): JsonResponse
    {
        try {
            $configuracion = Configuracion::find($id);

            if (!$configuracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $configuracion,
                'message' => 'Configuración obtenida exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nueva configuración
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), Configuracion::reglas(), Configuracion::mensajes());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $configuracion = Configuracion::create($request->all());

            // Log de creación
            Log::logCreacion('configuraciones', $configuracion->id, $configuracion->toArray());

            return response()->json([
                'success' => true,
                'data' => $configuracion,
                'message' => 'Configuración creada exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar configuración
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $configuracion = Configuracion::find($id);

            if (!$configuracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración no encontrada'
                ], 404);
            }

            $validator = Validator::make($request->all(), Configuracion::reglas($id), Configuracion::mensajes());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $datosAnteriores = $configuracion->toArray();
            $configuracion->update($request->all());

            // Log de actualización
            Log::logActualizacion('configuraciones', $configuracion->id, $datosAnteriores, $configuracion->toArray());

            return response()->json([
                'success' => true,
                'data' => $configuracion,
                'message' => 'Configuración actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar configuración
     */
    public function destroy($id): JsonResponse
    {
        try {
            $configuracion = Configuracion::find($id);

            if (!$configuracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración no encontrada'
                ], 404);
            }

            $datosAnteriores = $configuracion->toArray();
            $configuracion->delete();

            // Log de eliminación
            Log::logEliminacion('configuraciones', $configuracion->id, $datosAnteriores);

            return response()->json([
                'success' => true,
                'message' => 'Configuración eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener configuración por clave
     */
    public function obtenerPorClave($clave): JsonResponse
    {
        try {
            $configuracion = Configuracion::where('cla', $clave)->where('est', 'act')->first();

            if (!$configuracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'clave' => $configuracion->cla,
                    'valor' => $configuracion->valor_formateado,
                    'descripcion' => $configuracion->des,
                    'categoria' => $configuracion->cat,
                    'tipo' => $configuracion->tip
                ],
                'message' => 'Configuración obtenida exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar configuración por clave
     */
    public function actualizarPorClave(Request $request, $clave): JsonResponse
    {
        try {
            $configuracion = Configuracion::where('cla', $clave)->first();

            if (!$configuracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración no encontrada'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'valor' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Valor requerido',
                    'errors' => $validator->errors()
                ], 422);
            }

            $datosAnteriores = $configuracion->toArray();
            $configuracion->setValorFormateado($request->valor);

            // Log de actualización
            Log::logActualizacion('configuraciones', $configuracion->id, $datosAnteriores, $configuracion->toArray());

            return response()->json([
                'success' => true,
                'data' => $configuracion,
                'message' => 'Configuración actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener configuraciones por categoría
     */
    public function obtenerPorCategoria($categoria): JsonResponse
    {
        try {
            $configuraciones = Configuracion::where('cat', $categoria)
                ->where('est', 'act')
                ->get()
                ->map(function($config) {
                    return [
                        'clave' => $config->cla,
                        'valor' => $config->valor_formateado,
                        'descripcion' => $config->des,
                        'tipo' => $config->tip
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $configuraciones,
                'message' => 'Configuraciones obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener configuraciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar configuración
     */
    public function activar($id): JsonResponse
    {
        try {
            $configuracion = Configuracion::find($id);

            if (!$configuracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración no encontrada'
                ], 404);
            }

            $configuracion->activar();

            // Log de activación
            Log::crear('activar', 'configuraciones', $configuracion->id, 'Configuración activada');

            return response()->json([
                'success' => true,
                'data' => $configuracion,
                'message' => 'Configuración activada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al activar configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Desactivar configuración
     */
    public function desactivar($id): JsonResponse
    {
        try {
            $configuracion = Configuracion::find($id);

            if (!$configuracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración no encontrada'
                ], 404);
            }

            $configuracion->desactivar();

            // Log de desactivación
            Log::crear('desactivar', 'configuraciones', $configuracion->id, 'Configuración desactivada');

            return response()->json([
                'success' => true,
                'data' => $configuracion,
                'message' => 'Configuración desactivada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar configuraciones
     */
    public function buscar(Request $request): JsonResponse
    {
        try {
            $query = Configuracion::query();

            if ($request->has('termino')) {
                $termino = $request->termino;
                $query->where(function($q) use ($termino) {
                    $q->where('cla', 'like', '%' . $termino . '%')
                      ->orWhere('des', 'like', '%' . $termino . '%')
                      ->orWhere('cat', 'like', '%' . $termino . '%');
                });
            }

            $configuraciones = $query->limit(10)->get();

            return response()->json([
                'success' => true,
                'data' => $configuraciones,
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
