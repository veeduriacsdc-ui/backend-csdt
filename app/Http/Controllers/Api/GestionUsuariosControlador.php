<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sesion;
use App\Models\Cliente;
use App\Models\Operador;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GestionUsuariosControlador extends Controller
{
    /**
     * Obtener lista de usuarios con filtros y paginación
     */
    public function obtenerListaUsuarios(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'tipo_usuario' => 'nullable|in:cliente,operador,administrador',
                'rol' => 'nullable|in:cliente,operador,administrador',
                'estado' => 'nullable|in:activo,inactivo,suspendido',
                'buscar' => 'nullable|string|max:100',
                'por_pagina' => 'nullable|integer|min:1|max:100',
                'ordenar_por' => 'nullable|in:nombre,email,fecha_registro,estado',
                'orden' => 'nullable|in:asc,desc'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $tipoUsuario = $request->tipo_usuario;
            $rol = $request->rol;
            $estado = $request->estado;
            $buscar = $request->buscar;
            $porPagina = $request->por_pagina ?? 20;
            $ordenarPor = $request->ordenar_por ?? 'fecha_registro';
            $orden = $request->orden ?? 'desc';

            $usuarios = collect();

            // Buscar en clientes
            if (!$tipoUsuario || $tipoUsuario === 'cliente') {
                $clientes = Cliente::query();
                
                if ($estado) {
                    $clientes->where('estado', $estado);
                }
                
                if ($buscar) {
                    $clientes->where(function ($query) use ($buscar) {
                        $query->where('nombre_completo', 'like', "%{$buscar}%")
                              ->orWhere('correo_electronico', 'like', "%{$buscar}%");
                    });
                }
                
                $clientes = $clientes->orderBy($ordenarPor, $orden)->paginate($porPagina);
                
                foreach ($clientes as $cliente) {
                    $usuarios->push([
                        'id' => $cliente->id,
                        'tipo_usuario' => 'cliente',
                        'rol' => 'cliente',
                        'nombre_completo' => $cliente->nombre_completo,
                        'correo_electronico' => $cliente->correo_electronico,
                        'telefono' => $cliente->telefono,
                        'estado' => $cliente->estado,
                        'fecha_registro' => $cliente->fecha_registro,
                        'total_operaciones' => $cliente->operaciones()->count(),
                        'total_donaciones' => $cliente->donaciones()->count()
                    ]);
                }
            }

            // Buscar en operadores
            if (!$tipoUsuario || in_array($tipoUsuario, ['operador', 'administrador'])) {
                $operadores = Operador::query();
                
                if ($rol) {
                    $operadores->where('rol', $rol);
                }
                
                if ($estado) {
                    $operadores->where('estado', $estado);
                }
                
                if ($buscar) {
                    $operadores->where(function ($query) use ($buscar) {
                        $query->where('nombre_completo', 'like', "%{$buscar}%")
                              ->orWhere('correo_electronico', 'like', "%{$buscar}%");
                    });
                }
                
                $operadores = $operadores->orderBy($ordenarPor, $orden)->paginate($porPagina);
                
                foreach ($operadores as $operador) {
                    $usuarios->push([
                        'id' => $operador->id,
                        'tipo_usuario' => 'operador',
                        'rol' => $operador->rol,
                        'nombre_completo' => $operador->nombre_completo,
                        'correo_electronico' => $operador->correo_electronico,
                        'telefono' => $operador->telefono,
                        'estado' => $operador->estado,
                        'fecha_registro' => $operador->fecha_registro,
                        'nivel_acceso' => $operador->nivel_acceso,
                        'total_veedurias_asignadas' => $operador->veedurias()->count(),
                        'total_tareas_asignadas' => $operador->tareas()->count(),
                        'es_supervisor' => $operador->esSupervisor(),
                        'supervisor_id' => $operador->supervisor_id
                    ]);
                }
            }

            // Ordenar y paginar la colección combinada
            $usuarios = $usuarios->sortBy($ordenarPor);
            if ($orden === 'desc') {
                $usuarios = $usuarios->reverse();
            }

            $total = $usuarios->count();
            $usuarios = $usuarios->forPage(1, $porPagina);

            return response()->json([
                'success' => true,
                'message' => 'Lista de usuarios obtenida exitosamente',
                'data' => [
                    'usuarios' => $usuarios->values(),
                    'total' => $total,
                    'por_pagina' => $porPagina,
                    'pagina_actual' => 1,
                    'total_paginas' => ceil($total / $porPagina)
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al obtener lista de usuarios: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtener detalles de un usuario específico
     */
    public function obtenerUsuario(Request $request, int $id): JsonResponse
    {
        try {
            $tipoUsuario = $request->query('tipo_usuario', 'cliente');

            if ($tipoUsuario === 'cliente') {
                $usuario = Cliente::with(['veedurias', 'donaciones', 'archivos'])->find($id);
            } else {
                $usuario = Operador::with(['veedurias', 'tareas', 'subordinados'])->find($id);
            }

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Obtener sesión activa si existe
            $sesion = Sesion::where('usuario_id', $id)
                           ->where('tipo_usuario', $tipoUsuario)
                           ->where('estado_sesion', 'activa')
                           ->first();

            $datosUsuario = [
                'id' => $usuario->id,
                'tipo_usuario' => $tipoUsuario,
                'nombre_completo' => $usuario->nombre_completo,
                'correo_electronico' => $usuario->correo_electronico,
                'telefono' => $usuario->telefono,
                'direccion' => $usuario->direccion,
                'estado' => $usuario->estado,
                'fecha_registro' => $usuario->fecha_registro,
                'sesion_activa' => $sesion ? true : false
            ];

            if ($tipoUsuario === 'operador') {
                $datosUsuario['rol'] = $usuario->rol;
                $datosUsuario['nivel_acceso'] = $usuario->nivel_acceso;
                $datosUsuario['permisos'] = $usuario->permisos;
                $datosUsuario['es_supervisor'] = $usuario->esSupervisor();
                $datosUsuario['supervisor_id'] = $usuario->supervisor_id;
                $datosUsuario['total_veedurias_asignadas'] = $usuario->veedurias()->count();
                $datosUsuario['total_tareas_asignadas'] = $usuario->tareas()->count();
                $datosUsuario['total_subordinados'] = $usuario->subordinados()->count();
            } else {
                $datosUsuario['total_veedurias'] = $usuario->veedurias()->count();
                $datosUsuario['total_donaciones'] = $usuario->donaciones()->count();
                $datosUsuario['total_archivos'] = $usuario->archivos()->count();
            }

            return response()->json([
                'success' => true,
                'message' => 'Usuario obtenido exitosamente',
                'data' => $datosUsuario
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al obtener usuario: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Cambiar rol de un usuario
     */
    public function cambiarRolUsuario(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nuevo_rol' => 'required|in:cliente,operador,administrador',
                'nivel_acceso' => 'nullable|integer|min:1|max:5',
                'permisos' => 'nullable|array',
                'notas' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $tipoUsuario = $request->query('tipo_usuario', 'cliente');
            $nuevoRol = $request->nuevo_rol;
            $nivelAcceso = $request->nivel_acceso;
            $permisos = $request->permisos;
            $notas = $request->notas;

            if ($tipoUsuario === 'cliente') {
                $usuario = Cliente::find($id);
                
                if (!$usuario) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cliente no encontrado'
                    ], 404);
                }

                // Si se está promoviendo a operador, crear en tabla operadores
                if ($nuevoRol === 'operador' || $nuevoRol === 'administrador') {
                    $operador = Operador::create([
                        'nombre_completo' => $usuario->nombre_completo,
                        'correo_electronico' => $usuario->correo_electronico,
                        'contrasena' => $usuario->contrasena,
                        'telefono' => $usuario->telefono,
                        'direccion' => $usuario->direccion,
                        'rol' => $nuevoRol,
                        'nivel_acceso' => $nivelAcceso ?? 3,
                        'permisos' => $permisos ?? [],
                        'estado' => 'activo',
                        'notas_internas' => $notas
                    ]);

                    // Desactivar cliente
                    $usuario->update(['estado' => 'convertido_a_operador']);

                    $mensaje = "Cliente promovido a {$nuevoRol} exitosamente";
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Los clientes solo pueden ser promovidos a operador o administrador'
                    ], 400);
                }

            } else {
                $usuario = Operador::find($id);
                
                if (!$usuario) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Operador no encontrado'
                    ], 404);
                }

                $usuario->update([
                    'rol' => $nuevoRol,
                    'nivel_acceso' => $nivelAcceso ?? $usuario->nivel_acceso,
                    'permisos' => $permisos ?? $usuario->permisos,
                    'notas_internas' => $notas
                ]);

                $mensaje = "Rol del operador cambiado a {$nuevoRol} exitosamente";
            }

            // Actualizar sesión si existe
            $sesion = Sesion::where('usuario_id', $id)
                           ->where('tipo_usuario', $tipoUsuario)
                           ->where('estado_sesion', 'activa')
                           ->first();

            if ($sesion) {
                $sesion->cambiarRol($nuevoRol);
            }

            // Log de auditoría
            LogAuditoria::crear([
                'usuario_id' => auth()->id() ?? 1,
                'tipo_usuario' => 'administrador',
                'accion' => 'cambiar_rol_usuario',
                'entidad' => $tipoUsuario,
                'entidad_id' => $id,
                'datos_anteriores' => ['rol_anterior' => $usuario->rol ?? 'cliente'],
                'datos_nuevos' => ['nuevo_rol' => $nuevoRol],
                'ip_cliente' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'data' => [
                    'usuario_id' => $id,
                    'nuevo_rol' => $nuevoRol,
                    'nivel_acceso' => $nivelAcceso,
                    'permisos' => $permisos
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al cambiar rol de usuario: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Cambiar estado de un usuario
     */
    public function cambiarEstadoUsuario(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nuevo_estado' => 'required|in:activo,inactivo,suspendido',
                'motivo' => 'nullable|string|max:500',
                'fecha_suspension' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $tipoUsuario = $request->query('tipo_usuario', 'cliente');
            $nuevoEstado = $request->nuevo_estado;
            $motivo = $request->motivo;
            $fechaSuspension = $request->fecha_suspension;

            if ($tipoUsuario === 'cliente') {
                $usuario = Cliente::find($id);
            } else {
                $usuario = Operador::find($id);
            }

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $estadoAnterior = $usuario->estado;

            $datosActualizacion = ['estado' => $nuevoEstado];
            
            if ($motivo) {
                $datosActualizacion['notas_internas'] = $motivo;
            }

            if ($nuevoEstado === 'suspendido' && $fechaSuspension) {
                $datosActualizacion['fecha_suspension'] = $fechaSuspension;
            }

            $usuario->update($datosActualizacion);

            // Si se está suspendiendo, cerrar sesión activa
            if ($nuevoEstado === 'suspendido') {
                $sesion = Sesion::where('usuario_id', $id)
                               ->where('tipo_usuario', $tipoUsuario)
                               ->where('estado_sesion', 'activa')
                               ->first();

                if ($sesion) {
                    $sesion->cerrarSesion();
                }
            }

            // Log de auditoría
            LogAuditoria::crear([
                'usuario_id' => auth()->id() ?? 1,
                'tipo_usuario' => 'administrador',
                'accion' => 'cambiar_estado_usuario',
                'entidad' => $tipoUsuario,
                'entidad_id' => $id,
                'datos_anteriores' => ['estado_anterior' => $estadoAnterior],
                'datos_nuevos' => ['nuevo_estado' => $nuevoEstado, 'motivo' => $motivo],
                'ip_cliente' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Estado del usuario cambiado a {$nuevoEstado} exitosamente",
                'data' => [
                    'usuario_id' => $id,
                    'estado_anterior' => $estadoAnterior,
                    'nuevo_estado' => $nuevoEstado,
                    'motivo' => $motivo,
                    'fecha_suspension' => $fechaSuspension
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al cambiar estado de usuario: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de usuarios
     */
    public function obtenerEstadisticasUsuarios(): JsonResponse
    {
        try {
            $totalClientes = Cliente::count();
            $totalOperadores = Operador::count();
            $totalAdministradores = Operador::where('rol', 'administrador')->count();
            $totalOperadoresVeedores = Operador::where('rol', 'operador')->count();

            $clientesActivos = Cliente::where('estado', 'activo')->count();
            $operadoresActivos = Operador::where('estado', 'activo')->count();
            $usuariosSuspendidos = Cliente::where('estado', 'suspendido')->count() + 
                                 Operador::where('estado', 'suspendido')->count();

            $sesionesActivas = Sesion::activas()->count();
            $sesionesHoy = Sesion::whereDate('fecha_inicio', today())->count();

            $estadisticas = [
                'totales' => [
                    'clientes' => $totalClientes,
                    'operadores' => $totalOperadores,
                    'administradores' => $totalAdministradores,
                    'operadores_veedores' => $totalOperadoresVeedores,
                    'total_usuarios' => $totalClientes + $totalOperadores
                ],
                'estados' => [
                    'activos' => $clientesActivos + $operadoresActivos,
                    'suspendidos' => $usuariosSuspendidos,
                    'inactivos' => ($totalClientes + $totalOperadores) - ($clientesActivos + $operadoresActivos + $usuariosSuspendidos)
                ],
                'sesiones' => [
                    'activas' => $sesionesActivas,
                    'hoy' => $sesionesHoy
                ],
                'distribucion_roles' => [
                    'clientes' => round(($totalClientes / ($totalClientes + $totalOperadores)) * 100, 1),
                    'operadores' => round(($totalOperadoresVeedores / ($totalClientes + $totalOperadores)) * 100, 1),
                    'administradores' => round(($totalAdministradores / ($totalClientes + $totalOperadores)) * 100, 1)
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Estadísticas de usuarios obtenidas exitosamente',
                'data' => $estadisticas
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas de usuarios: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Eliminar usuario (soft delete)
     */
    public function eliminarUsuario(Request $request, int $id): JsonResponse
    {
        try {
            $tipoUsuario = $request->query('tipo_usuario', 'cliente');
            $motivo = $request->input('motivo', 'Eliminación por administrador');

            if ($tipoUsuario === 'cliente') {
                $usuario = Cliente::find($id);
            } else {
                $usuario = Operador::find($id);
            }

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Verificar que no sea el último administrador
            if ($tipoUsuario === 'operador' && $usuario->rol === 'administrador') {
                $totalAdmins = Operador::where('rol', 'administrador')->count();
                if ($totalAdmins <= 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede eliminar el último administrador del sistema'
                    ], 400);
                }
            }

            // Cerrar sesión activa si existe
            $sesion = Sesion::where('usuario_id', $id)
                           ->where('tipo_usuario', $tipoUsuario)
                           ->where('estado_sesion', 'activa')
                           ->first();

            if ($sesion) {
                $sesion->cerrarSesion();
            }

            // Soft delete del usuario
            $usuario->delete();

            // Log de auditoría
            LogAuditoria::crear([
                'usuario_id' => auth()->id() ?? 1,
                'tipo_usuario' => 'administrador',
                'accion' => 'eliminar_usuario',
                'entidad' => $tipoUsuario,
                'entidad_id' => $id,
                'datos_anteriores' => ['usuario_eliminado' => $usuario->toArray()],
                'datos_nuevos' => [],
                'ip_cliente' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente',
                'data' => [
                    'usuario_id' => $id,
                    'tipo_usuario' => $tipoUsuario,
                    'motivo' => $motivo
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al eliminar usuario: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Restaurar usuario eliminado
     */
    public function restaurarUsuario(Request $request, int $id): JsonResponse
    {
        try {
            $tipoUsuario = $request->query('tipo_usuario', 'cliente');

            if ($tipoUsuario === 'cliente') {
                $usuario = Cliente::withTrashed()->find($id);
            } else {
                $usuario = Operador::withTrashed()->find($id);
            }

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            if (!$usuario->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario no está eliminado'
                ], 400);
            }

            // Restaurar usuario
            $usuario->restore();

            // Log de auditoría
            LogAuditoria::crear([
                'usuario_id' => auth()->id() ?? 1,
                'tipo_usuario' => 'administrador',
                'accion' => 'restaurar_usuario',
                'entidad' => $tipoUsuario,
                'entidad_id' => $id,
                'datos_anteriores' => [],
                'datos_nuevos' => ['usuario_restaurado' => $usuario->toArray()],
                'ip_cliente' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Usuario restaurado exitosamente',
                'data' => [
                    'usuario_id' => $id,
                    'tipo_usuario' => $tipoUsuario,
                    'nombre_completo' => $usuario->nombre_completo
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al restaurar usuario: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
