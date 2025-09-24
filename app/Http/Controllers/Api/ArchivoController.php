<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Archivo;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ArchivoController extends Controller
{
    /**
     * Obtener lista de archivos
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Archivo::with(['usuario', 'veeduria', 'tarea']);

            // Filtros
            if ($request->has('est')) {
                $query->where('est', $request->est);
            }
            if ($request->has('tip')) {
                $query->where('tip', $request->tip);
            }
            if ($request->has('usu_id')) {
                $query->where('usu_id', $request->usu_id);
            }
            if ($request->has('vee_id')) {
                $query->where('vee_id', $request->vee_id);
            }
            if ($request->has('tar_id')) {
                $query->where('tar_id', $request->tar_id);
            }

            // Filtros especiales
            if ($request->has('imagenes')) {
                $query->imagenes();
            }
            if ($request->has('documentos')) {
                $query->documentos();
            }
            if ($request->has('videos')) {
                $query->videos();
            }
            if ($request->has('audios')) {
                $query->audios();
            }

            // Búsqueda
            if ($request->has('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('nom', 'like', '%' . $buscar . '%')
                      ->orWhere('nom_ori', 'like', '%' . $buscar . '%')
                      ->orWhere('des', 'like', '%' . $buscar . '%');
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
                'data' => $archivos,
                'message' => 'Archivos obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener archivos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener archivo por ID
     */
    public function show($id): JsonResponse
    {
        try {
            $archivo = Archivo::with(['usuario', 'veeduria', 'tarea'])->find($id);

            if (!$archivo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $archivo,
                'message' => 'Archivo obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Subir archivo
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'archivo' => 'required|file|max:10240', // 10MB máximo
                'usu_id' => 'required|exists:usuarios,id',
                'vee_id' => 'nullable|exists:veedurias,id',
                'tar_id' => 'nullable|exists:tareas,id',
                'des' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $archivo = $request->file('archivo');
            $nombreOriginal = $archivo->getClientOriginalName();
            $extension = $archivo->getClientOriginalExtension();
            $tamaño = $archivo->getSize();
            
            // Generar nombre único
            $nombreUnico = time() . '_' . uniqid() . '.' . $extension;
            
            // Subir archivo
            $ruta = $archivo->storeAs('archivos', $nombreUnico, 'public');

            // Crear registro en base de datos
            $archivoModel = Archivo::create([
                'usu_id' => $request->usu_id,
                'vee_id' => $request->vee_id,
                'tar_id' => $request->tar_id,
                'nom' => $nombreUnico,
                'nom_ori' => $nombreOriginal,
                'ruta' => $ruta,
                'tip' => $extension,
                'tam' => $tamaño,
                'des' => $request->des,
                'est' => 'act'
            ]);

            // Log de subida
            Log::crear('subir_archivo', 'archivos', $archivoModel->id, 'Archivo subido: ' . $nombreOriginal);

            return response()->json([
                'success' => true,
                'data' => $archivoModel,
                'message' => 'Archivo subido exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar archivo
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $archivo = Archivo::find($id);

            if (!$archivo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'des' => 'nullable|string',
                'est' => 'sometimes|in:act,eli,err',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $datosAnteriores = $archivo->toArray();
            $archivo->update($request->all());

            // Log de actualización
            Log::logActualizacion('archivos', $archivo->id, $datosAnteriores, $archivo->toArray());

            return response()->json([
                'success' => true,
                'data' => $archivo,
                'message' => 'Archivo actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar archivo (soft delete)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $archivo = Archivo::find($id);

            if (!$archivo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado'
                ], 404);
            }

            $datosAnteriores = $archivo->toArray();
            
            // Marcar como eliminado en base de datos
            $archivo->marcarComoEliminado();

            // Log de eliminación
            Log::crear('eliminar_archivo', 'archivos', $archivo->id, 'Archivo eliminado: ' . $archivo->nom_ori);

            return response()->json([
                'success' => true,
                'message' => 'Archivo eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurar archivo
     */
    public function restore($id): JsonResponse
    {
        try {
            $archivo = Archivo::withTrashed()->find($id);

            if (!$archivo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado'
                ], 404);
            }

            $archivo->restaurar();

            // Log de restauración
            Log::logRestauracion('archivos', $archivo->id);

            return response()->json([
                'success' => true,
                'data' => $archivo,
                'message' => 'Archivo restaurado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar archivo
     */
    public function descargar($id): JsonResponse
    {
        try {
            $archivo = Archivo::find($id);

            if (!$archivo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado'
                ], 404);
            }

            if (!Storage::disk('public')->exists($archivo->ruta)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado en el servidor'
                ], 404);
            }

            // Log de descarga
            Log::crear('descargar_archivo', 'archivos', $archivo->id, 'Archivo descargado: ' . $archivo->nom_ori);

            return response()->download(
                storage_path('app/public/' . $archivo->ruta),
                $archivo->nom_ori
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar archivo: ' . $e->getMessage()
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

            // Filtros por usuario
            if ($request->has('usu_id')) {
                $query->where('usu_id', $request->usu_id);
            }

            // Filtros por veeduría
            if ($request->has('vee_id')) {
                $query->where('vee_id', $request->vee_id);
            }

            // Filtros por tarea
            if ($request->has('tar_id')) {
                $query->where('tar_id', $request->tar_id);
            }

            $estadisticas = [
                'total_archivos' => $query->count(),
                'archivos_activos' => $query->where('est', 'act')->count(),
                'archivos_eliminados' => $query->where('est', 'eli')->count(),
                'archivos_con_error' => $query->where('est', 'err')->count(),
                'tamaño_total' => $query->where('est', 'act')->sum('tam'),
                'tamaño_promedio' => $query->where('est', 'act')->avg('tam'),
                'por_tipo' => $query->selectRaw('tip, COUNT(*) as total')
                    ->groupBy('tip')
                    ->get(),
                'por_estado' => $query->selectRaw('est, COUNT(*) as total')
                    ->groupBy('est')
                    ->get(),
                'imagenes' => $query->imagenes()->count(),
                'documentos' => $query->documentos()->count(),
                'videos' => $query->videos()->count(),
                'audios' => $query->audios()->count(),
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
     * Buscar archivos
     */
    public function buscar(Request $request): JsonResponse
    {
        try {
            $query = Archivo::with(['usuario', 'veeduria', 'tarea']);

            if ($request->has('termino')) {
                $termino = $request->termino;
                $query->where(function($q) use ($termino) {
                    $q->where('nom', 'like', '%' . $termino . '%')
                      ->orWhere('nom_ori', 'like', '%' . $termino . '%')
                      ->orWhere('des', 'like', '%' . $termino . '%');
                });
            }

            $archivos = $query->limit(10)->get();

            return response()->json([
                'success' => true,
                'data' => $archivos,
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
