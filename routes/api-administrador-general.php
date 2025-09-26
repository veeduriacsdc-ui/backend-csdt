<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdministradorGeneralController;
use App\Http\Controllers\Api\RegistroPublicoController;

/*
|--------------------------------------------------------------------------
| API Routes - Administrador General
|--------------------------------------------------------------------------
|
| Rutas específicas para el administrador general del sistema CSDT
| Incluye gestión completa de usuarios, roles y permisos
|
*/

Route::prefix('administrador-general')->middleware(['auth:sanctum'])->group(function () {
    
    // Gestión de administradores generales
    Route::get('/', [AdministradorGeneralController::class, 'index']);
    Route::post('/', [AdministradorGeneralController::class, 'store']);
    
    // Gestión de usuarios del sistema
    Route::prefix('usuarios')->group(function () {
        Route::get('/', [AdministradorGeneralController::class, 'gestionarUsuarios']);
        Route::post('/', [AdministradorGeneralController::class, 'crearUsuario']);
        Route::post('{id}/asignar-rol', [AdministradorGeneralController::class, 'asignarRol']);
        Route::delete('{id}/quitar-rol', [AdministradorGeneralController::class, 'quitarRol']);
        Route::patch('{id}/cambiar-estado', [AdministradorGeneralController::class, 'cambiarEstadoUsuario']);
    });
    
    // Gestión de roles
    Route::prefix('roles')->group(function () {
        Route::get('/', [AdministradorGeneralController::class, 'gestionarRoles']);
        Route::post('{id}/asignar-permisos', [AdministradorGeneralController::class, 'asignarPermisosRol']);
    });
    
    // Gestión de permisos
    Route::prefix('permisos')->group(function () {
        Route::get('/', [AdministradorGeneralController::class, 'gestionarPermisos']);
        Route::post('/inicializar', [AdministradorGeneralController::class, 'inicializarPermisos']);
    });
    
    // Estadísticas del sistema
    Route::get('/estadisticas', [AdministradorGeneralController::class, 'estadisticasSistema']);
});

/*
|--------------------------------------------------------------------------
| Rutas de Registro por Tipo de Usuario
|--------------------------------------------------------------------------
*/

// Registro de usuarios (público)
Route::prefix('registro')->group(function () {
    Route::post('/cliente', [RegistroPublicoController::class, 'registrarCliente']);
    Route::post('/operador', [RegistroPublicoController::class, 'registrarOperador']);
    Route::post('/administrador', [RegistroPublicoController::class, 'registrarAdministrador']);
    Route::post('/validar-campos', [RegistroPublicoController::class, 'validarCampos']);
});

/*
|--------------------------------------------------------------------------
| Rutas de Validación de Roles y Permisos
|--------------------------------------------------------------------------
*/

Route::prefix('validacion')->middleware(['auth:sanctum'])->group(function () {
    
    // Verificar rol del usuario
    Route::get('/rol', function (Request $request) {
        $usuario = $request->user();
        return response()->json([
            'success' => true,
            'data' => [
                'rol' => $usuario->rol,
                'es_administrador_general' => $usuario->esAdministradorGeneral(),
                'es_administrador' => $usuario->esAdministrador(),
                'es_operador' => $usuario->esOperador(),
                'es_cliente' => $usuario->esCliente(),
            ]
        ]);
    });
    
    // Verificar permisos del usuario
    Route::get('/permisos', function (Request $request) {
        $usuario = $request->user();
        return response()->json([
            'success' => true,
            'data' => [
                'puede_gestionar_usuarios' => $usuario->puedeGestionarUsuarios(),
                'puede_gestionar_roles' => $usuario->puedeGestionarRoles(),
                'puede_gestionar_permisos' => $usuario->puedeGestionarPermisos(),
                'puede_gestionar_veedurias' => $usuario->puedeGestionarVeedurias(),
                'puede_gestionar_donaciones' => $usuario->puedeGestionarDonaciones(),
                'puede_gestionar_tareas' => $usuario->puedeGestionarTareas(),
                'puede_ver_logs' => $usuario->puedeVerLogs(),
                'puede_ver_estadisticas' => $usuario->puedeVerEstadisticas(),
            ]
        ]);
    });
    
    // Verificar permiso específico
    Route::post('/permiso', function (Request $request) {
        $usuario = $request->user();
        $permiso = $request->input('permiso');
        
        return response()->json([
            'success' => true,
            'data' => [
                'permiso' => $permiso,
                'tiene_permiso' => $usuario->tienePermiso($permiso)
            ]
        ]);
    });
});

/*
|--------------------------------------------------------------------------
| Rutas de Dashboard por Rol
|--------------------------------------------------------------------------
*/

Route::prefix('dashboard')->middleware(['auth:sanctum'])->group(function () {
    
    // Dashboard del administrador general
    Route::get('/administrador-general', function (Request $request) {
        $usuario = $request->user();
        
        if (!$usuario->esAdministradorGeneral()) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para acceder a este dashboard'
            ], 403);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'tipo' => 'administrador_general',
                'titulo' => 'Dashboard Administrador General',
                'modulos' => [
                    'usuarios' => true,
                    'roles' => true,
                    'permisos' => true,
                    'veedurias' => true,
                    'donaciones' => true,
                    'tareas' => true,
                    'archivos' => true,
                    'configuraciones' => true,
                    'logs' => true,
                    'estadisticas' => true,
                    'ia' => true
                ]
            ]
        ]);
    });
    
    // Dashboard del administrador
    Route::get('/administrador', function (Request $request) {
        $usuario = $request->user();
        
        if (!$usuario->esAdministrador()) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para acceder a este dashboard'
            ], 403);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'tipo' => 'administrador',
                'titulo' => 'Dashboard Administrador',
                'modulos' => [
                    'usuarios' => $usuario->puedeGestionarUsuarios(),
                    'veedurias' => $usuario->puedeGestionarVeedurias(),
                    'donaciones' => $usuario->puedeGestionarDonaciones(),
                    'tareas' => $usuario->puedeGestionarTareas(),
                    'archivos' => true,
                    'estadisticas' => $usuario->puedeVerEstadisticas(),
                    'ia' => true
                ]
            ]
        ]);
    });
    
    // Dashboard del operador
    Route::get('/operador', function (Request $request) {
        $usuario = $request->user();
        
        if (!$usuario->esOperador()) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para acceder a este dashboard'
            ], 403);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'tipo' => 'operador',
                'titulo' => 'Dashboard Operador',
                'modulos' => [
                    'veedurias' => true,
                    'tareas' => true,
                    'archivos' => true,
                    'estadisticas' => $usuario->puedeVerEstadisticas()
                ]
            ]
        ]);
    });
    
    // Dashboard del cliente
    Route::get('/cliente', function (Request $request) {
        $usuario = $request->user();
        
        if (!$usuario->esCliente()) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para acceder a este dashboard'
            ], 403);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'tipo' => 'cliente',
                'titulo' => 'Dashboard Cliente',
                'modulos' => [
                    'veedurias' => true,
                    'donaciones' => true,
                    'archivos' => true
                ]
            ]
        ]);
    });
});
