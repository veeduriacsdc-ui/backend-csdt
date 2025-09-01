<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Models\Cliente;
use App\Models\Operador;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class NotificacionControlador extends Controller
{
    /**
     * Obtener lista paginada de notificaciones con filtros
     */
    public function ObtenerLista(Request $request): JsonResponse
    {
        try {
            $query = Notificacion::with(['cliente', 'operador']);

            // Filtros
            if ($request->filled('tipo')) {
                $query->where('Tipo', $request->tipo);
            }

            if ($request->filled('estado')) {
                $query->where('Estado', $request->estado);
            }

            if ($request->filled('cliente_id')) {
                $query->where('ClienteId', $request->cliente_id);
            }

            if ($request->filled('operador_id')) {
                $query->where('OperadorId', $request->operador_id);
            }

            if ($request->filled('fecha_inicio')) {
                $query->where('FechaCreacion', '>=', $request->fecha_inicio);
            }

            if ($request->filled('fecha_fin')) {
                $query->where('FechaCreacion', '<=', $request->fecha_fin);
            }

            if ($request->filled('leida')) {
                $query->where('Leida', $request->leida);
            }

            if ($request->filled('buscar')) {
                $buscar = $request->buscar;
                $query->where(function ($q) use ($buscar) {
                    $q->where('Titulo', 'like', '%' . $buscar . '%')
                      ->orWhere('Mensaje', 'like', '%' . $buscar . '%');
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'FechaCreacion');
            $direccion = $request->get('direccion', 'desc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $notificaciones = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $notificaciones->items(),
                'pagination' => [
                    'current_page' => $notificaciones->currentPage(),
                    'last_page' => $notificaciones->lastPage(),
                    'per_page' => $notificaciones->perPage(),
                    'total' => $notificaciones->total(),
                ],
                'message' => 'Notificaciones obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener notificaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una notificación específica
     */
    public function ObtenerPorId($id): JsonResponse
    {
        try {
            $notificacion = Notificacion::with(['cliente', 'operador'])->find($id);

            if (!$notificacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notificación no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $notificacion,
                'message' => 'Notificación obtenida exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener notificación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear una nueva notificación
     */
    public function Crear(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'Titulo' => 'required|string|max:255',
                'Mensaje' => 'required|string|max:1000',
                'Tipo' => 'required|in:info,success,warning,error,urgente',
                'Prioridad' => 'required|in:baja,media,alta,urgente',
                'ClienteId' => 'sometimes|exists:Clientes,IdCliente',
                'OperadorId' => 'sometimes|exists:Operadores,IdOperador',
                'Enlace' => 'sometimes|url|max:500',
                'DatosAdicionales' => 'sometimes|json'
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
            $datos['Estado'] = 'activa';
            $datos['Leida'] = false;
            $datos['Codigo'] = $this->GenerarCodigoNotificacion();

            $notificacion = Notificacion::create($datos);

            return response()->json([
                'success' => true,
                'data' => $notificacion,
                'message' => 'Notificación creada exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear notificación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar una notificación existente
     */
    public function Actualizar(Request $request, $id): JsonResponse
    {
        try {
            $notificacion = Notificacion::find($id);

            if (!$notificacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notificación no encontrada'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'Titulo' => 'sometimes|required|string|max:255',
                'Mensaje' => 'sometimes|required|string|max:1000',
                'Tipo' => 'sometimes|required|in:info,success,warning,error,urgente',
                'Prioridad' => 'sometimes|required|in:baja,media,alta,urgente',
                'Enlace' => 'sometimes|url|max:500',
                'DatosAdicionales' => 'sometimes|json'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $notificacion->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $notificacion,
                'message' => 'Notificación actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar notificación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una notificación
     */
    public function Eliminar($id): JsonResponse
    {
        try {
            $notificacion = Notificacion::find($id);

            if (!$notificacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notificación no encontrada'
                ], 404);
            }

            $notificacion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notificación eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar notificación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar notificación como leída
     */
    public function MarcarComoLeida($id): JsonResponse
    {
        try {
            $notificacion = Notificacion::find($id);

            if (!$notificacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notificación no encontrada'
                ], 404);
            }

            $notificacion->update([
                'Leida' => true,
                'FechaLectura' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => $notificacion,
                'message' => 'Notificación marcada como leída'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar notificación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar múltiples notificaciones como leídas
     */
    public function MarcarMultiplesComoLeidas(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:Notificaciones,IdNotificacion'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'IDs de notificaciones no válidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $notificaciones = Notificacion::whereIn('IdNotificacion', $request->ids)->get();
            
            foreach ($notificaciones as $notificacion) {
                $notificacion->update([
                    'Leida' => true,
                    'FechaLectura' => now()
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'notificaciones_actualizadas' => $notificaciones->count(),
                    'ids' => $request->ids
                ],
                'message' => 'Notificaciones marcadas como leídas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar notificaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener notificaciones por usuario
     */
    public function ObtenerPorUsuario(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'cliente_id' => 'sometimes|exists:Clientes,IdCliente',
                'operador_id' => 'sometimes|exists:Operadores,IdOperador',
                'no_leidas' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parámetros no válidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Notificacion::query();

            if ($request->filled('cliente_id')) {
                $query->where('ClienteId', $request->cliente_id);
            }

            if ($request->filled('operador_id')) {
                $query->where('OperadorId', $request->operador_id);
            }

            if ($request->filled('no_leidas') && $request->no_leidas) {
                $query->where('Leida', false);
            }

            $notificaciones = $query->orderBy('FechaCreacion', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $notificaciones,
                'message' => 'Notificaciones del usuario obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener notificaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener contador de notificaciones no leídas
     */
    public function ObtenerContadorNoLeidas(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'cliente_id' => 'sometimes|exists:Clientes,IdCliente',
                'operador_id' => 'sometimes|exists:Operadores,IdOperador'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parámetros no válidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Notificacion::where('Leida', false);

            if ($request->filled('cliente_id')) {
                $query->where('ClienteId', $request->cliente_id);
            }

            if ($request->filled('operador_id')) {
                $query->where('OperadorId', $request->operador_id);
            }

            $contador = $query->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'contador_no_leidas' => $contador
                ],
                'message' => 'Contador obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener contador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de notificaciones
     */
    public function ObtenerEstadisticas(): JsonResponse
    {
        try {
            $estadisticas = [
                'total' => Notificacion::count(),
                'por_tipo' => Notificacion::selectRaw('Tipo, COUNT(*) as total')
                    ->groupBy('Tipo')
                    ->get(),
                'por_prioridad' => Notificacion::selectRaw('Prioridad, COUNT(*) as total')
                    ->groupBy('Prioridad')
                    ->get(),
                'por_estado' => Notificacion::selectRaw('Estado, COUNT(*) as total')
                    ->groupBy('Estado')
                    ->get(),
                'leidas' => Notificacion::where('Leida', true)->count(),
                'no_leidas' => Notificacion::where('Leida', false)->count(),
                'por_mes' => Notificacion::selectRaw('MONTH(FechaCreacion) as mes, COUNT(*) as total')
                    ->whereYear('FechaCreacion', date('Y'))
                    ->groupBy('mes')
                    ->orderBy('mes')
                    ->get()
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
     * Generar código único para notificación
     */
    private function GenerarCodigoNotificacion(): string
    {
        $prefijo = 'NOT';
        $anio = date('Y');
        $ultimoCodigo = Notificacion::whereYear('FechaCreacion', $anio)
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
