<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\PermisoMejorado;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ValidacionController extends Controller
{
    /**
     * Validar permisos de un usuario
     */
    public function validarPermisos(Request $request): JsonResponse
    {
        try {
            $validator = \Validator::make($request->all(), [
                'usuario_id' => 'sometimes|integer|exists:usu,id',
                'permisos' => 'required|array',
                'permisos.*' => 'string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Si no se proporciona usuario_id, usar el usuario autenticado
            $usuarioId = $request->usuario_id ?? Auth::id();
            $permisosRequeridos = $request->permisos;

            $usuario = Usuario::find($usuarioId);
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $resultados = [];
            $tieneTodosLosPermisos = true;

            foreach ($permisosRequeridos as $permiso) {
                $tienePermiso = $usuario->tienePermiso($permiso);
                $resultados[] = [
                    'permiso' => $permiso,
                    'tiene_permiso' => $tienePermiso,
                    'nivel' => $this->obtenerNivelPermiso($permiso)
                ];

                if (!$tienePermiso) {
                    $tieneTodosLosPermisos = false;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'usuario' => [
                        'id' => $usuario->id,
                        'nombre' => $usuario->nom . ' ' . $usuario->ape,
                        'email' => $usuario->cor,
                        'rol' => $usuario->rol
                    ],
                    'permisos' => $resultados,
                    'tiene_todos_los_permisos' => $tieneTodosLosPermisos,
                    'resumen' => [
                        'total_permisos' => count($permisosRequeridos),
                        'permisos_otorgados' => count(array_filter($resultados, fn($p) => $p['tiene_permiso'])),
                        'permisos_denegados' => count(array_filter($resultados, fn($p) => !$p['tiene_permiso']))
                    ]
                ],
                'message' => 'Validación de permisos completada'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al validar permisos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar rol de un usuario
     */
    public function validarRol(Request $request): JsonResponse
    {
        try {
            $validator = \Validator::make($request->all(), [
                'usuario_id' => 'required|integer|exists:usu,id',
                'roles' => 'required|array',
                'roles.*' => 'string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $usuarioId = $request->usuario_id;
            $rolesRequeridos = $request->roles;

            $usuario = Usuario::find($usuarioId);
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $resultados = [];
            $tieneAlgunRol = false;

            foreach ($rolesRequeridos as $rol) {
                $tieneRol = $usuario->tieneRol($rol);
                $resultados[] = [
                    'rol' => $rol,
                    'tiene_rol' => $tieneRol,
                    'descripcion' => $this->obtenerDescripcionRol($rol)
                ];

                if ($tieneRol) {
                    $tieneAlgunRol = true;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'usuario' => [
                        'id' => $usuario->id,
                        'nombre' => $usuario->nom . ' ' . $usuario->ape,
                        'email' => $usuario->cor,
                        'rol_actual' => $usuario->rol
                    ],
                    'roles' => $resultados,
                    'tiene_algun_rol' => $tieneAlgunRol,
                    'resumen' => [
                        'total_roles' => count($rolesRequeridos),
                        'roles_otorgados' => count(array_filter($resultados, fn($r) => $r['tiene_rol'])),
                        'roles_denegados' => count(array_filter($resultados, fn($r) => !$r['tiene_rol']))
                    ]
                ],
                'message' => 'Validación de roles completada'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al validar roles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener permisos de un usuario
     */
    public function obtenerPermisosUsuario(Request $request, $usuarioId): JsonResponse
    {
        try {
            $usuario = Usuario::find($usuarioId);
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $permisos = $usuario->obtenerPermisos();
            $roles = $usuario->roles;

            return response()->json([
                'success' => true,
                'data' => [
                    'usuario' => [
                        'id' => $usuario->id,
                        'nombre' => $usuario->nom . ' ' . $usuario->ape,
                        'email' => $usuario->cor,
                        'rol' => $usuario->rol
                    ],
                    'roles' => $roles->map(function($rol) {
                        return [
                            'id' => $rol->id,
                            'nombre' => $rol->nom,
                            'descripcion' => $rol->des,
                            'estado' => $rol->est
                        ];
                    }),
                    'permisos' => $permisos->map(function($permiso) {
                        return [
                            'id' => $permiso->id,
                            'nombre' => $permiso->nom,
                            'slug' => $permiso->slug,
                            'descripcion' => $permiso->des,
                            'categoria' => $permiso->cat,
                            'modulo' => $permiso->mod,
                            'funcion' => $permiso->fun,
                            'recurso' => $permiso->rec,
                            'accion' => $permiso->acc,
                            'nivel_requerido' => $permiso->niv_req
                        ];
                    }),
                    'resumen' => [
                        'total_roles' => $roles->count(),
                        'total_permisos' => $permisos->count(),
                        'es_administrador_general' => $usuario->esAdministradorGeneral()
                    ]
                ],
                'message' => 'Permisos del usuario obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener permisos del usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener nivel de un permiso
     */
    private function obtenerNivelPermiso($permiso)
    {
        $niveles = [
            'usuarios_crear' => 'Alto',
            'usuarios_editar' => 'Alto',
            'usuarios_eliminar' => 'Alto',
            'veedurias_crear' => 'Medio',
            'veedurias_editar' => 'Medio',
            'donaciones_ver' => 'Bajo',
            'tareas_crear' => 'Medio',
            'logs_ver' => 'Alto',
            'configuraciones_editar' => 'Alto'
        ];

        return $niveles[$permiso] ?? 'Medio';
    }

    /**
     * Obtener descripción de un rol
     */
    private function obtenerDescripcionRol($rol)
    {
        $descripciones = [
            'adm_gen' => 'Administrador General - Acceso total al sistema',
            'adm' => 'Administrador - Gestión de usuarios y veedurías',
            'ope' => 'Operador - Gestión de veedurías y tareas',
            'cli' => 'Cliente - Creación y seguimiento de veedurías'
        ];

        return $descripciones[$rol] ?? 'Rol no definido';
    }
}
