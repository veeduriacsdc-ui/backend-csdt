<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ConfiguracionControlador extends Controller
{
    /**
     * Obtener lista paginada de configuraciones con filtros
     */
    public function ObtenerLista(Request $request): JsonResponse
    {
        try {
            $query = Configuracion::query();

            // Filtros
            if ($request->filled('categoria')) {
                $query->where('Categoria', $request->categoria);
            }

            if ($request->filled('activa')) {
                $query->where('Activa', $request->activa);
            }

            if ($request->filled('buscar')) {
                $buscar = $request->buscar;
                $query->where(function ($q) use ($buscar) {
                    $q->where('Clave', 'like', '%' . $buscar . '%')
                      ->orWhere('Descripcion', 'like', '%' . $buscar . '%')
                      ->orWhere('Categoria', 'like', '%' . $buscar . '%');
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'Categoria');
            $direccion = $request->get('direccion', 'asc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $configuraciones = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $configuraciones->items(),
                'pagination' => [
                    'current_page' => $configuraciones->currentPage(),
                    'last_page' => $configuraciones->lastPage(),
                    'per_page' => $configuraciones->perPage(),
                    'total' => $configuraciones->total(),
                ],
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
     * Obtener una configuración específica
     */
    public function ObtenerPorId($id): JsonResponse
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
     * Obtener configuración por clave
     */
    public function ObtenerPorClave(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'clave' => 'required|string|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Clave no válida',
                    'errors' => $validator->errors()
                ], 422);
            }

            $configuracion = Configuracion::where('Clave', $request->clave)
                ->where('Activa', true)
                ->first();

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
     * Crear una nueva configuración
     */
    public function Crear(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'Clave' => 'required|string|max:100|unique:Configuraciones,Clave',
                'Valor' => 'required|string|max:1000',
                'Descripcion' => 'required|string|max:500',
                'Categoria' => 'required|string|max:100',
                'Tipo' => 'required|in:string,integer,float,boolean,json,array',
                'Activa' => 'boolean',
                'Editable' => 'boolean',
                'Validacion' => 'sometimes|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $datos = $request->all();
            $datos['FechaCreacion'] = now();
            $datos['Activa'] = $request->get('Activa', true);
            $datos['Editable'] = $request->get('Editable', true);

            $configuracion = Configuracion::create($datos);

            // Limpiar cache de configuraciones
            Cache::forget('configuracion_' . $configuracion->Clave);
            Cache::forget('configuraciones_categoria_' . $configuracion->Categoria);

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
     * Actualizar una configuración existente
     */
    public function Actualizar(Request $request, $id): JsonResponse
    {
        try {
            $configuracion = Configuracion::find($id);

            if (!$configuracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración no encontrada'
                ], 404);
            }

            // Verificar si la configuración es editable
            if (!$configuracion->Editable) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta configuración no es editable'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'Valor' => 'sometimes|required|string|max:1000',
                'Descripcion' => 'sometimes|required|string|max:500',
                'Categoria' => 'sometimes|required|string|max:100',
                'Tipo' => 'sometimes|required|in:string,integer,float,boolean,json,array',
                'Activa' => 'sometimes|boolean',
                'Validacion' => 'sometimes|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $configuracion->update($request->all());

            // Limpiar cache de configuraciones
            Cache::forget('configuracion_' . $configuracion->Clave);
            Cache::forget('configuraciones_categoria_' . $configuracion->Categoria);

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
     * Eliminar una configuración
     */
    public function Eliminar($id): JsonResponse
    {
        try {
            $configuracion = Configuracion::find($id);

            if (!$configuracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración no encontrada'
                ], 404);
            }

            // Verificar si la configuración es editable
            if (!$configuracion->Editable) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta configuración no se puede eliminar'
                ], 400);
            }

            $configuracion->delete();

            // Limpiar cache de configuraciones
            Cache::forget('configuracion_' . $configuracion->Clave);
            Cache::forget('configuraciones_categoria_' . $configuracion->Categoria);

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
     * Obtener configuraciones por categoría
     */
    public function ObtenerPorCategoria(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'categoria' => 'required|string|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no válida',
                    'errors' => $validator->errors()
                ], 422);
            }

            $cacheKey = 'configuraciones_categoria_' . $request->categoria;
            
            $configuraciones = Cache::remember($cacheKey, 3600, function () use ($request) {
                return Configuracion::where('Categoria', $request->categoria)
                    ->where('Activa', true)
                    ->orderBy('Orden', 'asc')
                    ->get();
            });

            return response()->json([
                'success' => true,
                'data' => $configuraciones,
                'message' => 'Configuraciones de la categoría obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener configuraciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todas las configuraciones del sistema
     */
    public function ObtenerSistema(): JsonResponse
    {
        try {
            $cacheKey = 'configuraciones_sistema';
            
            $configuraciones = Cache::remember($cacheKey, 3600, function () {
                return Configuracion::where('Activa', true)
                    ->orderBy('Categoria', 'asc')
                    ->orderBy('Orden', 'asc')
                    ->get()
                    ->groupBy('Categoria');
            });

            return response()->json([
                'success' => true,
                'data' => $configuraciones,
                'message' => 'Configuraciones del sistema obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener configuraciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar múltiples configuraciones
     */
    public function ActualizarMultiples(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'configuraciones' => 'required|array',
                'configuraciones.*.id' => 'required|exists:Configuraciones,IdConfiguracion',
                'configuraciones.*.valor' => 'required|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $configuracionesActualizadas = [];
            $cacheKeys = [];

            foreach ($request->configuraciones as $config) {
                $configuracion = Configuracion::find($config['id']);
                
                if ($configuracion && $configuracion->Editable) {
                    $configuracion->update(['Valor' => $config['valor']]);
                    $configuracionesActualizadas[] = $configuracion;
                    
                    // Agregar claves de cache para limpiar
                    $cacheKeys[] = 'configuracion_' . $configuracion->Clave;
                    $cacheKeys[] = 'configuraciones_categoria_' . $configuracion->Categoria;
                }
            }

            // Limpiar cache
            foreach (array_unique($cacheKeys) as $key) {
                Cache::forget($key);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'configuraciones_actualizadas' => count($configuracionesActualizadas),
                    'configuraciones' => $configuracionesActualizadas
                ],
                'message' => 'Configuraciones actualizadas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar configuraciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de configuraciones
     */
    public function ObtenerEstadisticas(): JsonResponse
    {
        try {
            $estadisticas = [
                'total' => Configuracion::count(),
                'activas' => Configuracion::where('Activa', true)->count(),
                'inactivas' => Configuracion::where('Activa', false)->count(),
                'editables' => Configuracion::where('Editable', true)->count(),
                'no_editables' => Configuracion::where('Editable', false)->count(),
                'por_categoria' => Configuracion::selectRaw('Categoria, COUNT(*) as total')
                    ->groupBy('Categoria')
                    ->get(),
                'por_tipo' => Configuracion::selectRaw('Tipo, COUNT(*) as total')
                    ->groupBy('Tipo')
                    ->get(),
                'ultimas_actualizaciones' => Configuracion::orderBy('FechaActualizacion', 'desc')
                    ->limit(10)
                    ->get(['Clave', 'Categoria', 'FechaActualizacion'])
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
     * Limpiar cache de configuraciones
     */
    public function LimpiarCache(): JsonResponse
    {
        try {
            // Obtener todas las configuraciones para limpiar sus caches
            $configuraciones = Configuracion::all();
            
            foreach ($configuraciones as $config) {
                Cache::forget('configuracion_' . $config->Clave);
                Cache::forget('configuraciones_categoria_' . $config->Categoria);
            }
            
            // Limpiar cache general del sistema
            Cache::forget('configuraciones_sistema');

            return response()->json([
                'success' => true,
                'data' => [
                    'configuraciones_procesadas' => $configuraciones->count(),
                    'cache_limpiado' => true
                ],
                'message' => 'Cache de configuraciones limpiado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar configuraciones
     */
    public function Exportar(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'formato' => 'required|in:json,csv',
                'categoria' => 'sometimes|string|max:100',
                'incluir_inactivas' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parámetros no válidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Configuracion::query();

            if ($request->filled('categoria')) {
                $query->where('Categoria', $request->categoria);
            }

            if (!$request->get('incluir_inactivas', false)) {
                $query->where('Activa', true);
            }

            $configuraciones = $query->orderBy('Categoria', 'asc')
                ->orderBy('Orden', 'asc')
                ->get();

            $datosExportacion = [
                'formato' => $request->formato,
                'categoria' => $request->categoria ?? 'todas',
                'total_registros' => $configuraciones->count(),
                'fecha_exportacion' => now()->toISOString(),
                'configuraciones' => $configuraciones,
                'estado' => 'exportado'
            ];

            return response()->json([
                'success' => true,
                'data' => $datosExportacion,
                'message' => 'Configuraciones exportadas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar configuraciones: ' . $e->getMessage()
            ], 500);
        }
    }
}
