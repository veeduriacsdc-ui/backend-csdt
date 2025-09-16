<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Operador;
use App\Models\Tarea;
use App\Models\Proyecto;
use App\Models\PQRSFD;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class OperadorController extends Controller
{
    /**
     * Obtener lista de operadores
     */
    public function obtenerLista(Request $request): JsonResponse
    {
        try {
            $query = Operador::with(['usuarioSistema', 'supervisor', 'subordinados']);

            // Filtros
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('departamento')) {
                $query->where('departamento', $request->departamento);
            }

            if ($request->has('especialidad')) {
                $query->where('especialidad', $request->especialidad);
            }

            if ($request->has('activo')) {
                $query->where('activo', $request->boolean('activo'));
            }

            // Búsqueda
            if ($request->has('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('nombres', 'like', "%{$buscar}%")
                      ->orWhere('apellidos', 'like', "%{$buscar}%")
                      ->orWhere('correo', 'like', "%{$buscar}%")
                      ->orWhere('documento', 'like', "%{$buscar}%");
                });
            }

            // Paginación
            $perPage = $request->get('per_page', 15);
            $operadores = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $operadores,
                'message' => 'Lista de operadores obtenida exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener lista de operadores: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener operador por ID
     */
    public function obtenerPorId($id): JsonResponse
    {
        try {
            $operador = Operador::with([
                'usuarioSistema',
                'supervisor',
                'subordinados',
                'pqrsfdAsignadas',
                'actividadesCaso',
                'tareas',
                'clientesAsignados'
            ])->find($id);

            if (!$operador) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operador no encontrado'
                ], 404);
            }

            // Agregar estadísticas
            $operador->estadisticas = $operador->obtenerEstadisticas();
            $operador->carga_trabajo = $operador->obtenerCargaTrabajo();

            return response()->json([
                'success' => true,
                'data' => $operador,
                'message' => 'Operador obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener operador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo operador
     */
    public function crear(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'usuario_sistema_id' => 'required|exists:usuarios_sistema,id',
                'nombres' => 'required|string|max:100',
                'apellidos' => 'required|string|max:100',
                'correo' => 'required|email|unique:operadores,correo',
                'telefono' => 'required|string|max:20',
                'documento' => 'required|string|unique:operadores,documento',
                'tipo_documento' => 'required|in:CC,CE,TI,RC,PA',
                'departamento' => 'required|string|max:100',
                'ciudad' => 'required|string|max:100',
                'direccion' => 'required|string',
                'fecha_nacimiento' => 'required|date',
                'fecha_ingreso' => 'required|date',
                'supervisor_id' => 'nullable|exists:operadores,id',
                'especialidad' => 'required|string|max:100',
                'nivel_experiencia' => 'required|integer|min:1|max:5',
                'certificaciones' => 'nullable|array',
                'observaciones' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $operador = Operador::create($request->all());

            return response()->json([
                'success' => true,
                'data' => $operador,
                'message' => 'Operador creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear operador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar operador
     */
    public function actualizar(Request $request, $id): JsonResponse
    {
        try {
            $operador = Operador::find($id);

            if (!$operador) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operador no encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nombres' => 'sometimes|required|string|max:100',
                'apellidos' => 'sometimes|required|string|max:100',
                'correo' => 'sometimes|required|email|unique:operadores,correo,' . $id,
                'telefono' => 'sometimes|required|string|max:20',
                'documento' => 'sometimes|required|string|unique:operadores,documento,' . $id,
                'tipo_documento' => 'sometimes|required|in:CC,CE,TI,RC,PA',
                'departamento' => 'sometimes|required|string|max:100',
                'ciudad' => 'sometimes|required|string|max:100',
                'direccion' => 'sometimes|required|string',
                'fecha_nacimiento' => 'sometimes|required|date',
                'supervisor_id' => 'nullable|exists:operadores,id',
                'especialidad' => 'sometimes|required|string|max:100',
                'nivel_experiencia' => 'sometimes|required|integer|min:1|max:5',
                'certificaciones' => 'nullable|array',
                'observaciones' => 'nullable|string',
                'estado' => 'sometimes|required|in:activo,inactivo,suspendido',
                'activo' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $operador->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $operador,
                'message' => 'Operador actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar operador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar operador (soft delete)
     */
    public function eliminar($id): JsonResponse
    {
        try {
            $operador = Operador::find($id);

            if (!$operador) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operador no encontrado'
                ], 404);
            }

            $operador->delete();

            return response()->json([
                'success' => true,
                'message' => 'Operador eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar operador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurar operador
     */
    public function restaurar($id): JsonResponse
    {
        try {
            $operador = Operador::withTrashed()->find($id);

            if (!$operador) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operador no encontrado'
                ], 404);
            }

            $operador->restore();

            return response()->json([
                'success' => true,
                'data' => $operador,
                'message' => 'Operador restaurado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar operador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar estado del operador
     */
    public function cambiarEstado(Request $request, $id): JsonResponse
    {
        try {
            $operador = Operador::find($id);

            if (!$operador) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operador no encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'estado' => 'required|in:activo,inactivo,suspendido',
                'motivo' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $estadoAnterior = $operador->estado;
            $operador->update([
                'estado' => $request->estado,
                'observaciones' => $operador->observaciones . "\nCambio de estado: {$estadoAnterior} -> {$request->estado}. Motivo: " . ($request->motivo ?? 'Sin motivo especificado')
            ]);

            return response()->json([
                'success' => true,
                'data' => $operador,
                'message' => 'Estado del operador actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado del operador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas del operador
     */
    public function obtenerEstadisticas($id): JsonResponse
    {
        try {
            $operador = Operador::find($id);

            if (!$operador) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operador no encontrado'
                ], 404);
            }

            $estadisticas = $operador->obtenerEstadisticas();
            $cargaTrabajo = $operador->obtenerCargaTrabajo();

            return response()->json([
                'success' => true,
                'data' => [
                    'operador' => $operador,
                    'estadisticas' => $estadisticas,
                    'carga_trabajo' => $cargaTrabajo
                ],
                'message' => 'Estadísticas del operador obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas del operador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tareas del operador
     */
    public function obtenerTareasAsignadas($id): JsonResponse
    {
        try {
            $operador = Operador::find($id);

            if (!$operador) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operador no encontrado'
                ], 404);
            }

            $tareas = Tarea::where('operador_id', $id)
                ->with(['cliente', 'pqrsfd', 'proyecto'])
                ->orderBy('fecha_vencimiento', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tareas,
                'message' => 'Tareas del operador obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tareas del operador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener proyectos del operador
     */
    public function obtenerProyectosAsignados($id): JsonResponse
    {
        try {
            $operador = Operador::find($id);

            if (!$operador) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operador no encontrado'
                ], 404);
            }

            $proyectos = Proyecto::where('operador_id', $id)
                ->with(['cliente', 'tareas'])
                ->orderBy('fecha_fin_estimada', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $proyectos,
                'message' => 'Proyectos del operador obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener proyectos del operador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener clientes asignados al operador
     */
    public function obtenerClientesAsignados($id): JsonResponse
    {
        try {
            $operador = Operador::find($id);

            if (!$operador) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operador no encontrado'
                ], 404);
            }

            $clientes = Cliente::where('operador_asignado_id', $id)
                ->with(['usuarioSistema'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $clientes,
                'message' => 'Clientes del operador obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener clientes del operador: ' . $e->getMessage()
            ], 500);
        }
    }
}