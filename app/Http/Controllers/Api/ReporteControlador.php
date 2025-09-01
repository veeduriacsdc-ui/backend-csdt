<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reporte;
use App\Models\Veeduria;
use App\Models\Donacion;
use App\Models\Tarea;
use App\Models\Cliente;
use App\Models\Operador;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ReporteControlador extends Controller
{
    /**
     * Obtener lista paginada de reportes con filtros
     */
    public function ObtenerLista(Request $request): JsonResponse
    {
        try {
            $query = Reporte::with(['cliente', 'operador']);

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

            if ($request->filled('buscar')) {
                $buscar = $request->buscar;
                $query->where(function ($q) use ($buscar) {
                    $q->where('Titulo', 'like', '%' . $buscar . '%')
                      ->orWhere('Descripcion', 'like', '%' . $buscar . '%')
                      ->orWhere('Codigo', 'like', '%' . $buscar . '%');
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'FechaCreacion');
            $direccion = $request->get('direccion', 'desc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $reportes = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $reportes->items(),
                'pagination' => [
                    'current_page' => $reportes->currentPage(),
                    'last_page' => $reportes->lastPage(),
                    'per_page' => $reportes->perPage(),
                    'total' => $reportes->total(),
                ],
                'message' => 'Reportes obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener reportes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un reporte específico
     */
    public function ObtenerPorId($id): JsonResponse
    {
        try {
            $reporte = Reporte::with(['cliente', 'operador'])->find($id);

            if (!$reporte) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reporte no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $reporte,
                'message' => 'Reporte obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo reporte
     */
    public function Crear(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'Titulo' => 'required|string|max:255',
                'Descripcion' => 'required|string|max:1000',
                'Tipo' => 'required|in:veedurias,donaciones,tareas,usuarios,general,personalizado',
                'Formato' => 'required|in:pdf,excel,csv,json',
                'Parametros' => 'sometimes|json',
                'ClienteId' => 'sometimes|exists:Clientes,IdCliente',
                'OperadorId' => 'sometimes|exists:Operadores,IdOperador',
                'Programado' => 'boolean',
                'FechaProgramada' => 'sometimes|date|after:now'
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
            $datos['Codigo'] = $this->GenerarCodigoReporte();

            $reporte = Reporte::create($datos);

            return response()->json([
                'success' => true,
                'data' => $reporte,
                'message' => 'Reporte creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un reporte existente
     */
    public function Actualizar(Request $request, $id): JsonResponse
    {
        try {
            $reporte = Reporte::find($id);

            if (!$reporte) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reporte no encontrado'
                ], 404);
            }

            // Solo se pueden actualizar reportes pendientes o en proceso
            if (!in_array($reporte->Estado, ['pendiente', 'en_proceso'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden actualizar reportes pendientes o en proceso'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'Titulo' => 'sometimes|required|string|max:255',
                'Descripcion' => 'sometimes|required|string|max:1000',
                'Parametros' => 'sometimes|json',
                'FechaProgramada' => 'sometimes|date|after:now'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $reporte->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $reporte,
                'message' => 'Reporte actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un reporte
     */
    public function Eliminar($id): JsonResponse
    {
        try {
            $reporte = Reporte::find($id);

            if (!$reporte) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reporte no encontrado'
                ], 404);
            }

            // Solo se pueden eliminar reportes pendientes
            if ($reporte->Estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden eliminar reportes pendientes'
                ], 400);
            }

            $reporte->delete();

            return response()->json([
                'success' => true,
                'message' => 'Reporte eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar reporte
     */
    public function GenerarReporte(Request $request, $id): JsonResponse
    {
        try {
            $reporte = Reporte::find($id);

            if (!$reporte) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reporte no encontrado'
                ], 404);
            }

            // Actualizar estado a en proceso
            $reporte->update([
                'Estado' => 'en_proceso',
                'FechaInicioGeneracion' => now()
            ]);

            // Generar reporte según el tipo
            $datosReporte = $this->GenerarDatosReporte($reporte);

            // Actualizar reporte con datos generados
            $reporte->update([
                'Estado' => 'completado',
                'FechaCompletado' => now(),
                'Resultado' => json_encode($datosReporte),
                'TamanioArchivo' => strlen(json_encode($datosReporte))
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'reporte' => $reporte,
                    'datos_generados' => $datosReporte
                ],
                'message' => 'Reporte generado exitosamente'
            ]);

        } catch (\Exception $e) {
            // Actualizar estado a error
            if (isset($reporte)) {
                $reporte->update([
                    'Estado' => 'error',
                    'Error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar reporte generado
     */
    public function DescargarReporte($id): JsonResponse
    {
        try {
            $reporte = Reporte::find($id);

            if (!$reporte) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reporte no encontrado'
                ], 404);
            }

            if ($reporte->Estado !== 'completado') {
                return response()->json([
                    'success' => false,
                    'message' => 'El reporte no está listo para descarga'
                ], 400);
            }

            // Aquí se implementaría la lógica de descarga según el formato
            $datosDescarga = [
                'id' => $reporte->IdReporte,
                'codigo' => $reporte->Codigo,
                'titulo' => $reporte->Titulo,
                'tipo' => $reporte->Tipo,
                'formato' => $reporte->Formato,
                'fecha_generacion' => $reporte->FechaCompletado,
                'tamanio' => $reporte->TamanioArchivo,
                'estado' => 'disponible_para_descarga'
            ];

            return response()->json([
                'success' => true,
                'data' => $datosDescarga,
                'message' => 'Reporte disponible para descarga'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener reportes por usuario
     */
    public function ObtenerPorUsuario(Request $request): JsonResponse
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

            $query = Reporte::query();

            if ($request->filled('cliente_id')) {
                $query->where('ClienteId', $request->cliente_id);
            }

            if ($request->filled('operador_id')) {
                $query->where('OperadorId', $request->operador_id);
            }

            $reportes = $query->orderBy('FechaCreacion', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $reportes,
                'message' => 'Reportes del usuario obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener reportes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de reportes
     */
    public function ObtenerEstadisticas(): JsonResponse
    {
        try {
            $estadisticas = [
                'total' => Reporte::count(),
                'por_tipo' => Reporte::selectRaw('Tipo, COUNT(*) as total')
                    ->groupBy('Tipo')
                    ->get(),
                'por_estado' => Reporte::selectRaw('Estado, COUNT(*) as total')
                    ->groupBy('Estado')
                    ->get(),
                'por_formato' => Reporte::selectRaw('Formato, COUNT(*) as total')
                    ->groupBy('Formato')
                    ->get(),
                'por_mes' => Reporte::selectRaw('MONTH(FechaCreacion) as mes, COUNT(*) as total')
                    ->whereYear('FechaCreacion', date('Y'))
                    ->groupBy('mes')
                    ->orderBy('mes')
                    ->get(),
                'completados_hoy' => Reporte::whereDate('FechaCompletado', today())->count(),
                'pendientes' => Reporte::where('Estado', 'pendiente')->count(),
                'en_proceso' => Reporte::where('Estado', 'en_proceso')->count(),
                'completados' => Reporte::where('Estado', 'completado')->count(),
                'con_error' => Reporte::where('Estado', 'error')->count()
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
     * Generar datos del reporte según el tipo
     */
    private function GenerarDatosReporte(Reporte $reporte): array
    {
        $cacheKey = "reporte_{$reporte->Tipo}_{$reporte->IdReporte}";
        
        return Cache::remember($cacheKey, 300, function () use ($reporte) {
            switch ($reporte->Tipo) {
                case 'veedurias':
                    return $this->GenerarReporteVeedurias($reporte);
                case 'donaciones':
                    return $this->GenerarReporteDonaciones($reporte);
                case 'tareas':
                    return $this->GenerarReporteTareas($reporte);
                case 'usuarios':
                    return $this->GenerarReporteUsuarios($reporte);
                case 'general':
                    return $this->GenerarReporteGeneral($reporte);
                default:
                    return ['error' => 'Tipo de reporte no soportado'];
            }
        });
    }

    /**
     * Generar reporte de veedurías
     */
    private function GenerarReporteVeedurias(Reporte $reporte): array
    {
        $query = Veeduria::with(['cliente', 'operador']);

        if ($reporte->Parametros) {
            $parametros = json_decode($reporte->Parametros, true);
            if (isset($parametros['estado'])) {
                $query->where('Estado', $parametros['estado']);
            }
            if (isset($parametros['tipo'])) {
                $query->where('Tipo', $parametros['tipo']);
            }
        }

        $veedurias = $query->get();

        return [
            'tipo' => 'veedurias',
            'total' => $veedurias->count(),
            'por_estado' => $veedurias->groupBy('Estado')->map->count(),
            'por_tipo' => $veedurias->groupBy('Tipo')->map->count(),
            'datos' => $veedurias
        ];
    }

    /**
     * Generar reporte de donaciones
     */
    private function GenerarReporteDonaciones(Reporte $reporte): array
    {
        $query = Donacion::with(['cliente', 'operador']);

        if ($reporte->Parametros) {
            $parametros = json_decode($reporte->Parametros, true);
            if (isset($parametros['estado'])) {
                $query->where('Estado', $parametros['estado']);
            }
            if (isset($parametros['tipo'])) {
                $query->where('Tipo', $parametros['tipo']);
            }
        }

        $donaciones = $query->get();

        return [
            'tipo' => 'donaciones',
            'total' => $donaciones->count(),
            'monto_total' => $donaciones->sum('Monto'),
            'por_estado' => $donaciones->groupBy('Estado')->map->count(),
            'por_tipo' => $donaciones->groupBy('Tipo')->map->count(),
            'datos' => $donaciones
        ];
    }

    /**
     * Generar reporte de tareas
     */
    private function GenerarReporteTareas(Reporte $reporte): array
    {
        $query = Tarea::with(['veeduria', 'operador']);

        if ($reporte->Parametros) {
            $parametros = json_decode($reporte->Parametros, true);
            if (isset($parametros['estado'])) {
                $query->where('Estado', $parametros['estado']);
            }
            if (isset($parametros['prioridad'])) {
                $query->where('Prioridad', $parametros['prioridad']);
            }
        }

        $tareas = $query->get();

        return [
            'tipo' => 'tareas',
            'total' => $tareas->count(),
            'por_estado' => $tareas->groupBy('Estado')->map->count(),
            'por_prioridad' => $tareas->groupBy('Prioridad')->map->count(),
            'datos' => $tareas
        ];
    }

    /**
     * Generar reporte de usuarios
     */
    private function GenerarReporteUsuarios(Reporte $reporte): array
    {
        $clientes = Cliente::count();
        $operadores = Operador::count();

        return [
            'tipo' => 'usuarios',
            'total_clientes' => $clientes,
            'total_operadores' => $operadores,
            'total_usuarios' => $clientes + $operadores,
            'por_tipo' => [
                'clientes' => $clientes,
                'operadores' => $operadores
            ]
        ];
    }

    /**
     * Generar reporte general
     */
    private function GenerarReporteGeneral(Reporte $reporte): array
    {
        return [
            'tipo' => 'general',
            'veedurias' => [
                'total' => Veeduria::count(),
                'pendientes' => Veeduria::where('Estado', 'pendiente')->count(),
                'en_proceso' => Veeduria::where('Estado', 'en_proceso')->count(),
                'completadas' => Veeduria::where('Estado', 'completada')->count()
            ],
            'donaciones' => [
                'total' => Donacion::count(),
                'pendientes' => Donacion::where('Estado', 'pendiente')->count(),
                'validadas' => Donacion::where('Estado', 'validada')->count(),
                'monto_total' => Donacion::where('Estado', 'validada')->sum('Monto')
            ],
            'tareas' => [
                'total' => Tarea::count(),
                'pendientes' => Tarea::where('Estado', 'pendiente')->count(),
                'en_proceso' => Tarea::where('Estado', 'en_proceso')->count(),
                'completadas' => Tarea::where('Estado', 'completada')->count()
            ],
            'usuarios' => [
                'clientes' => Cliente::count(),
                'operadores' => Operador::count()
            ]
        ];
    }

    /**
     * Generar código único para reporte
     */
    private function GenerarCodigoReporte(): string
    {
        $prefijo = 'REP';
        $anio = date('Y');
        $ultimoCodigo = Reporte::whereYear('FechaCreacion', $anio)
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
