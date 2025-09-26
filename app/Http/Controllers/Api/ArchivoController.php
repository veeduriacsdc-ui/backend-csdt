<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Archivo;
use App\Models\Usuario;
use App\Models\Veeduria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ArchivoController extends Controller
{
    /**
     * Obtener lista de archivos
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Archivo::with(['usuario', 'veeduria']);

            // Filtros
            if ($request->has('usu_id')) {
                $query->where('usu_id', $request->usu_id);
            }
            if ($request->has('vee_id')) {
                $query->where('vee_id', $request->vee_id);
            }
            if ($request->has('tip')) {
                $query->where('tip', $request->tip);
            }
            if ($request->has('est')) {
                $query->where('est', $request->est);
            }

            // Búsqueda
            if ($request->has('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('nom', 'like', "%{$buscar}%")
                      ->orWhere('des', 'like', "%{$buscar}%");
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'created_at');
            $direccion = $request->get('direccion', 'desc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $porPagina = $request->get('por_pagina', 15);
            $archivos = $query->paginate($porPagina);

            return response()->json([
                'success' => true,
                'data' => [
                    'archivos' => $archivos->items(),
                    'pagination' => [
                        'current_page' => $archivos->currentPage(),
                        'per_page' => $archivos->perPage(),
                        'total' => $archivos->total(),
                        'last_page' => $archivos->lastPage(),
                        'from' => $archivos->firstItem(),
                        'to' => $archivos->lastItem()
                    ]
                ],
                'message' => 'Archivos obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener archivos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener archivos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de archivos
     */
    public function estadisticas(Request $request): JsonResponse
    {
        try {
            $query = Archivo::query();

            // Filtros por fecha
            if ($request->has('fec_ini') && $request->has('fec_fin')) {
                $query->whereBetween('created_at', [$request->fec_ini, $request->fec_fin]);
            }

            $estadisticas = [
                'total_archivos' => Archivo::count(),
                'archivos_activos' => Archivo::where('est', 'act')->count(),
                'archivos_inactivos' => Archivo::where('est', 'ina')->count(),
                'archivos_suspendidos' => Archivo::where('est', 'sus')->count(),
                'tamaño_total' => Archivo::where('est', 'act')->sum('tam'),
                'tamaño_promedio' => Archivo::where('est', 'act')->avg('tam'),
                'tamaño_maximo' => Archivo::where('est', 'act')->max('tam'),
                'tamaño_minimo' => Archivo::where('est', 'act')->min('tam'),
                'por_tipo' => Archivo::selectRaw('tip, COUNT(*) as total, SUM(tam) as tamaño_total')
                    ->groupBy('tip')
                    ->get(),
                'por_estado' => Archivo::selectRaw('est, COUNT(*) as total')
                    ->groupBy('est')
                    ->get(),
                'por_usuario' => Archivo::with('usuario')
                    ->selectRaw('usu_id, COUNT(*) as total, SUM(tam) as tamaño_total')
                    ->groupBy('usu_id')
                    ->orderBy('total', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function($item) {
                        return [
                            'usuario_id' => $item->usu_id,
                            'usuario_nombre' => $item->usuario ? $item->usuario->nom . ' ' . $item->usuario->ape : 'Usuario no encontrado',
                            'total_archivos' => $item->total,
                            'tamaño_total' => $item->tamaño_total
                        ];
                    }),
                'por_veeduria' => Archivo::with('veeduria')
                    ->selectRaw('vee_id, COUNT(*) as total, SUM(tam) as tamaño_total')
                    ->whereNotNull('vee_id')
                    ->groupBy('vee_id')
                    ->orderBy('total', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function($item) {
                        return [
                            'veeduria_id' => $item->vee_id,
                            'veeduria_titulo' => $item->veeduria ? $item->veeduria->tit : 'Veeduría no encontrada',
                            'total_archivos' => $item->total,
                            'tamaño_total' => $item->tamaño_total
                        ];
                    }),
                'por_mes' => Archivo::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as mes, COUNT(*) as total, SUM(tam) as tamaño')
                    ->groupBy('mes')
                    ->orderBy('mes', 'desc')
                    ->limit(12)
                    ->get(),
                'tipos_mas_comunes' => Archivo::selectRaw('tip, COUNT(*) as total')
                    ->groupBy('tip')
                    ->orderBy('total', 'desc')
                    ->limit(10)
                    ->get(),
                'estadisticas_generales' => [
                    'archivos_hoy' => Archivo::whereDate('created_at', today())->count(),
                    'archivos_esta_semana' => Archivo::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    'archivos_este_mes' => Archivo::whereMonth('created_at', now()->month)->count(),
                    'archivos_este_ano' => Archivo::whereYear('created_at', now()->year)->count()
                ],
                'distribucion_por_tamaño' => [
                    'pequenos' => Archivo::where('tam', '<', 1024 * 1024)->count(), // < 1MB
                    'medianos' => Archivo::whereBetween('tam', [1024 * 1024, 10 * 1024 * 1024])->count(), // 1MB - 10MB
                    'grandes' => Archivo::where('tam', '>', 10 * 1024 * 1024)->count() // > 10MB
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas de archivos obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas de archivos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas de archivos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un archivo específico
     */
    public function show($id): JsonResponse
    {
        try {
            $archivo = Archivo::with(['usuario', 'veeduria'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $archivo,
                'message' => 'Archivo obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener archivo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo archivo
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nom' => 'required|string|max:255',
                'tip' => 'required|string|max:50',
                'tam' => 'required|integer|min:1',
                'ruta' => 'required|string|max:500',
                'usu_id' => 'required|integer|exists:usu,id',
                'vee_id' => 'nullable|integer|exists:vee,id',
                'des' => 'nullable|string',
                'mime_type' => 'nullable|string|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $archivo = Archivo::create([
                'nom' => $request->nom,
                'tip' => $request->tip,
                'tam' => $request->tam,
                'ruta' => $request->ruta,
                'usu_id' => $request->usu_id,
                'vee_id' => $request->vee_id,
                'des' => $request->des,
                'mime_type' => $request->mime_type,
                'hash_archivo' => hash_file('sha256', $request->ruta),
                'est' => 'act'
            ]);

            return response()->json([
                'success' => true,
                'data' => $archivo,
                'message' => 'Archivo creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al crear archivo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un archivo
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $archivo = Archivo::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'nom' => 'sometimes|string|max:255',
                'des' => 'sometimes|string',
                'est' => 'sometimes|in:act,ina,sus'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $archivo->update($request->only(['nom', 'des', 'est']));

            return response()->json([
                'success' => true,
                'data' => $archivo,
                'message' => 'Archivo actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al actualizar archivo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un archivo
     */
    public function destroy($id): JsonResponse
    {
        try {
            $archivo = Archivo::findOrFail($id);

            // Eliminar archivo físico si existe
            if (Storage::exists($archivo->ruta)) {
                Storage::delete($archivo->ruta);
            }

            $archivo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Archivo eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar archivo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar archivo: ' . $e->getMessage()
            ], 500);
        }
    }
}