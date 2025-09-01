<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NarracionConsejoIa;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class NarracionConsejoIaControlador extends Controller
{
    /**
     * Obtener lista paginada de narraciones con filtros
     */
    public function ObtenerLista(Request $request): JsonResponse
    {
        try {
            $query = NarracionConsejoIa::with(['cliente']);

            // Filtros
            if ($request->filled('estado')) {
                $query->where('Estado', $request->estado);
            }

            if ($request->filled('cliente_id')) {
                $query->where('ClienteId', $request->cliente_id);
            }

            if ($request->filled('fecha_inicio')) {
                $query->where('FechaCreacion', '>=', $request->fecha_inicio);
            }

            if ($request->filled('fecha_fin')) {
                $query->where('FechaCreacion', '<=', $request->fecha_fin);
            }

            if ($request->filled('buscar')) {
                $buscar = $request->buscar;
                $query->where(function ($q) use ($buscar) {
                    $q->where('Titulo', 'like', '%' . $buscar . '%')
                      ->orWhere('NarracionOriginal', 'like', '%' . $buscar . '%')
                      ->orWhere('NarracionMejorada', 'like', '%' . $buscar . '%');
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'FechaCreacion');
            $direccion = $request->get('direccion', 'desc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $narraciones = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $narraciones->items(),
                'pagination' => [
                    'current_page' => $narraciones->currentPage(),
                    'last_page' => $narraciones->lastPage(),
                    'per_page' => $narraciones->perPage(),
                    'total' => $narraciones->total(),
                ],
                'message' => 'Narraciones obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener narraciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una narración específica
     */
    public function ObtenerPorId($id): JsonResponse
    {
        try {
            $narracion = NarracionConsejoIa::with(['cliente', 'archivos'])->find($id);

            if (!$narracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Narración no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $narracion,
                'message' => 'Narración obtenida exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener narración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear una nueva narración
     */
    public function Crear(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'Titulo' => 'required|string|max:255',
                'NarracionOriginal' => 'required|string|max:5000',
                'ClienteId' => 'required|exists:Clientes,IdCliente',
                'TipoNarracion' => 'required|in:hechos,denuncia,queja,solicitud,informacion',
                'Prioridad' => 'required|in:baja,media,alta,urgente'
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
            $datos['Estado'] = 'pendiente';
            $datos['Codigo'] = $this->GenerarCodigoNarracion();

            $narracion = NarracionConsejoIa::create($datos);

            return response()->json([
                'success' => true,
                'data' => $narracion,
                'message' => 'Narración creada exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear narración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar una narración existente
     */
    public function Actualizar(Request $request, $id): JsonResponse
    {
        try {
            $narracion = NarracionConsejoIa::find($id);

            if (!$narracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Narración no encontrada'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'Titulo' => 'sometimes|required|string|max:255',
                'NarracionOriginal' => 'sometimes|required|string|max:5000',
                'NarracionMejorada' => 'sometimes|string|max:5000',
                'TipoNarracion' => 'sometimes|required|in:hechos,denuncia,queja,solicitud,informacion',
                'Prioridad' => 'sometimes|required|in:baja,media,alta,urgente',
                'Comentarios' => 'sometimes|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $narracion->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $narracion,
                'message' => 'Narración actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar narración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una narración
     */
    public function Eliminar($id): JsonResponse
    {
        try {
            $narracion = NarracionConsejoIa::find($id);

            if (!$narracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Narración no encontrada'
                ], 404);
            }

            // Verificar si tiene archivos asociados
            if ($narracion->archivos()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la narración porque tiene archivos asociados'
                ], 400);
            }

            $narracion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Narración eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar narración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mejorar narración con IA
     */
    public function MejorarConIa(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'NarracionOriginal' => 'required|string|max:5000',
                'TipoMejora' => 'required|in:gramatica,claridad,estructura,completo'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Aquí se implementaría la lógica de IA para mejorar el texto
            // Por ahora simulamos una mejora básica
            $narracionOriginal = $request->NarracionOriginal;
            $tipoMejora = $request->TipoMejora;

            $narracionMejorada = $this->SimularMejoraIA($narracionOriginal, $tipoMejora);

            return response()->json([
                'success' => true,
                'data' => [
                    'narracion_original' => $narracionOriginal,
                    'narracion_mejorada' => $narracionMejorada,
                    'tipo_mejora' => $tipoMejora,
                    'fecha_mejora' => now()->toISOString()
                ],
                'message' => 'Narración mejorada exitosamente con IA'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mejorar narración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar PDF de narración
     */
    public function GenerarPdf(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'narracion_id' => 'required|exists:NarracionesConsejoIa,IdNarracion'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de narración no válido',
                    'errors' => $validator->errors()
                ], 422);
            }

            $narracion = NarracionConsejoIa::with(['cliente'])->find($request->narracion_id);

            // Aquí se implementaría la lógica de generación de PDF
            // Por ahora simulamos la generación
            $pdfData = [
                'id' => $narracion->IdNarracion,
                'codigo' => $narracion->Codigo,
                'titulo' => $narracion->Titulo,
                'fecha_generacion' => now()->toISOString(),
                'estado' => 'generado'
            ];

            return response()->json([
                'success' => true,
                'data' => $pdfData,
                'message' => 'PDF generado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de narraciones
     */
    public function ObtenerEstadisticas(): JsonResponse
    {
        try {
            $estadisticas = [
                'total' => NarracionConsejoIa::count(),
                'por_estado' => NarracionConsejoIa::selectRaw('Estado, COUNT(*) as total')
                    ->groupBy('Estado')
                    ->get(),
                'por_tipo' => NarracionConsejoIa::selectRaw('TipoNarracion, COUNT(*) as total')
                    ->groupBy('TipoNarracion')
                    ->get(),
                'por_prioridad' => NarracionConsejoIa::selectRaw('Prioridad, COUNT(*) as total')
                    ->groupBy('Prioridad')
                    ->get(),
                'por_mes' => NarracionConsejoIa::selectRaw('MONTH(FechaCreacion) as mes, COUNT(*) as total')
                    ->whereYear('FechaCreacion', date('Y'))
                    ->groupBy('mes')
                    ->orderBy('mes')
                    ->get(),
                'mejoradas_con_ia' => NarracionConsejoIa::whereNotNull('NarracionMejorada')->count(),
                'pendientes_mejora' => NarracionConsejoIa::whereNull('NarracionMejorada')->count()
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
     * Simular mejora de IA (placeholder)
     */
    private function SimularMejoraIA(string $texto, string $tipo): string
    {
        // Esta es una simulación básica - en producción se conectaría con un servicio de IA real
        $mejoras = [
            'gramatica' => 'Se han corregido errores gramaticales y ortográficos.',
            'claridad' => 'Se ha mejorado la claridad y legibilidad del texto.',
            'estructura' => 'Se ha reorganizado la estructura para mejor comprensión.',
            'completo' => 'Se ha expandido y completado la información faltante.'
        ];

        $mejora = $mejoras[$tipo] ?? 'Se ha aplicado una mejora general al texto.';
        
        return $texto . "\n\n[MEJORA APLICADA: " . strtoupper($tipo) . "]\n" . $mejora;
    }

    /**
     * Generar código único para narración
     */
    private function GenerarCodigoNarracion(): string
    {
        $prefijo = 'NAR';
        $anio = date('Y');
        $ultimoCodigo = NarracionConsejoIa::whereYear('FechaCreacion', $anio)
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
