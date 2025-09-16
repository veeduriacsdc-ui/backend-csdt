<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UsuarioSistema;
use App\Models\Rol;
use App\Models\Permiso;
use App\Models\LogCambioUsuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UsuarioSistemaController extends Controller
{
    /**
     * Obtener lista de usuarios con filtros y paginación
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'tipo_usuario' => 'nullable|in:cliente,operador,administrador',
                'estado' => 'nullable|in:activo,inactivo,suspendido,pendiente_verificacion,en_revision',
                'buscar' => 'nullable|string|max:100',
                'por_pagina' => 'nullable|integer|min:1|max:100',
                'ordenar_por' => 'nullable|in:nombres,apellidos,correo,estado,tipo_usuario,created_at',
                'orden' => 'nullable|in:asc,desc',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $query = UsuarioSistema::with(['roles', 'permisosEspeciales']);

            // Filtros
            if ($request->filled('tipo_usuario')) {
                $query->where('tipo_usuario', $request->tipo_usuario);
            }

            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->filled('buscar')) {
                $buscar = $request->buscar;
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombres', 'like', '%'.$buscar.'%')
                        ->orWhere('apellidos', 'like', '%'.$buscar.'%')
                        ->orWhere('correo', 'like', '%'.$buscar.'%')
                        ->orWhere('documento_identidad', 'like', '%'.$buscar.'%');
                });
            }

            // Ordenamiento
            $ordenarPor = $request->get('ordenar_por', 'created_at');
            $orden = $request->get('orden', 'desc');
            $query->orderBy($ordenarPor, $orden);

            // Paginación
            $porPagina = $request->get('por_pagina', 20);
            $usuarios = $query->paginate($porPagina);

            // Formatear datos para el frontend
            $usuariosFormateados = $usuarios->map(function ($usuario) {
                return [
                    'id' => $usuario->id,
                    'nombre' => $usuario->nombre_completo,
                    'correo' => $usuario->correo,
                    'telefono' => $usuario->telefono,
                    'documento_identidad' => $usuario->documento_identidad,
                    'tipo_usuario' => $usuario->tipo_usuario,
                    'estado' => $usuario->estado,
                    'nivel_administracion' => $usuario->nivel_administracion,
                    'profesion' => $usuario->profesion,
                    'especializacion' => $usuario->especializacion,
                    'anos_experiencia' => $usuario->anos_experiencia,
                    'correo_verificado' => $usuario->correo_verificado,
                    'perfil_verificado' => $usuario->perfil_verificado,
                    'ultimo_acceso' => $usuario->ultimo_acceso,
                    'fecha_registro' => $usuario->created_at,
                    'roles' => $usuario->roles->map(function ($rol) {
                        return [
                            'id' => $rol->id,
                            'nombre' => $rol->nombre,
                            'slug' => $rol->slug,
                            'nivel_acceso' => $rol->nivel_acceso
                        ];
                    }),
                    'permisos_especiales' => $usuario->permisosEspeciales->map(function ($permiso) {
                        return [
                            'id' => $permiso->id,
                            'nombre' => $permiso->nombre,
                            'slug' => $permiso->slug,
                            'modulo' => $permiso->modulo
                        ];
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $usuariosFormateados,
                'pagination' => [
                    'current_page' => $usuarios->currentPage(),
                    'last_page' => $usuarios->lastPage(),
                    'per_page' => $usuarios->perPage(),
                    'total' => $usuarios->total(),
                ],
                'message' => 'Usuarios obtenidos exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener un usuario específico
     */
    public function show($id): JsonResponse
    {
        try {
            $usuario = UsuarioSistema::with(['roles', 'permisosEspeciales', 'supervisor', 'supervisados'])
                ->findOrFail($id);

            $usuarioFormateado = [
                'id' => $usuario->id,
                'nombres' => $usuario->nombres,
                'apellidos' => $usuario->apellidos,
                'nombre_completo' => $usuario->nombre_completo,
                'correo' => $usuario->correo,
                'telefono' => $usuario->telefono,
                'documento_identidad' => $usuario->documento_identidad,
                'tipo_documento' => $usuario->tipo_documento,
                'fecha_nacimiento' => $usuario->fecha_nacimiento,
                'direccion' => $usuario->direccion,
                'ciudad' => $usuario->ciudad,
                'departamento' => $usuario->departamento,
                'genero' => $usuario->genero,
                'tipo_usuario' => $usuario->tipo_usuario,
                'estado' => $usuario->estado,
                'nivel_administracion' => $usuario->nivel_administracion,
                'profesion' => $usuario->profesion,
                'especializacion' => $usuario->especializacion,
                'anos_experiencia' => $usuario->anos_experiencia,
                'perfil_profesional' => $usuario->perfil_profesional,
                'areas_expertise' => $usuario->areas_expertise,
                'correo_verificado' => $usuario->correo_verificado,
                'perfil_verificado' => $usuario->perfil_verificado,
                'ultimo_acceso' => $usuario->ultimo_acceso,
                'fecha_registro' => $usuario->created_at,
                'roles' => $usuario->roles,
                'permisos_especiales' => $usuario->permisosEspeciales,
                'supervisor' => $usuario->supervisor,
                'supervisados' => $usuario->supervisados
            ];

            return response()->json([
                'success' => true,
                'data' => $usuarioFormateado,
                'message' => 'Usuario obtenido exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
            ], 404);
        }
    }

    /**
     * Crear un nuevo usuario
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), UsuarioSistema::rules());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $usuario = UsuarioSistema::create($request->all());

            // Asignar rol por defecto según el tipo de usuario
            $rolPorDefecto = $this->obtenerRolPorDefecto($usuario->tipo_usuario);
            if ($rolPorDefecto) {
                $usuario->asignarRol($rolPorDefecto->id, auth()->id(), 'Rol asignado automáticamente al crear usuario');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $usuario->load('roles'),
                'message' => 'Usuario creado exitosamente',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear usuario: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar un usuario
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $usuario = UsuarioSistema::findOrFail($id);
            $datosAnteriores = $usuario->toArray();

            $validator = Validator::make($request->all(), UsuarioSistema::rules($id));

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $usuario->update($request->all());

            // Registrar cambios importantes
            $this->registrarCambios($usuario, $datosAnteriores, $request->all());

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $usuario->load('roles', 'permisosEspeciales'),
                'message' => 'Usuario actualizado exitosamente',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar usuario: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cambiar rol de un usuario
     */
    public function cambiarRol(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'rol_id' => 'required|exists:roles,id',
                'motivo' => 'nullable|string|max:500',
                'fecha_expiracion' => 'nullable|date|after:today',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $usuario = UsuarioSistema::findOrFail($id);
            $rolAnterior = $usuario->roles()->wherePivot('activo', true)->first();

            DB::beginTransaction();

            // Revocar rol anterior si existe
            if ($rolAnterior) {
                $usuario->revocarRol($rolAnterior->id);
            }

            // Asignar nuevo rol
            $usuario->asignarRol(
                $request->rol_id,
                auth()->id(),
                $request->motivo,
                $request->fecha_expiracion
            );

            // Registrar el cambio
            LogCambioUsuario::create([
                'usuario_id' => $usuario->id,
                'tipo_cambio' => 'rol',
                'campo_anterior' => 'rol_id',
                'valor_anterior' => $rolAnterior ? $rolAnterior->id : null,
                'campo_nuevo' => 'rol_id',
                'valor_nuevo' => $request->rol_id,
                'cambiado_por' => auth()->id(),
                'motivo' => $request->motivo
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $usuario->load('roles'),
                'message' => 'Rol del usuario cambiado exitosamente',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar rol: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cambiar estado de un usuario
     */
    public function cambiarEstado(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'estado' => 'required|in:activo,inactivo,suspendido,pendiente_verificacion,en_revision',
                'motivo' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $usuario = UsuarioSistema::findOrFail($id);
            $estadoAnterior = $usuario->estado;

            DB::beginTransaction();

            $usuario->cambiarEstado($request->estado, $request->motivo);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $usuario,
                'message' => "Estado del usuario cambiado de '{$estadoAnterior}' a '{$request->estado}' exitosamente",
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar usuario (soft delete)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $usuario = UsuarioSistema::findOrFail($id);

            // Verificar que no sea el último administrador general
            if ($usuario->esAdministradorGeneral()) {
                $totalAdminsGenerales = UsuarioSistema::where('tipo_usuario', 'administrador')
                    ->where('nivel_administracion', 4)
                    ->count();
                
                if ($totalAdminsGenerales <= 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede eliminar el último administrador general del sistema',
                    ], 400);
                }
            }

            DB::beginTransaction();

            $usuario->delete();

            // Registrar el cambio
            LogCambioUsuario::create([
                'usuario_id' => $usuario->id,
                'tipo_cambio' => 'eliminacion',
                'campo_anterior' => 'estado',
                'valor_anterior' => $usuario->estado,
                'campo_nuevo' => 'estado',
                'valor_nuevo' => 'eliminado',
                'cambiado_por' => auth()->id(),
                'motivo' => 'Usuario eliminado del sistema'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar usuario: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de usuarios
     */
    public function estadisticas(): JsonResponse
    {
        try {
            $estadisticas = [
                'totales' => [
                    'clientes' => UsuarioSistema::clientes()->count(),
                    'operadores' => UsuarioSistema::operadores()->count(),
                    'administradores' => UsuarioSistema::administradores()->count(),
                    'total_usuarios' => UsuarioSistema::count(),
                ],
                'estados' => [
                    'activos' => UsuarioSistema::activos()->count(),
                    'inactivos' => UsuarioSistema::where('estado', 'inactivo')->count(),
                    'suspendidos' => UsuarioSistema::where('estado', 'suspendido')->count(),
                    'pendientes' => UsuarioSistema::where('estado', 'pendiente_verificacion')->count(),
                ],
                'verificaciones' => [
                    'correo_verificado' => UsuarioSistema::verificados()->count(),
                    'perfil_verificado' => UsuarioSistema::where('perfil_verificado', true)->count(),
                ],
                'niveles_administracion' => UsuarioSistema::administradores()
                    ->selectRaw('nivel_administracion, COUNT(*) as total')
                    ->groupBy('nivel_administracion')
                    ->pluck('total', 'nivel_administracion')
                    ->toArray(),
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas de usuarios obtenidas exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener rol por defecto según el tipo de usuario
     */
    private function obtenerRolPorDefecto($tipoUsuario)
    {
        return Rol::where('slug', $tipoUsuario)->first();
    }

    /**
     * Registrar cambios importantes en el usuario
     */
    private function registrarCambios($usuario, $datosAnteriores, $datosNuevos)
    {
        $camposImportantes = ['tipo_usuario', 'nivel_administracion', 'estado'];
        
        foreach ($camposImportantes as $campo) {
            if (isset($datosNuevos[$campo]) && $datosAnteriores[$campo] !== $datosNuevos[$campo]) {
                LogCambioUsuario::create([
                    'usuario_id' => $usuario->id,
                    'tipo_cambio' => $campo,
                    'campo_anterior' => $campo,
                    'valor_anterior' => $datosAnteriores[$campo],
                    'campo_nuevo' => $campo,
                    'valor_nuevo' => $datosNuevos[$campo],
                    'cambiado_por' => auth()->id(),
                    'motivo' => 'Actualización de perfil'
                ]);
            }
        }
    }
}
