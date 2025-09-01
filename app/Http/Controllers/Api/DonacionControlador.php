<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donacion;
use App\Models\Cliente;
use App\Models\Operador;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class DonacionControlador extends Controller
{
    /**
     * Obtener lista paginada de donaciones con filtros
     */
    public function ObtenerLista(Request $request): JsonResponse
    {
        try {
            $query = Donacion::with(['cliente', 'operador']);

            // Filtros
            if ($request->filled('estado')) {
                $query->where('Estado', $request->estado);
            }

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
                $query->where('FechaDonacion', '>=', $request->fecha_inicio);
            }

            if ($request->filled('fecha_fin')) {
                $query->where('FechaDonacion', '<=', $request->fecha_fin);
            }

            if ($request->filled('monto_minimo')) {
                $query->where('Monto', '>=', $request->monto_minimo);
            }

            if ($request->filled('monto_maximo')) {
                $query->where('Monto', '<=', $request->monto_maximo);
            }

            if ($request->filled('buscar')) {
                $buscar = $request->buscar;
                $query->where(function ($q) use ($buscar) {
                    $q->where('Descripcion', 'like', '%' . $buscar . '%')
                      ->orWhere('Motivo', 'like', '%' . $buscar . '%')
                      ->orWhere('Codigo', 'like', '%' . $buscar . '%');
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'FechaDonacion');
            $direccion = $request->get('direccion', 'desc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $donaciones = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $donaciones->items(),
                'pagination' => [
                    'current_page' => $donaciones->currentPage(),
                    'last_page' => $donaciones->lastPage(),
                    'per_page' => $donaciones->perPage(),
                    'total' => $donaciones->total(),
                ],
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
     * Obtener una donación específica
     */
    public function ObtenerPorId($id): JsonResponse
    {
        try {
            $donacion = Donacion::with(['cliente', 'operador', 'archivos'])->find($id);

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
     * Crear una nueva donación
     */
    public function Crear(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'Monto' => 'required|numeric|min:0.01',
                'Tipo' => 'required|in:monetaria,bienes,servicios,otro',
                'Descripcion' => 'required|string|max:1000',
                'Motivo' => 'required|string|max:500',
                'ClienteId' => 'required|exists:Clientes,IdCliente',
                'MetodoPago' => 'required|in:efectivo,transferencia,cheque,tarjeta,otro',
                'Anonima' => 'boolean',
                'Comentarios' => 'sometimes|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $datos = $request->all();
            $datos['FechaDonacion'] = now();
            $datos['Estado'] = 'pendiente';
            $datos['Codigo'] = $this->GenerarCodigoDonacion();

            $donacion = Donacion::create($datos);

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
     * Actualizar una donación existente
     */
    public function Actualizar(Request $request, $id): JsonResponse
    {
        try {
            $donacion = Donacion::find($id);

            if (!$donacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donación no encontrada'
                ], 404);
            }

            // Solo se pueden actualizar donaciones pendientes
            if ($donacion->Estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden actualizar donaciones pendientes'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'Monto' => 'sometimes|required|numeric|min:0.01',
                'Tipo' => 'sometimes|required|in:monetaria,bienes,servicios,otro',
                'Descripcion' => 'sometimes|required|string|max:1000',
                'Motivo' => 'sometimes|required|string|max:500',
                'MetodoPago' => 'sometimes|required|in:efectivo,transferencia,cheque,tarjeta,otro',
                'Anonima' => 'sometimes|boolean',
                'Comentarios' => 'sometimes|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $donacion->update($request->all());

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
     * Eliminar una donación
     */
    public function Eliminar($id): JsonResponse
    {
        try {
            $donacion = Donacion::find($id);

            if (!$donacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donación no encontrada'
                ], 404);
            }

            // Solo se pueden eliminar donaciones pendientes
            if ($donacion->Estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden eliminar donaciones pendientes'
                ], 400);
            }

            $donacion->delete();

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
     * Validar donación (operador)
     */
    public function ValidarDonacion(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'Estado' => 'required|in:validada,rechazada',
                'Comentarios' => 'required|string|max:1000',
                'OperadorId' => 'required|exists:Operadores,IdOperador'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $donacion = Donacion::find($id);

            if (!$donacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donación no encontrada'
                ], 404);
            }

            if ($donacion->Estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden validar donaciones pendientes'
                ], 400);
            }

            $donacion->update([
                'Estado' => $request->Estado,
                'OperadorId' => $request->OperadorId,
                'FechaValidacion' => now(),
                'ComentariosValidacion' => $request->Comentarios
            ]);

            return response()->json([
                'success' => true,
                'data' => $donacion,
                'message' => "Donación {$request->Estado} exitosamente"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al validar donación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar donación validada
     */
    public function DescargarDonacion($id): JsonResponse
    {
        try {
            $donacion = Donacion::with(['cliente', 'operador'])->find($id);

            if (!$donacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donación no encontrada'
                ], 404);
            }

            if ($donacion->Estado !== 'validada') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden descargar donaciones validadas'
                ], 400);
            }

            // Aquí se implementaría la lógica de descarga
            // Por ahora simulamos la descarga
            $datosDescarga = [
                'id' => $donacion->IdDonacion,
                'codigo' => $donacion->Codigo,
                'monto' => $donacion->Monto,
                'tipo' => $donacion->Tipo,
                'descripcion' => $donacion->Descripcion,
                'fecha_donacion' => $donacion->FechaDonacion,
                'fecha_validacion' => $donacion->FechaValidacion,
                'cliente' => $donacion->cliente ? $donacion->cliente->Nombres . ' ' . $donacion->cliente->Apellidos : 'Anónimo',
                'operador' => $donacion->operador ? $donacion->operador->Nombres . ' ' . $donacion->operador->Apellidos : 'N/A',
                'estado' => 'disponible_para_descarga'
            ];

            return response()->json([
                'success' => true,
                'data' => $datosDescarga,
                'message' => 'Donación disponible para descarga'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar donación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de donaciones
     */
    public function ObtenerEstadisticas(): JsonResponse
    {
        try {
            $estadisticas = [
                'total' => Donacion::count(),
                'por_estado' => Donacion::selectRaw('Estado, COUNT(*) as total')
                    ->groupBy('Estado')
                    ->get(),
                'por_tipo' => Donacion::selectRaw('Tipo, COUNT(*) as total')
                    ->groupBy('Tipo')
                    ->get(),
                'por_mes' => Donacion::selectRaw('MONTH(FechaDonacion) as mes, COUNT(*) as total')
                    ->whereYear('FechaDonacion', date('Y'))
                    ->groupBy('mes')
                    ->orderBy('mes')
                    ->get(),
                'monto_total' => Donacion::where('Estado', 'validada')->sum('Monto'),
                'monto_promedio' => Donacion::where('Estado', 'validada')->avg('Monto'),
                'donaciones_anonimas' => Donacion::where('Anonima', true)->count(),
                'donaciones_identificadas' => Donacion::where('Anonima', false)->count()
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
     * Generar código único para donación
     */
    private function GenerarCodigoDonacion(): string
    {
        $prefijo = 'DON';
        $anio = date('Y');
        $ultimoCodigo = Donacion::whereYear('FechaDonacion', $anio)
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
