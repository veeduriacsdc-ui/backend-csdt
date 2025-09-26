<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UsuarioController;

/*
|--------------------------------------------------------------------------
| API Routes - Usuarios
|--------------------------------------------------------------------------
|
| Rutas para la gestión de usuarios del sistema CSDT
| Incluye operaciones CRUD completas y funcionalidades adicionales
|
*/

Route::prefix('usuarios')->group(function () {
    
    // Rutas públicas (sin autenticación)
    Route::post('validar', [UsuarioController::class, 'validar']);
    Route::get('buscar', [UsuarioController::class, 'buscar']);
    
    // Rutas protegidas (requieren autenticación)
    Route::middleware(['auth:sanctum'])->group(function () {
        
        // CRUD básico
        Route::get('/', [UsuarioController::class, 'index']);                    // Listar usuarios
        Route::get('/activos', [UsuarioController::class, 'usuariosActivos']);  // Usuarios activos
        Route::post('/', [UsuarioController::class, 'store']);                  // Crear usuario
        Route::get('{id}', [UsuarioController::class, 'show']);                 // Mostrar usuario
        Route::put('{id}', [UsuarioController::class, 'update']);               // Actualizar usuario
        Route::delete('{id}', [UsuarioController::class, 'destroy']);           // Eliminar usuario
        
        // Operaciones específicas
        Route::patch('{id}/activar', [UsuarioController::class, 'activar']);     // Activar usuario
        Route::patch('{id}/desactivar', [UsuarioController::class, 'desactivar']); // Desactivar usuario
        Route::patch('{id}/verificar-correo', [UsuarioController::class, 'verificarCorreo']); // Verificar correo
        Route::put('{id}/cambiar-estado', [UsuarioController::class, 'cambiarEstado']); // Cambiar estado
        
        // Estadísticas y reportes
        Route::get('estadisticas/general', [UsuarioController::class, 'estadisticas']); // Estadísticas generales
        
        // Rutas por rol
        Route::prefix('clientes')->group(function () {
            Route::get('/', [UsuarioController::class, 'index'])->where('rol', 'cli');
            Route::get('estadisticas', [UsuarioController::class, 'estadisticas'])->where('rol', 'cli');
        });
        
        Route::prefix('operadores')->group(function () {
            Route::get('/', [UsuarioController::class, 'index'])->where('rol', 'ope');
            Route::get('estadisticas', [UsuarioController::class, 'estadisticas'])->where('rol', 'ope');
        });
        
        Route::prefix('administradores')->group(function () {
            Route::get('/', [UsuarioController::class, 'index'])->where('rol', 'adm');
            Route::get('estadisticas', [UsuarioController::class, 'estadisticas'])->where('rol', 'adm');
        });
        
        // Rutas por estado
        Route::prefix('activos')->group(function () {
            Route::get('/', [UsuarioController::class, 'usuariosActivos']);
        });
        
        Route::prefix('inactivos')->group(function () {
            Route::get('/', [UsuarioController::class, 'index'])->where('est', 'ina');
        });
        
        Route::prefix('pendientes')->group(function () {
            Route::get('/', [UsuarioController::class, 'index'])->where('est', 'pen');
        });
        
        Route::prefix('suspendidos')->group(function () {
            Route::get('/', [UsuarioController::class, 'index'])->where('est', 'sus');
        });
    });
    
    // Rutas de administrador (requieren rol de administrador)
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('masivo', [UsuarioController::class, 'store']); // Creación masiva
        Route::post('importar', [UsuarioController::class, 'store']); // Importar desde archivo
        Route::get('exportar', [UsuarioController::class, 'index']); // Exportar usuarios
        Route::post('{id}/asignar-rol', [UsuarioController::class, 'update']); // Asignar rol
        Route::delete('{id}/quitar-rol', [UsuarioController::class, 'update']); // Quitar rol
    });
});

/*
|--------------------------------------------------------------------------
| Rutas de Autenticación de Usuarios
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    
    // Registro de usuarios
    Route::post('registro', [UsuarioController::class, 'store']);
    
    // Verificación de correo
    Route::get('verificar/{id}', [UsuarioController::class, 'verificarCorreo']);
    
    // Recuperación de contraseña
    Route::post('recuperar', [UsuarioController::class, 'buscar']); // Buscar por correo
    Route::post('resetear', [UsuarioController::class, 'update']); // Resetear contraseña
    
    // Perfil de usuario
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('perfil', [UsuarioController::class, 'show']); // Obtener perfil
        Route::put('perfil', [UsuarioController::class, 'update']); // Actualizar perfil
        Route::patch('perfil/verificar', [UsuarioController::class, 'verificarCorreo']); // Verificar correo
    });
});

/*
|--------------------------------------------------------------------------
| Rutas de Búsqueda Avanzada
|--------------------------------------------------------------------------
*/

Route::prefix('buscar')->group(function () {
    
    // Búsqueda general
    Route::get('usuarios', [UsuarioController::class, 'buscar']);
    
    // Búsqueda por criterios específicos
    Route::get('por-nombre', [UsuarioController::class, 'buscar'])->where('campo', 'nom');
    Route::get('por-correo', [UsuarioController::class, 'buscar'])->where('campo', 'cor');
    Route::get('por-documento', [UsuarioController::class, 'buscar'])->where('campo', 'doc');
    Route::get('por-rol', [UsuarioController::class, 'buscar'])->where('campo', 'rol');
    Route::get('por-estado', [UsuarioController::class, 'buscar'])->where('campo', 'est');
    
    // Búsqueda combinada
    Route::get('avanzada', [UsuarioController::class, 'index']); // Con filtros múltiples
});

/*
|--------------------------------------------------------------------------
| Rutas de Validación
|--------------------------------------------------------------------------
*/

Route::prefix('validar')->group(function () {
    
    // Validaciones individuales
    Route::post('correo', [UsuarioController::class, 'validar'])->where('campo', 'cor');
    Route::post('documento', [UsuarioController::class, 'validar'])->where('campo', 'doc');
    Route::post('telefono', [UsuarioController::class, 'validar'])->where('campo', 'tel');
    
    // Validación completa
    Route::post('completa', [UsuarioController::class, 'validar']);
});

/*
|--------------------------------------------------------------------------
| Rutas de Reportes y Estadísticas
|--------------------------------------------------------------------------
*/

Route::prefix('reportes')->middleware(['auth:sanctum'])->group(function () {
    
    // Estadísticas generales
    Route::get('estadisticas', [UsuarioController::class, 'estadisticas']);
    
    // Reportes por período
    Route::get('por-fecha', [UsuarioController::class, 'index']); // Con filtro de fecha
    Route::get('por-mes', [UsuarioController::class, 'index']); // Agrupado por mes
    Route::get('por-ano', [UsuarioController::class, 'index']); // Agrupado por año
    
    // Reportes por rol
    Route::get('clientes', [UsuarioController::class, 'index'])->where('rol', 'cli');
    Route::get('operadores', [UsuarioController::class, 'index'])->where('rol', 'ope');
    Route::get('administradores', [UsuarioController::class, 'index'])->where('rol', 'adm');
    
    // Reportes por estado
    Route::get('activos', [UsuarioController::class, 'index'])->where('est', 'act');
    Route::get('inactivos', [UsuarioController::class, 'index'])->where('est', 'ina');
    Route::get('pendientes', [UsuarioController::class, 'index'])->where('est', 'pen');
    Route::get('suspendidos', [UsuarioController::class, 'index'])->where('est', 'sus');
    
    // Reportes de actividad
    Route::get('actividad', [UsuarioController::class, 'index']); // Usuarios activos
    Route::get('inactividad', [UsuarioController::class, 'index']); // Usuarios inactivos
    Route::get('verificados', [UsuarioController::class, 'index']); // Correos verificados
    Route::get('no-verificados', [UsuarioController::class, 'index']); // Correos no verificados
});
