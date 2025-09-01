<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Operador;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OperadorControlador extends Controller
{
    /**
     * Obtener lista paginada de operadores con filtros
     */
    public function ObtenerLista(Request $request): JsonResponse
    {
        try {
            $query = Operador::with(['veedurias', 'tareas']);

            // Filtros
            if ($request->filled('estado')) {
                $query->where('Estado', $request->estado);
            }

            if ($request->filled('profesion')) {
                $query->where('Profesion', 'like', '%' . $request->profesion . '%');
            }

            if ($request->filled('especializacion')) {
                $query->where('Especializacion', 'like', '%' . $request->especializacion . '%');
            }

            if ($request->filled('buscar')) {
                $buscar = $request->buscar;
                $query->where(function ($q) use ($buscar) {
                    $q->where('Nombres', 'like', '%' . $buscar . '%')
                      ->orWhere('Apellidos', 'like', '%' . $buscar . '%')
                      ->orWhere('Correo', 'like', '%' . $buscar . '%')
                      ->orWhere('DocumentoIdentidad', 'like', '%' . $buscar . '%');
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'Nombres');
            $direccion = $request->get('direccion', 'asc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $operadores = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $operadores->items(),
                'pagination' => [
                    'current_page' => $operadores->currentPage(),
                    'last_page' => $operadores->lastPage(),
                    'per_page' => $operadores->perPage(),
                    'total' => $operadores->total(),
                ],
                'message' => 'Operadores obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener operadores: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un operador específico
     */
    public function ObtenerPorId($id): JsonResponse
    {
        try {
            $operador = Operador::with(['veedurias', 'tareas'])->find($id);

            if (!$operador) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operador no encontrado'
                ], 404);
            }

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
     * Crear un nuevo operador
     */
    public function Crear(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'Nombres' => 'required|string|max:255',
                'Apellidos' => 'required|string|max:255',
                'DocumentoIdentidad' => 'required|string|unique:Operadores,DocumentoIdentidad',
                'Correo' => 'required|email|unique:Operadores,Correo',
                'Contrasena' => 'required|string|min:8',
                'Telefono' => 'required|string|max:20',
                'Direccion' => 'required|string|max:500',
                'Ciudad' => 'required|string|max:100',
                'Departamento' => 'required|string|max:100',
                'Profesion' => 'required|string|max:255',
                'Especializacion' => 'required|string|max:255',
                'ExperienciaAnos' => 'required|integer|min:0',
                'Estado' => 'required|in:activo,inactivo,suspendido'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $datos = $request->all();
            $datos['Contrasena'] = Hash::make($request->Contrasena);
            $datos['FechaRegistro'] = now();

            $operador = Operador::create($datos);

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
     * Actualizar un operador existente
     */
    public function Actualizar(Request $request, $id): JsonResponse
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
                'Nombres' => 'sometimes|required|string|max:255',
                'Apellidos' => 'sometimes|required|string|max:255',
                'DocumentoIdentidad' => [
                    'sometimes',
                    'required',
                    'string',
                    Rule::unique('Operadores', 'DocumentoIdentidad')->ignore($id, 'IdOperador')
                ],
                'Correo' => [
                    'sometimes',
                    'required',
                    'email',
                    Rule::unique('Operadores', 'Correo')->ignore($id, 'IdOperador')
                ],
                'Telefono' => 'sometimes|required|string|max:20',
                'Direccion' => 'sometimes|required|string|max:500',
                'Ciudad' => 'sometimes|required|string|max:100',
                'Departamento' => 'sometimes|required|string|max:100',
                'Profesion' => 'sometimes|required|string|max:255',
                'Especializacion' => 'sometimes|required|string|max:255',
                'ExperienciaAnos' => 'sometimes|required|integer|min:0',
                'Estado' => 'sometimes|required|in:activo,inactivo,suspendido'
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
     * Eliminar un operador
     */
    public function Eliminar($id): JsonResponse
    {
        try {
            $operador = Operador::find($id);

            if (!$operador) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operador no encontrado'
                ], 404);
            }

            // Verificar si tiene veedurías activas
            if ($operador->veedurias()->where('Estado', '!=', 'completada')->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el operador porque tiene veedurías activas'
                ], 400);
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
     * Obtener estadísticas de operadores
     */
    public function ObtenerEstadisticas(): JsonResponse
    {
        try {
            $estadisticas = [
                'total' => Operador::count(),
                'activos' => Operador::where('Estado', 'activo')->count(),
                'inactivos' => Operador::where('Estado', 'inactivo')->count(),
                'suspendidos' => Operador::where('Estado', 'suspendido')->count(),
                'por_profesion' => Operador::selectRaw('Profesion, COUNT(*) as total')
                    ->groupBy('Profesion')
                    ->get(),
                'por_ciudad' => Operador::selectRaw('Ciudad, COUNT(*) as total')
                    ->groupBy('Ciudad')
                    ->orderBy('total', 'desc')
                    ->limit(10)
                    ->get(),
                'por_experiencia' => [
                    '0-2 años' => Operador::where('ExperienciaAnos', '<=', 2)->count(),
                    '3-5 años' => Operador::whereBetween('ExperienciaAnos', [3, 5])->count(),
                    '6-10 años' => Operador::whereBetween('ExperienciaAnos', [6, 10])->count(),
                    'Más de 10 años' => Operador::where('ExperienciaAnos', '>', 10)->count(),
                ]
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
     * Verificar perfil de operador
     */
    public function VerificarPerfil(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'DocumentoIdentidad' => 'required|string',
                'Correo' => 'required|email'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $operador = Operador::where('DocumentoIdentidad', $request->DocumentoIdentidad)
                ->orWhere('Correo', $request->Correo)
                ->first();

            if ($operador) {
                return response()->json([
                    'success' => false,
                    'message' => 'El documento de identidad o correo ya están registrados'
                ], 409);
            }

            return response()->json([
                'success' => true,
                'message' => 'Perfil disponible para registro'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar perfil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar estado de operador
     */
    public function CambiarEstado(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'Estado' => 'required|in:activo,inactivo,suspendido'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estado no válido',
                    'errors' => $validator->errors()
                ], 422);
            }

            $operador = Operador::find($id);

            if (!$operador) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operador no encontrado'
                ], 404);
            }

            $operador->update(['Estado' => $request->Estado]);

            return response()->json([
                'success' => true,
                'data' => $operador,
                'message' => 'Estado del operador actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado: ' . $e->getMessage()
            ], 500);
        }
    }
}
