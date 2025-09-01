<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClienteControlador extends Controller
{
    /**
     * Obtener lista paginada de clientes con filtros
     */
    public function ObtenerLista(Request $request): JsonResponse
    {
        try {
            $query = Cliente::with(['pqrsfds', 'donaciones']);

            // Filtros
            if ($request->filled('estado')) {
                $query->where('Estado', $request->estado);
            }

            if ($request->filled('ciudad')) {
                $query->where('Ciudad', 'like', '%' . $request->ciudad . '%');
            }

            if ($request->filled('departamento')) {
                $query->where('Departamento', 'like', '%' . $request->departamento . '%');
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
            $clientes = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $clientes->items(),
                'pagination' => [
                    'current_page' => $clientes->currentPage(),
                    'last_page' => $clientes->lastPage(),
                    'per_page' => $clientes->perPage(),
                    'total' => $clientes->total(),
                ],
                'message' => 'Clientes obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener clientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un cliente específico
     */
    public function ObtenerPorId($id): JsonResponse
    {
        try {
            $cliente = Cliente::with(['pqrsfds', 'donaciones', 'documentos'])->find($id);

            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $cliente,
                'message' => 'Cliente obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo cliente
     */
    public function Crear(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'Nombres' => 'required|string|max:255',
                'Apellidos' => 'required|string|max:255',
                'DocumentoIdentidad' => 'required|string|unique:Clientes,DocumentoIdentidad',
                'Correo' => 'required|email|unique:Clientes,Correo',
                'Contrasena' => 'required|string|min:8',
                'Telefono' => 'required|string|max:20',
                'Direccion' => 'required|string|max:500',
                'Ciudad' => 'required|string|max:100',
                'Departamento' => 'required|string|max:100',
                'FechaNacimiento' => 'sometimes|date|before:today',
                'Genero' => 'sometimes|in:masculino,femenino,otro',
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

            $cliente = Cliente::create($datos);

            return response()->json([
                'success' => true,
                'data' => $cliente,
                'message' => 'Cliente creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un cliente existente
     */
    public function Actualizar(Request $request, $id): JsonResponse
    {
        try {
            $cliente = Cliente::find($id);

            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'Nombres' => 'sometimes|required|string|max:255',
                'Apellidos' => 'sometimes|required|string|max:255',
                'DocumentoIdentidad' => [
                    'sometimes',
                    'required',
                    'string',
                    Rule::unique('Clientes', 'DocumentoIdentidad')->ignore($id, 'IdCliente')
                ],
                'Correo' => [
                    'sometimes',
                    'required',
                    'email',
                    Rule::unique('Clientes', 'Correo')->ignore($id, 'IdCliente')
                ],
                'Telefono' => 'sometimes|required|string|max:20',
                'Direccion' => 'sometimes|required|string|max:500',
                'Ciudad' => 'sometimes|required|string|max:100',
                'Departamento' => 'sometimes|required|string|max:100',
                'FechaNacimiento' => 'sometimes|date|before:today',
                'Genero' => 'sometimes|in:masculino,femenino,otro',
                'Estado' => 'sometimes|required|in:activo,inactivo,suspendido'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $cliente->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $cliente,
                'message' => 'Cliente actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un cliente
     */
    public function Eliminar($id): JsonResponse
    {
        try {
            $cliente = Cliente::find($id);

            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ], 404);
            }

            // Verificar si tiene veedurías activas
            if ($cliente->veedurias()->where('Estado', '!=', 'completada')->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el cliente porque tiene veedurías activas'
                ], 400);
            }

            $cliente->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cliente eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de clientes
     */
    public function ObtenerEstadisticas(): JsonResponse
    {
        try {
            $estadisticas = [
                'total' => Cliente::count(),
                'activos' => Cliente::where('Estado', 'activo')->count(),
                'inactivos' => Cliente::where('Estado', 'inactivo')->count(),
                'suspendidos' => Cliente::where('Estado', 'suspendido')->count(),
                'por_ciudad' => Cliente::selectRaw('Ciudad, COUNT(*) as total')
                    ->groupBy('Ciudad')
                    ->orderBy('total', 'desc')
                    ->limit(10)
                    ->get(),
                'por_departamento' => Cliente::selectRaw('Departamento, COUNT(*) as total')
                    ->groupBy('Departamento')
                    ->orderBy('total', 'desc')
                    ->get(),
                'por_genero' => Cliente::selectRaw('Genero, COUNT(*) as total')
                    ->whereNotNull('Genero')
                    ->groupBy('Genero')
                    ->get(),
                'por_mes' => Cliente::selectRaw('MONTH(FechaRegistro) as mes, COUNT(*) as total')
                    ->whereYear('FechaRegistro', date('Y'))
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
     * Verificar correo de cliente
     */
    public function VerificarCorreo(Request $request): JsonResponse
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

            $cliente = Cliente::where('DocumentoIdentidad', $request->DocumentoIdentidad)
                ->orWhere('Correo', $request->Correo)
                ->first();

            if ($cliente) {
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
     * Cambiar estado de cliente
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

            $cliente = Cliente::find($id);

            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ], 404);
            }

            $estadoAnterior = $cliente->Estado;
            $cliente->update(['Estado' => $request->Estado]);

            return response()->json([
                'success' => true,
                'data' => $cliente,
                'message' => "Estado del cliente cambiado de '{$estadoAnterior}' a '{$request->Estado}' exitosamente"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado: ' . $e->getMessage()
            ], 500);
        }
    }
}
