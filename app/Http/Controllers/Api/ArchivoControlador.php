<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Archivo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ArchivoControlador extends Controller
{
    /**
     * Obtener lista paginada de archivos con filtros
     */
    public function ObtenerLista(Request $request): JsonResponse
    {
        try {
            $query = Archivo::with(['cliente', 'operador']);

            // Filtros
            if ($request->filled('tipo')) {
                $query->where('Tipo', $request->tipo);
            }

            if ($request->filled('cliente_id')) {
                $query->where('ClienteId', $request->cliente_id);
            }

            if ($request->filled('operador_id')) {
                $query->where('OperadorId', $request->operador_id);
            }

            if ($request->filled('fecha_inicio')) {
                $query->where('FechaSubida', '>=', $request->fecha_inicio);
            }

            if ($request->filled('fecha_fin')) {
                $query->where('FechaSubida', '<=', $request->fecha_fin);
            }

            if ($request->filled('buscar')) {
                $buscar = $request->buscar;
                $query->where(function ($q) use ($buscar) {
                    $q->where('Nombre', 'like', '%' . $buscar . '%')
                      ->orWhere('Descripcion', 'like', '%' . $buscar . '%')
                      ->orWhere('Codigo', 'like', '%' . $buscar . '%');
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'FechaSubida');
            $direccion = $request->get('direccion', 'desc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $archivos = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $archivos->items(),
                'pagination' => [
                    'current_page' => $archivos->currentPage(),
                    'last_page' => $archivos->lastPage(),
                    'per_page' => $archivos->perPage(),
                    'total' => $archivos->total(),
                ],
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
     * Obtener un archivo específico
     */
    public function ObtenerPorId($id): JsonResponse
    {
        try {
            $archivo = Archivo::with(['cliente', 'operador'])->find($id);

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
     * Subir un nuevo archivo
     */
    public function Subir(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'archivo' => 'required|file|max:10240', // 10MB máximo
                'Nombre' => 'required|string|max:255',
                'Descripcion' => 'sometimes|string|max:1000',
                'Tipo' => 'required|in:documento,imagen,video,audio,otro',
                'ClienteId' => 'sometimes|exists:Clientes,IdCliente',
                'OperadorId' => 'sometimes|exists:Operadores,IdOperador',
                'Categoria' => 'sometimes|string|max:100',
                'Privado' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $archivo = $request->file('archivo');
            $nombreOriginal = $archivo->getClientOriginalName();
            $extension = $archivo->getClientOriginalExtension();
            $tamano = $archivo->getSize();
            $mimeType = $archivo->getMimeType();

            // Generar nombre único para el archivo
            $nombreArchivo = uniqid() . '_' . time() . '.' . $extension;
            
            // Guardar archivo en storage
            $ruta = $archivo->storeAs('archivos', $nombreArchivo, 'public');

            $datos = $request->all();
            $datos['NombreOriginal'] = $nombreOriginal;
            $datos['Extension'] = $extension;
            $datos['Tamano'] = $tamano;
            $datos['MimeType'] = $mimeType;
            $datos['Ruta'] = $ruta;
            $datos['FechaSubida'] = now();
            $datos['Codigo'] = $this->GenerarCodigoArchivo();

            $archivoModel = Archivo::create($datos);

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
     * Actualizar un archivo existente
     */
    public function Actualizar(Request $request, $id): JsonResponse
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
                'Nombre' => 'sometimes|required|string|max:255',
                'Descripcion' => 'sometimes|string|max:1000',
                'Categoria' => 'sometimes|string|max:100',
                'Privado' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $archivo->update($request->all());

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
     * Eliminar un archivo
     */
    public function Eliminar($id): JsonResponse
    {
        try {
            $archivo = Archivo::find($id);

            if (!$archivo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado'
                ], 404);
            }

            // Eliminar archivo físico del storage
            if (Storage::disk('public')->exists($archivo->Ruta)) {
                Storage::disk('public')->delete($archivo->Ruta);
            }

            $archivo->delete();

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
     * Descargar un archivo
     */
    public function Descargar($id): JsonResponse
    {
        try {
            $archivo = Archivo::find($id);

            if (!$archivo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado'
                ], 404);
            }

            // Verificar si el archivo existe físicamente
            if (!Storage::disk('public')->exists($archivo->Ruta)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo físico no existe'
                ], 404);
            }

            // Generar URL de descarga
            $urlDescarga = Storage::disk('public')->url($archivo->Ruta);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $archivo->IdArchivo,
                    'nombre' => $archivo->Nombre,
                    'nombre_original' => $archivo->NombreOriginal,
                    'extension' => $archivo->Extension,
                    'tamano' => $archivo->Tamano,
                    'mime_type' => $archivo->MimeType,
                    'url_descarga' => $urlDescarga,
                    'fecha_subida' => $archivo->FechaSubida
                ],
                'message' => 'Archivo disponible para descarga'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener archivos por cliente
     */
    public function ObtenerPorCliente(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'cliente_id' => 'required|exists:Clientes,IdCliente'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de cliente no válido',
                    'errors' => $validator->errors()
                ], 422);
            }

            $archivos = Archivo::where('ClienteId', $request->cliente_id)
                ->orderBy('FechaSubida', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $archivos,
                'message' => 'Archivos del cliente obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener archivos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de archivos
     */
    public function ObtenerEstadisticas(): JsonResponse
    {
        try {
            $estadisticas = [
                'total' => Archivo::count(),
                'por_tipo' => Archivo::selectRaw('Tipo, COUNT(*) as total')
                    ->groupBy('Tipo')
                    ->get(),
                'por_categoria' => Archivo::selectRaw('Categoria, COUNT(*) as total')
                    ->whereNotNull('Categoria')
                    ->groupBy('Categoria')
                    ->get(),
                'por_mes' => Archivo::selectRaw('MONTH(FechaSubida) as mes, COUNT(*) as total')
                    ->whereYear('FechaSubida', date('Y'))
                    ->groupBy('mes')
                    ->orderBy('mes')
                    ->get(),
                'tamano_total' => Archivo::sum('Tamano'),
                'tamano_promedio' => Archivo::avg('Tamano'),
                'archivos_privados' => Archivo::where('Privado', true)->count(),
                'archivos_publicos' => Archivo::where('Privado', false)->count()
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
     * Generar código único para archivo
     */
    private function GenerarCodigoArchivo(): string
    {
        $prefijo = 'ARC';
        $anio = date('Y');
        $ultimoCodigo = Archivo::whereYear('FechaSubida', $anio)
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
