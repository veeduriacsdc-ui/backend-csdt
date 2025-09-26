<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdministradorGeneral;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\PermisoMejorado;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdministradorGeneralController extends Controller
{
    /**
     * Obtener lista de administradores generales
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = AdministradorGeneral::administradoresGenerales()->with(['roles']);

            // Filtros
            if ($request->has('est')) {
                $query->where('est', $request->est);
            }

            if ($request->has('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('nom', 'like', "%{$buscar}%")
                      ->orWhere('ape', 'like', "%{$buscar}%")
                      ->orWhere('cor', 'like', "%{$buscar}%")
                      ->orWhere('doc', 'like', "%{$buscar}%");
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'created_at');
            $direccion = $request->get('direccion', 'desc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $porPagina = $request->get('por_pagina', 15);
            $administradores = $query->paginate($porPagina);

            return response()->json([
                'success' => true,
                'data' => $administradores,
                'message' => 'Administradores generales obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener administradores generales: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo administrador general
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nom' => 'required|string|max:100',
                'ape' => 'required|string|max:100',
                'cor' => 'required|email|max:150|unique:usu,cor',
                'con' => 'required|string|min:8',
                'con_confirmation' => 'required_with:con|same:con',
                'tel' => 'nullable|string|max:20',
                'doc' => 'required|string|max:20|unique:usu,doc',
                'tip_doc' => 'required|in:cc,ce,ti,pp,nit',
                'fec_nac' => 'nullable|date',
                'dir' => 'nullable|string|max:200',
                'ciu' => 'nullable|string|max:100',
                'dep' => 'nullable|string|max:100',
                'gen' => 'nullable|in:m,f,o,n',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $administrador = AdministradorGeneral::create([
                'nom' => $request->nom,
                'ape' => $request->ape,
                'cor' => $request->cor,
                'con' => Hash::make($request->con),
                'tel' => $request->tel,
                'doc' => $request->doc,
                'tip_doc' => $request->tip_doc,
                'fec_nac' => $request->fec_nac,
                'dir' => $request->dir,
                'ciu' => $request->ciu,
                'dep' => $request->dep,
                'gen' => $request->gen,
                'rol' => AdministradorGeneral::ROL_ADMINISTRADOR_GENERAL,
                'est' => AdministradorGeneral::ESTADO_ACTIVO,
                'cor_ver' => true,
                'cor_ver_en' => now(),
            ]);

            // Asignar rol de administrador general
            $rolAdminGeneral = Rol::where('nom', 'Administrador General')->first();
            if ($rolAdminGeneral) {
                $administrador->roles()->attach($rolAdminGeneral->id, [
                    'asig_por' => $administrador->id,
                    'asig_en' => now(),
                    'act' => true
                ]);
            }

            DB::commit();

            // Log de creación
            Log::crear('crear_administrador_general', 'usuarios', $administrador->id, 
                      "Administrador general creado: {$administrador->nombre_completo}");

            return response()->json([
                'success' => true,
                'data' => $administrador->load('roles'),
                'message' => 'Administrador general creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear administrador general: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gestionar usuarios del sistema
     */
    public function gestionarUsuarios(Request $request): JsonResponse
    {
        try {
            $query = Usuario::with(['roles']);

            // Filtros
            if ($request->has('rol')) {
                $query->where('rol', $request->rol);
            }

            if ($request->has('est')) {
                $query->where('est', $request->est);
            }

            if ($request->has('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('nom', 'like', "%{$buscar}%")
                      ->orWhere('ape', 'like', "%{$buscar}%")
                      ->orWhere('cor', 'like', "%{$buscar}%")
                      ->orWhere('doc', 'like', "%{$buscar}%");
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'created_at');
            $direccion = $request->get('direccion', 'desc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $porPagina = $request->get('por_pagina', 15);
            $usuarios = $query->paginate($porPagina);

            return response()->json([
                'success' => true,
                'data' => $usuarios,
                'message' => 'Usuarios obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear usuario desde administrador general
     */
    public function crearUsuario(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nom' => 'required|string|max:100',
                'ape' => 'required|string|max:100',
                'cor' => 'required|email|max:150|unique:usu,cor',
                'con' => 'required|string|min:8',
                'tel' => 'nullable|string|max:20',
                'doc' => 'required|string|max:20|unique:usu,doc',
                'tip_doc' => 'required|in:cc,ce,ti,pp,nit',
                'fec_nac' => 'nullable|date',
                'dir' => 'nullable|string|max:200',
                'ciu' => 'nullable|string|max:100',
                'dep' => 'nullable|string|max:100',
                'gen' => 'nullable|in:m,f,o,n',
                'rol' => 'required|in:adm,ope,cli',
                'est' => 'nullable|in:act,ina,sus,pen',
                'roles' => 'nullable|array',
                'roles.*' => 'exists:rol,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $usuario = Usuario::create([
                'nom' => $request->nom,
                'ape' => $request->ape,
                'cor' => $request->cor,
                'con' => Hash::make($request->con),
                'tel' => $request->tel,
                'doc' => $request->doc,
                'tip_doc' => $request->tip_doc,
                'fec_nac' => $request->fec_nac,
                'dir' => $request->dir,
                'ciu' => $request->ciu,
                'dep' => $request->dep,
                'gen' => $request->gen,
                'rol' => $request->rol,
                'est' => $request->est ?? Usuario::ESTADO_ACTIVO,
                'creado_por' => $request->user()->id,
            ]);

            // Asignar roles si se proporcionan
            if ($request->has('roles')) {
                $usuario->roles()->attach($request->roles, [
                    'asig_por' => $request->user()->id,
                    'asig_en' => now(),
                    'act' => true
                ]);
            }

            DB::commit();

            // Log de creación
            Log::crear('crear_usuario', 'usuarios', $usuario->id, 
                      "Usuario creado por administrador general: {$usuario->nombre_completo}");

            return response()->json([
                'success' => true,
                'data' => $usuario->load('roles'),
                'message' => 'Usuario creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asignar rol a usuario
     */
    public function asignarRol(Request $request, $usuarioId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'rol_id' => 'required|exists:rol,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $usuario = Usuario::findOrFail($usuarioId);
            $rol = Rol::findOrFail($request->rol_id);

            // Verificar si ya tiene el rol
            if ($usuario->roles()->where('rol_id', $request->rol_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario ya tiene este rol asignado'
                ], 422);
            }

            $usuario->roles()->attach($request->rol_id, [
                'asig_por' => $request->user()->id,
                'asig_en' => now(),
                'act' => true
            ]);

            // Log de asignación
            Log::crear('asignar_rol', 'usuarios', $usuarioId, 
                      "Rol '{$rol->nom}' asignado a {$usuario->nombre_completo}");

            return response()->json([
                'success' => true,
                'data' => $usuario->load('roles'),
                'message' => 'Rol asignado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Quitar rol de usuario
     */
    public function quitarRol(Request $request, $usuarioId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'rol_id' => 'required|exists:rol,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $usuario = Usuario::findOrFail($usuarioId);
            $rol = Rol::findOrFail($request->rol_id);

            $usuario->roles()->detach($request->rol_id);

            // Log de quitar rol
            Log::crear('quitar_rol', 'usuarios', $usuarioId, 
                      "Rol '{$rol->nom}' quitado a {$usuario->nombre_completo}");

            return response()->json([
                'success' => true,
                'data' => $usuario->load('roles'),
                'message' => 'Rol quitado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al quitar rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar estado de usuario
     */
    public function cambiarEstadoUsuario(Request $request, $usuarioId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'estado' => 'required|in:act,ina,sus,pen'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estado inválido',
                    'errors' => $validator->errors()
                ], 422);
            }

            $usuario = Usuario::findOrFail($usuarioId);
            $estadoAnterior = $usuario->est;
            
            $usuario->update(['est' => $request->estado]);

            // Log de cambio de estado
            Log::crear('cambiar_estado_usuario', 'usuarios', $usuarioId, 
                      "Estado cambiado de '{$estadoAnterior}' a '{$request->estado}'");

            return response()->json([
                'success' => true,
                'data' => $usuario,
                'message' => 'Estado de usuario actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gestionar roles del sistema
     */
    public function gestionarRoles(Request $request): JsonResponse
    {
        try {
            $query = Rol::with(['usuarios']);

            // Filtros
            if ($request->has('est')) {
                $query->where('est', $request->est);
            }

            if ($request->has('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('nom', 'like', "%{$buscar}%")
                      ->orWhere('des', 'like', "%{$buscar}%");
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'nom');
            $direccion = $request->get('direccion', 'asc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $porPagina = $request->get('por_pagina', 15);
            $roles = $query->paginate($porPagina);

            return response()->json([
                'success' => true,
                'data' => $roles,
                'message' => 'Roles obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener roles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gestionar permisos del sistema
     */
    public function gestionarPermisos(Request $request): JsonResponse
    {
        try {
            $query = PermisoMejorado::with(['roles']);

            // Filtros
            if ($request->has('mod')) {
                $query->where('mod', $request->mod);
            }

            if ($request->has('est')) {
                $query->where('est', $request->est);
            }

            if ($request->has('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('nom', 'like', "%{$buscar}%")
                      ->orWhere('des', 'like', "%{$buscar}%");
                });
            }

            // Ordenamiento
            $orden = $request->get('orden', 'nom');
            $direccion = $request->get('direccion', 'asc');
            $query->orderBy($orden, $direccion);

            // Paginación
            $porPagina = $request->get('por_pagina', 15);
            $permisos = $query->paginate($porPagina);

            return response()->json([
                'success' => true,
                'data' => $permisos,
                'message' => 'Permisos obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener permisos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asignar permisos a rol
     */
    public function asignarPermisosRol(Request $request, $rolId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'permisos' => 'required|array',
                'permisos.*' => 'exists:perm,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $rol = Rol::findOrFail($rolId);
            $rol->permisos()->sync($request->permisos);

            // Log de asignación de permisos
            Log::crear('asignar_permisos_rol', 'roles', $rolId, 
                      "Permisos asignados al rol '{$rol->nom}'");

            return response()->json([
                'success' => true,
                'data' => $rol->load('permisos'),
                'message' => 'Permisos asignados exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar permisos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas del sistema
     */
    public function estadisticasSistema(): JsonResponse
    {
        try {
            $estadisticas = [
                'usuarios' => [
                    'total' => Usuario::count(),
                    'activos' => Usuario::where('est', 'act')->count(),
                    'inactivos' => Usuario::where('est', 'ina')->count(),
                    'pendientes' => Usuario::where('est', 'pen')->count(),
                    'suspendidos' => Usuario::where('est', 'sus')->count(),
                    'por_rol' => Usuario::selectRaw('rol, COUNT(*) as total')
                        ->groupBy('rol')
                        ->get()
                ],
                'roles' => [
                    'total' => Rol::count(),
                    'activos' => Rol::where('est', 'act')->count(),
                    'inactivos' => Rol::where('est', 'ina')->count()
                ],
                'permisos' => [
                    'total' => PermisoMejorado::count(),
                    'activos' => PermisoMejorado::where('est', 'act')->count(),
                    'inactivos' => PermisoMejorado::where('est', 'ina')->count(),
                'por_modulo' => PermisoMejorado::selectRaw('`mod`, COUNT(*) as total')
                    ->groupBy('mod')
                    ->get()
                ],
                'actividad' => [
                    'logs_hoy' => Log::whereDate('created_at', today())->count(),
                    'logs_semana' => Log::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    'logs_mes' => Log::whereMonth('created_at', now()->month)->count()
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas del sistema obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Inicializar permisos del sistema
     */
    public function inicializarPermisos(): JsonResponse
    {
        try {
            PermisoMejorado::crearPermisosSistema();

            return response()->json([
                'success' => true,
                'message' => 'Permisos del sistema inicializados exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al inicializar permisos: ' . $e->getMessage()
            ], 500);
        }
    }
}
