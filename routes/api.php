<?php

use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\VeeduriaController;
use App\Http\Controllers\Api\DonacionController;
use App\Http\Controllers\Api\TareaController;
use App\Http\Controllers\Api\ArchivoController;
use App\Http\Controllers\Api\RolController;
use App\Http\Controllers\Api\ConfiguracionController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RegistroControlador;
use App\Http\Controllers\Api\PublicoController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

// Incluir rutas específicas de módulos
require_once __DIR__ . '/api-usuarios.php';
require_once __DIR__ . '/api-administrador-general.php';

// Rutas públicas (sin autenticación)
Route::prefix('publico')->group(function () {
    Route::get('tipos-veeduria', [PublicoController::class, 'tiposVeeduria']);
    Route::get('estados-veeduria', [PublicoController::class, 'estadosVeeduria']);
    Route::get('categorias-veeduria', [PublicoController::class, 'categoriasVeeduria']);
    Route::get('tipos-documento', [PublicoController::class, 'tiposDocumento']);
    Route::get('generos', [PublicoController::class, 'generos']);
    Route::get('prioridades-tarea', [PublicoController::class, 'prioridadesTarea']);
    Route::get('estados-tarea', [PublicoController::class, 'estadosTarea']);
    Route::get('tipos-donacion', [PublicoController::class, 'tiposDonacion']);
    Route::get('estados-donacion', [PublicoController::class, 'estadosDonacion']);
});

// Rutas de Dashboard
Route::prefix('dashboard')->middleware(['auth:sanctum'])->group(function () {
    Route::get('general', [DashboardController::class, 'general']);
    Route::get('administrador', [DashboardController::class, 'administrador']);
    Route::get('operador', [DashboardController::class, 'operador']);
    Route::get('cliente', [DashboardController::class, 'cliente']);
});

// Rutas del Sistema de IA
Route::prefix('ia')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/estadisticas', [App\Http\Controllers\Api\SistemaIAControllerMejorado::class, 'estadisticasIA']);
    Route::post('/recomendaciones', [App\Http\Controllers\Api\SistemaIAControllerMejorado::class, 'obtenerRecomendaciones']);
    Route::post('/generar-narracion', [App\Http\Controllers\Api\SistemaIAControllerMejorado::class, 'generarNarracion']);
    Route::post('/analizar-veeduria/{id}', [App\Http\Controllers\Api\SistemaIAControllerMejorado::class, 'analizarVeeduria']);
});

// Rutas de Logs
Route::prefix('logs')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [App\Http\Controllers\Api\LogController::class, 'index']);
    Route::get('/recientes/{dias?}', [App\Http\Controllers\Api\LogController::class, 'recientes']);
    Route::get('/estadisticas', [App\Http\Controllers\Api\LogController::class, 'estadisticas']);
});

// Rutas de Validación
Route::prefix('validacion')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/permisos', [App\Http\Controllers\Api\ValidacionController::class, 'validarPermisos']);
    Route::post('/rol', [App\Http\Controllers\Api\ValidacionController::class, 'validarRol']);
    Route::get('/usuario/{id}/permisos', [App\Http\Controllers\Api\ValidacionController::class, 'obtenerPermisosUsuario']);
});

// Rutas de Archivos
Route::prefix('archivos')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [App\Http\Controllers\Api\ArchivoController::class, 'index']);
    Route::get('/estadisticas', [App\Http\Controllers\Api\ArchivoController::class, 'estadisticas']);
    Route::get('/{id}', [App\Http\Controllers\Api\ArchivoController::class, 'show']);
    Route::post('/', [App\Http\Controllers\Api\ArchivoController::class, 'store']);
    Route::put('/{id}', [App\Http\Controllers\Api\ArchivoController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\ArchivoController::class, 'destroy']);
});

// Rutas de Dashboard
Route::prefix('dashboard')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [App\Http\Controllers\Api\DashboardController::class, 'general']);
    Route::get('/administrador-general', [App\Http\Controllers\Api\DashboardController::class, 'administradorGeneral']);
    Route::get('/administrador', [App\Http\Controllers\Api\DashboardController::class, 'administrador']);
    Route::get('/operador', [App\Http\Controllers\Api\DashboardController::class, 'operador']);
    Route::get('/cliente', [App\Http\Controllers\Api\DashboardController::class, 'cliente']);
});

/*
|--------------------------------------------------------------------------
| API Routes - CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
|--------------------------------------------------------------------------
|
| Aquí se registran todas las rutas de la API de la aplicación.
| Las rutas se cargan a través del RouteServiceProvider.
|
*/

// Ruta de verificación de salud de la API
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API del CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL funcionando correctamente',
        'timestamp' => now()->toISOString(),
        'version' => '2.0.0',
    ]);
});

// Rutas de autenticación
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    Route::post('/cambiar-contrasena', [AuthController::class, 'cambiarContrasena'])->middleware('auth:sanctum');
    Route::post('/recuperar-contrasena', [AuthController::class, 'recuperarContrasena']);
    Route::post('/resetear-contrasena', [AuthController::class, 'resetearContrasena']);
    Route::post('/verificar-email', [AuthController::class, 'verificarEmail']);
});

// Rutas de registro
Route::prefix('registro')->group(function () {
    Route::post('/validar-campos', [RegistroControlador::class, 'validarCampos']);
    Route::post('/registrar', [RegistroControlador::class, 'registrar']);
    Route::post('/verificar-email', [RegistroControlador::class, 'verificarEmail']);
    Route::get('/pendientes', [RegistroControlador::class, 'obtenerPendientes'])->middleware('auth:sanctum');
    Route::post('/aprobar/{id}', [RegistroControlador::class, 'aprobar'])->middleware('auth:sanctum');
    Route::post('/rechazar/{id}', [RegistroControlador::class, 'rechazar'])->middleware('auth:sanctum');
});

// Rutas públicas (sin autenticación)
Route::prefix('publico')->group(function () {
    // Datos de referencia públicos
    Route::get('/tipos-veeduria', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'pet' => 'Petición',
                'que' => 'Queja',
                'rec' => 'Reclamo',
                'sug' => 'Sugerencia',
                'fel' => 'Felicitación',
                'den' => 'Denuncia',
            ],
            'message' => 'Tipos de veeduría obtenidos exitosamente',
        ]);
    });

    Route::get('/estados-veeduria', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'pen' => 'Pendiente',
                'pro' => 'En Proceso',
                'rad' => 'Radicada',
                'cer' => 'Cerrada',
                'can' => 'Cancelada',
            ],
            'message' => 'Estados de veeduría obtenidos exitosamente',
        ]);
    });

    Route::get('/prioridades-tarea', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'baj' => 'Baja',
                'med' => 'Media',
                'alt' => 'Alta',
                'urg' => 'Urgente',
            ],
            'message' => 'Prioridades de tarea obtenidas exitosamente',
        ]);
    });

    Route::get('/categorias-veeduria', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'inf' => 'Infraestructura',
                'ser' => 'Servicios Públicos',
                'seg' => 'Seguridad',
                'edu' => 'Educación',
                'sal' => 'Salud',
                'tra' => 'Transporte',
                'amb' => 'Medio Ambiente',
                'otr' => 'Otros',
            ],
            'message' => 'Categorías de veeduría obtenidas exitosamente',
        ]);
    });

    Route::get('/tipos-documento', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'cc' => 'Cédula de Ciudadanía',
                'ce' => 'Cédula de Extranjería',
                'ti' => 'Tarjeta de Identidad',
                'pp' => 'Pasaporte',
                'nit' => 'NIT',
            ],
            'message' => 'Tipos de documento obtenidos exitosamente',
        ]);
    });

    Route::get('/generos', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'm' => 'Masculino',
                'f' => 'Femenino',
                'o' => 'Otro',
                'n' => 'No Especificado',
            ],
            'message' => 'Géneros obtenidos exitosamente',
        ]);
    });
});

// Rutas protegidas por autenticación
Route::middleware('auth:sanctum')->group(function () {

    // Rutas de usuarios
    Route::prefix('usuarios')->group(function () {
        Route::get('/', [UsuarioController::class, 'index']);
        Route::get('/{id}', [UsuarioController::class, 'show']);
        Route::post('/', [UsuarioController::class, 'store']);
        Route::put('/{id}', [UsuarioController::class, 'update']);
        Route::delete('/{id}', [UsuarioController::class, 'destroy']);
        Route::post('/{id}/restaurar', [UsuarioController::class, 'restore']);
        Route::put('/{id}/cambiar-estado', [UsuarioController::class, 'cambiarEstado']);
        Route::post('/{id}/verificar-correo', [UsuarioController::class, 'verificarCorreo']);
        Route::get('/{id}/estadisticas', [UsuarioController::class, 'estadisticas']);
        Route::get('/buscar/termino', [UsuarioController::class, 'buscar']);
    });

    // Rutas de veedurías
    Route::prefix('veedurias')->group(function () {
        Route::get('/', [VeeduriaController::class, 'index']);
        Route::get('/{id}', [VeeduriaController::class, 'show']);
        Route::post('/', [VeeduriaController::class, 'store']);
        Route::put('/{id}', [VeeduriaController::class, 'update']);
        Route::delete('/{id}', [VeeduriaController::class, 'destroy']);
        Route::post('/{id}/restaurar', [VeeduriaController::class, 'restore']);
        Route::post('/{id}/radicar', [VeeduriaController::class, 'radicar']);
        Route::post('/{id}/cerrar', [VeeduriaController::class, 'cerrar']);
        Route::post('/{id}/cancelar', [VeeduriaController::class, 'cancelar']);
        Route::post('/{id}/asignar-operador', [VeeduriaController::class, 'asignarOperador']);
        Route::get('/{id}/estadisticas', [VeeduriaController::class, 'estadisticas']);
        Route::get('/buscar/termino', [VeeduriaController::class, 'buscar']);
    });

    // Rutas de donaciones
    Route::prefix('donaciones')->group(function () {
        Route::get('/', [DonacionController::class, 'index']);
        Route::get('/{id}', [DonacionController::class, 'show']);
        Route::post('/', [DonacionController::class, 'store']);
        Route::put('/{id}', [DonacionController::class, 'update']);
        Route::delete('/{id}', [DonacionController::class, 'destroy']);
        Route::post('/{id}/restaurar', [DonacionController::class, 'restore']);
        Route::post('/{id}/confirmar', [DonacionController::class, 'confirmar']);
        Route::post('/{id}/rechazar', [DonacionController::class, 'rechazar']);
        Route::post('/{id}/cancelar', [DonacionController::class, 'cancelar']);
        Route::post('/{id}/procesar', [DonacionController::class, 'procesar']);
        Route::get('/estadisticas/generales', [DonacionController::class, 'estadisticas']);
        Route::get('/buscar/termino', [DonacionController::class, 'buscar']);
    });

    // Rutas de tareas
    Route::prefix('tareas')->group(function () {
        Route::get('/', [TareaController::class, 'index']);
        Route::get('/{id}', [TareaController::class, 'show']);
        Route::post('/', [TareaController::class, 'store']);
        Route::put('/{id}', [TareaController::class, 'update']);
        Route::delete('/{id}', [TareaController::class, 'destroy']);
        Route::post('/{id}/restaurar', [TareaController::class, 'restore']);
        Route::post('/{id}/iniciar', [TareaController::class, 'iniciar']);
        Route::post('/{id}/completar', [TareaController::class, 'completar']);
        Route::post('/{id}/cancelar', [TareaController::class, 'cancelar']);
        Route::post('/{id}/suspender', [TareaController::class, 'suspender']);
        Route::post('/{id}/reanudar', [TareaController::class, 'reanudar']);
        Route::post('/{id}/asignar', [TareaController::class, 'asignar']);
        Route::get('/estadisticas/generales', [TareaController::class, 'estadisticas']);
        Route::get('/buscar/termino', [TareaController::class, 'buscar']);
    });

    // Rutas de archivos
    Route::prefix('archivos')->group(function () {
        Route::get('/', [ArchivoController::class, 'index']);
        Route::get('/{id}', [ArchivoController::class, 'show']);
        Route::post('/', [ArchivoController::class, 'store']);
        Route::put('/{id}', [ArchivoController::class, 'update']);
        Route::delete('/{id}', [ArchivoController::class, 'destroy']);
        Route::post('/{id}/restaurar', [ArchivoController::class, 'restore']);
        Route::get('/{id}/descargar', [ArchivoController::class, 'descargar']);
        Route::get('/{id}/estadisticas', [ArchivoController::class, 'estadisticas']);
        Route::get('/buscar/termino', [ArchivoController::class, 'buscar']);
    });

    // Rutas de roles (solo administradores)
    Route::prefix('roles')->middleware('verificar.rol:adm')->group(function () {
        Route::get('/', [RolController::class, 'index']);
        Route::get('/{id}', [RolController::class, 'show']);
        Route::post('/', [RolController::class, 'store']);
        Route::put('/{id}', [RolController::class, 'update']);
        Route::delete('/{id}', [RolController::class, 'destroy']);
        Route::post('/{id}/activar', [RolController::class, 'activar']);
        Route::post('/{id}/desactivar', [RolController::class, 'desactivar']);
        Route::post('/{id}/agregar-permiso', [RolController::class, 'agregarPermiso']);
        Route::post('/{id}/quitar-permiso', [RolController::class, 'quitarPermiso']);
    });

    // Rutas de configuraciones (solo administradores)
    Route::prefix('configuraciones')->middleware('verificar.rol:adm')->group(function () {
        Route::get('/', [ConfiguracionController::class, 'index']);
        Route::get('/{id}', [ConfiguracionController::class, 'show']);
        Route::post('/', [ConfiguracionController::class, 'store']);
        Route::put('/{id}', [ConfiguracionController::class, 'update']);
        Route::delete('/{id}', [ConfiguracionController::class, 'destroy']);
        Route::get('/clave/{clave}', [ConfiguracionController::class, 'obtenerPorClave']);
        Route::put('/clave/{clave}', [ConfiguracionController::class, 'actualizarPorClave']);
        Route::get('/categoria/{categoria}', [ConfiguracionController::class, 'obtenerPorCategoria']);
        Route::post('/{id}/activar', [ConfiguracionController::class, 'activar']);
        Route::post('/{id}/desactivar', [ConfiguracionController::class, 'desactivar']);
    });

    // Rutas de logs (solo administradores)
    Route::prefix('logs')->middleware('verificar.rol:adm')->group(function () {
        Route::get('/', [LogController::class, 'index']);
        Route::get('/{id}', [LogController::class, 'show']);
        Route::get('/usuario/{usuarioId}', [LogController::class, 'porUsuario']);
        Route::get('/accion/{accion}', [LogController::class, 'porAccion']);
        Route::get('/tabla/{tabla}', [LogController::class, 'porTabla']);
        Route::get('/registro/{tabla}/{registroId}', [LogController::class, 'porRegistro']);
        Route::get('/fecha/{fechaInicio}/{fechaFin?}', [LogController::class, 'porFecha']);
        Route::get('/recientes/{dias?}', [LogController::class, 'recientes']);
    });

    // Rutas de dashboard y estadísticas generales
    Route::prefix('dashboard')->group(function () {
        Route::get('/resumen', function () {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_usuarios' => \App\Models\Usuario::count(),
                    'total_veedurias' => \App\Models\Veeduria::count(),
                    'total_donaciones' => \App\Models\Donacion::count(),
                    'total_tareas' => \App\Models\Tarea::count(),
                    'total_archivos' => \App\Models\Archivo::count(),
                    'veedurias_pendientes' => \App\Models\Veeduria::where('est', 'pen')->count(),
                    'donaciones_pendientes' => \App\Models\Donacion::where('est', 'pen')->count(),
                    'tareas_pendientes' => \App\Models\Tarea::where('est', 'pen')->count(),
                    'tareas_vencidas' => \App\Models\Tarea::vencidas()->count(),
                ],
                'message' => 'Resumen del dashboard obtenido exitosamente',
            ]);
        });

        Route::get('/estadisticas-generales', function () {
            return response()->json([
                'success' => true,
                'data' => [
                    'veedurias_por_estado' => \App\Models\Veeduria::selectRaw('est, COUNT(*) as total')
                        ->groupBy('est')
                        ->get(),
                    'veedurias_por_tipo' => \App\Models\Veeduria::selectRaw('tip, COUNT(*) as total')
                        ->groupBy('tip')
                        ->get(),
                    'donaciones_por_estado' => \App\Models\Donacion::selectRaw('est, COUNT(*) as total')
                        ->groupBy('est')
                        ->get(),
                    'tareas_por_estado' => \App\Models\Tarea::selectRaw('est, COUNT(*) as total')
                        ->groupBy('est')
                        ->get(),
                    'usuarios_por_rol' => \App\Models\Usuario::selectRaw('rol, COUNT(*) as total')
                        ->groupBy('rol')
                        ->get(),
                ],
                'message' => 'Estadísticas generales obtenidas exitosamente',
            ]);
        });
    });
});

// Ruta de fallback para rutas no encontradas
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Ruta no encontrada',
        'error' => 'La ruta solicitada no existe en la API',
    ], 404);
});