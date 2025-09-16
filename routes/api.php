<?php

use App\Http\Controllers\Api\AdministradorControlador;
use App\Http\Controllers\Api\ArchivoControlador;
use App\Http\Controllers\Api\ClienteControlador;
use App\Http\Controllers\Api\ConfiguracionControlador;
use App\Http\Controllers\Api\GestionUsuariosControlador;
use App\Http\Controllers\Api\NarracionConsejoIaControlador;
use App\Http\Controllers\Api\OperadorController;
use App\Http\Controllers\Api\TareaControlador;
use App\Http\Controllers\Api\EstadisticasController;
use App\Http\Controllers\Api\UsuarioSistemaController;
use App\Http\Controllers\Api\DonacionController;
use App\Http\Controllers\Api\RegistroControlador;
use App\Http\Controllers\VozController;
use App\Http\Controllers\VozInteligenteController;
use Illuminate\Support\Facades\Route;

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
        'version' => '1.0.0',
    ]);
});

// Rutas de autenticación unificada (eliminadas - usar /auth)

// Rutas de registro público
Route::prefix('registro')->group(function () {
    Route::post('/', [RegistroControlador::class, 'registrar']);
    Route::post('/verificar-email', [RegistroControlador::class, 'verificarEmail']);
});

// Rutas de gestión de usuarios (solo administradores)
Route::prefix('gestion-usuarios')->middleware(['auth:sanctum', 'verificar.rol:administrador'])->group(function () {
    Route::get('/', [GestionUsuariosControlador::class, 'obtenerListaUsuarios']);
    Route::get('/estadisticas', [GestionUsuariosControlador::class, 'obtenerEstadisticasUsuarios']);
    Route::get('/{id}', [GestionUsuariosControlador::class, 'obtenerUsuario']);
    Route::put('/{id}/rol', [GestionUsuariosControlador::class, 'cambiarRolUsuario']);
    Route::put('/{id}/estado', [GestionUsuariosControlador::class, 'cambiarEstadoUsuario']);
    Route::delete('/{id}', [GestionUsuariosControlador::class, 'eliminarUsuario']);
    Route::post('/{id}/restaurar', [GestionUsuariosControlador::class, 'restaurarUsuario']);
});

// Rutas de administración de registros (solo administradores)
Route::prefix('admin-registros')->middleware(['auth:sanctum', 'verificar.rol:administrador'])->group(function () {
    Route::get('/pendientes', [RegistroControlador::class, 'obtenerPendientes']);
    Route::post('/{id}/aprobar', [RegistroControlador::class, 'aprobar']);
    Route::post('/{id}/rechazar', [RegistroControlador::class, 'rechazar']);
});

// Rutas del sistema de usuarios unificado
Route::prefix('usuarios-sistema')->middleware(['auth:sanctum'])->group(function () {
    // Rutas públicas para usuarios autenticados
    Route::get('/estadisticas', [UsuarioSistemaController::class, 'estadisticas']);
    
    // Rutas de administradores
    Route::middleware('verificar.rol:administrador')->group(function () {
        Route::get('/', [UsuarioSistemaController::class, 'index']);
        Route::get('/{id}', [UsuarioSistemaController::class, 'show']);
        Route::post('/', [UsuarioSistemaController::class, 'store']);
        Route::put('/{id}', [UsuarioSistemaController::class, 'update']);
        Route::delete('/{id}', [UsuarioSistemaController::class, 'destroy']);
        Route::put('/{id}/cambiar-rol', [UsuarioSistemaController::class, 'cambiarRol']);
        Route::put('/{id}/cambiar-estado', [UsuarioSistemaController::class, 'cambiarEstado']);
    });
});

// Rutas del Dashboard con datos en tiempo real (eliminadas - usar /dashboard/resumen)

// Rutas de autenticación unificada
Route::prefix('auth')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/register-cliente', [\App\Http\Controllers\Api\AuthController::class, 'registerCliente']);
    Route::post('/register-operador', [\App\Http\Controllers\Api\AuthController::class, 'registerOperador'])->middleware('auth:sanctum');
    Route::post('/validar-campos', [\App\Http\Controllers\Api\AuthController::class, 'validarCampos']);
    Route::post('/verificar-email', [\App\Http\Controllers\Api\AuthController::class, 'verificarEmail']);
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [\App\Http\Controllers\Api\AuthController::class, 'me'])->middleware('auth:sanctum');
    Route::post('/cambiar-contrasena', [\App\Http\Controllers\Api\AuthController::class, 'cambiarContrasena'])->middleware('auth:sanctum');
    Route::post('/recuperar-contrasena', [\App\Http\Controllers\Api\AuthController::class, 'recuperarContrasena']);
    Route::post('/resetear-contrasena', [\App\Http\Controllers\Api\AuthController::class, 'resetearContrasena']);
});

// Rutas de autenticación legacy (mantener compatibilidad)
Route::prefix('auth-legacy')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'registerCliente']);
    Route::post('/verificar-email', [\App\Http\Controllers\Api\AuthController::class, 'verificarEmail']);
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [\App\Http\Controllers\Api\AuthController::class, 'me'])->middleware('auth:sanctum');
    Route::post('/cambiar-password', [\App\Http\Controllers\Api\AuthController::class, 'cambiarContrasena'])->middleware('auth:sanctum');
});

// Rutas públicas (sin autenticación)
Route::prefix('publico')->group(function () {

    // Rutas de CONSEJOIA (públicas)
    Route::prefix('consejoia')->group(function () {
        Route::post('/mejorar-texto', [NarracionConsejoIaControlador::class, 'MejorarConIa']);
        Route::post('/guardar-narracion', [NarracionConsejoIaControlador::class, 'Crear']);
        Route::post('/actualizar-narracion', [NarracionConsejoIaControlador::class, 'Actualizar']);
        Route::post('/generar-pdf', [NarracionConsejoIaControlador::class, 'GenerarPdf']);
        Route::get('/estadisticas', [NarracionConsejoIaControlador::class, 'ObtenerEstadisticas']);
    });


    // Rutas de clientes públicas
    Route::prefix('clientes')->group(function () {
        Route::post('/', [ClienteControlador::class, 'Crear']);
        Route::post('/verificar-correo', [ClienteControlador::class, 'VerificarCorreo']);
    });

    // Datos de referencia públicos
    Route::get('/tipos-veeduria', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'ambiental' => 'Ambiental',
                'social' => 'Social',
                'economica' => 'Económica',
                'politica' => 'Política',
                'administrativa' => 'Administrativa',
            ],
            'message' => 'Tipos de veeduría obtenidos exitosamente',
        ]);
    });

    Route::get('/estados-veeduria', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'pendiente' => 'Pendiente',
                'en_proceso' => 'En Proceso',
                'completada' => 'Completada',
                'cancelada' => 'Cancelada',
                'archivada' => 'Archivada',
            ],
            'message' => 'Estados de veeduría obtenidos exitosamente',
        ]);
    });

    Route::get('/prioridades-tarea', function () {
        return response()->json([
            'success' => true,
            'data' => [
                1 => 'Baja',
                2 => 'Media',
                3 => 'Alta',
                4 => 'Crítica',
            ],
            'message' => 'Prioridades de tarea obtenidas exitosamente',
        ]);
    });

    Route::get('/categorias-veeduria', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'infraestructura' => 'Infraestructura',
                'servicios_publicos' => 'Servicios Públicos',
                'salud' => 'Salud',
                'educacion' => 'Educación',
                'seguridad' => 'Seguridad',
                'transporte' => 'Transporte',
                'medio_ambiente' => 'Medio Ambiente',
                'desarrollo_social' => 'Desarrollo Social',
            ],
            'message' => 'Categorías de veeduría obtenidas exitosamente',
        ]);
    });
});

// Rutas protegidas por autenticación
Route::middleware('auth:sanctum')->group(function () {

    // Rutas de clientes autenticados
    Route::prefix('clientes')->group(function () {
        Route::get('/', [ClienteControlador::class, 'ObtenerLista']);
        Route::get('/{id}', [ClienteControlador::class, 'ObtenerPorId']);
        Route::put('/{id}', [ClienteControlador::class, 'Actualizar']);
        Route::delete('/{id}', [ClienteControlador::class, 'Eliminar']);
        Route::post('/{id}/restaurar', [ClienteControlador::class, 'Restaurar']);
        Route::put('/{id}/estado', [ClienteControlador::class, 'CambiarEstado']);
        Route::get('/{id}/estadisticas', [ClienteControlador::class, 'ObtenerEstadisticas']);
        Route::get('/{id}/veedurias', [ClienteControlador::class, 'ObtenerVeedurias']);
        Route::get('/{id}/donaciones', [ClienteControlador::class, 'ObtenerDonaciones']);
        Route::get('/{id}/archivos', [ClienteControlador::class, 'ObtenerArchivos']);
    });

    // Rutas de operadores autenticados
    Route::prefix('operadores')->group(function () {
        Route::get('/', [OperadorController::class, 'ObtenerLista']);
        Route::get('/{id}', [OperadorController::class, 'ObtenerPorId']);
        Route::put('/{id}', [OperadorController::class, 'Actualizar']);
        Route::delete('/{id}', [OperadorController::class, 'Eliminar']);
        Route::post('/{id}/restaurar', [OperadorController::class, 'Restaurar']);
        Route::put('/{id}/estado', [OperadorController::class, 'CambiarEstado']);
        Route::post('/{id}/verificar', [OperadorController::class, 'Verificar']);
        Route::post('/{id}/asignar-supervisor', [OperadorController::class, 'AsignarSupervisor']);
        Route::get('/{id}/estadisticas', [OperadorController::class, 'ObtenerEstadisticas']);
        Route::get('/{id}/veedurias-asignadas', [OperadorController::class, 'ObtenerVeeduriasAsignadas']);
        Route::get('/{id}/tareas-asignadas', [OperadorController::class, 'ObtenerTareasAsignadas']);
        Route::get('/{id}/subordinados', [OperadorController::class, 'ObtenerSubordinados']);
    });

    // Rutas de administradores (acceso especial)
    Route::prefix('administradores')->middleware('verificar.rol:administrador')->group(function () {
        Route::get('/', [AdministradorControlador::class, 'ObtenerLista']);
        Route::get('/{id}', [AdministradorControlador::class, 'ObtenerPorId']);
        Route::post('/', [AdministradorControlador::class, 'Crear']);
        Route::put('/{id}', [AdministradorControlador::class, 'Actualizar']);
        Route::get('/panel-control', [AdministradorControlador::class, 'ObtenerPanelControl']);
        Route::post('/{id}/permisos-especiales', [AdministradorControlador::class, 'AsignarPermisosEspeciales']);
        Route::get('/estadisticas', [AdministradorControlador::class, 'ObtenerEstadisticas']);
    });

    // Rutas de PQRSFD
    Route::prefix('pqrsfd')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\PQRSFDController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\PQRSFDController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\PQRSFDController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\PQRSFDController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\PQRSFDController::class, 'destroy']);
        Route::post('/{id}/asignar-operador', [\App\Http\Controllers\Api\PQRSFDController::class, 'asignarOperador']);
        Route::post('/{id}/radicar', [\App\Http\Controllers\Api\PQRSFDController::class, 'radicar']);
        Route::post('/{id}/cerrar', [\App\Http\Controllers\Api\PQRSFDController::class, 'cerrar']);
        Route::post('/{id}/cancelar', [\App\Http\Controllers\Api\PQRSFDController::class, 'cancelar']);
        Route::post('/{id}/agregar-comentario', [\App\Http\Controllers\Api\PQRSFDController::class, 'agregarComentario']);
        Route::get('/estadisticas/general', [\App\Http\Controllers\Api\PQRSFDController::class, 'estadisticas']);
    });

    // Rutas de actividades de caso (eliminadas)

    // Rutas de tareas
    Route::prefix('tareas')->group(function () {
        Route::get('/', [TareaControlador::class, 'ObtenerLista']);
        Route::get('/{id}', [TareaControlador::class, 'ObtenerPorId']);
        Route::post('/', [TareaControlador::class, 'Crear']);
        Route::put('/{id}', [TareaControlador::class, 'Actualizar']);
        Route::delete('/{id}', [TareaControlador::class, 'Eliminar']);
        Route::post('/{id}/restaurar', [TareaControlador::class, 'Restaurar']);
        Route::post('/{id}/asignar-operador', [TareaControlador::class, 'AsignarOperador']);
        Route::put('/{id}/estado', [TareaControlador::class, 'CambiarEstado']);
        Route::get('/{id}/estadisticas', [TareaControlador::class, 'ObtenerEstadisticas']);
        Route::get('/por-veeduria/{veeduriaId}', [TareaControlador::class, 'ObtenerPorVeeduria']);
        Route::get('/por-operador/{operadorId}', [TareaControlador::class, 'ObtenerPorOperador']);
    });

    // Rutas de narraciones CONSEJOIA (autenticadas)
    Route::prefix('narraciones-consejoia')->group(function () {
        Route::get('/', [NarracionConsejoIaControlador::class, 'ObtenerLista']);
        Route::get('/{id}', [NarracionConsejoIaControlador::class, 'ObtenerPorId']);
        Route::post('/', [NarracionConsejoIaControlador::class, 'Crear']);
        Route::put('/{id}', [NarracionConsejoIaControlador::class, 'Actualizar']);
        Route::delete('/{id}', [NarracionConsejoIaControlador::class, 'Eliminar']);
        Route::post('/{id}/mejorar-ia', [NarracionConsejoIaControlador::class, 'MejorarConIa']);
        Route::post('/{id}/generar-pdf', [NarracionConsejoIaControlador::class, 'GenerarPdf']);
        Route::get('/{id}/estadisticas', [NarracionConsejoIaControlador::class, 'ObtenerEstadisticas']);
        Route::get('/por-cliente/{clienteId}', [NarracionConsejoIaControlador::class, 'ObtenerPorCliente']);
        Route::get('/por-operador/{operadorId}', [NarracionConsejoIaControlador::class, 'ObtenerPorOperador']);
    });

    // Rutas de donaciones (autenticadas)
    Route::prefix('donaciones')->group(function () {
        Route::put('/{id}', [\App\Http\Controllers\Api\DonacionController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\DonacionController::class, 'destroy']);
        Route::post('/{id}/validar', [\App\Http\Controllers\Api\DonacionController::class, 'validar']);
        Route::post('/{id}/rechazar', [\App\Http\Controllers\Api\DonacionController::class, 'rechazar']);
        Route::get('/por-cliente/{clienteId}', [\App\Http\Controllers\Api\DonacionController::class, 'index']);
        Route::get('/por-operador/{operadorId}', [\App\Http\Controllers\Api\DonacionController::class, 'index']);
        Route::get('/pendientes-validacion', [\App\Http\Controllers\Api\DonacionController::class, 'index']);
        Route::get('/validadas', [\App\Http\Controllers\Api\DonacionController::class, 'index']);
    });

    // Rutas de archivos
    Route::prefix('archivos')->group(function () {
        Route::get('/', [ArchivoControlador::class, 'ObtenerLista']);
        Route::get('/{id}', [ArchivoControlador::class, 'ObtenerPorId']);
        Route::post('/', [ArchivoControlador::class, 'Subir']);
        Route::put('/{id}', [ArchivoControlador::class, 'Actualizar']);
        Route::delete('/{id}', [ArchivoControlador::class, 'Eliminar']);
        Route::get('/{id}/descargar', [ArchivoControlador::class, 'Descargar']);
        Route::get('/{id}/estadisticas', [ArchivoControlador::class, 'ObtenerEstadisticas']);
        Route::get('/por-veeduria/{veeduriaId}', [ArchivoControlador::class, 'ObtenerPorVeeduria']);
        Route::get('/por-cliente/{clienteId}', [ArchivoControlador::class, 'ObtenerPorCliente']);
        Route::get('/por-operador/{operadorId}', [ArchivoControlador::class, 'ObtenerPorOperador']);
    });

    // Rutas de notificaciones (eliminadas)

    // Rutas de logs de auditoría (eliminadas)

    // Rutas de reportes (eliminadas)

    // Rutas de configuración (solo administradores)
    Route::prefix('configuracion')->middleware('verificar.rol:administrador')->group(function () {
        Route::get('/', [ConfiguracionControlador::class, 'ObtenerLista']);
        Route::get('/{id}', [ConfiguracionControlador::class, 'ObtenerPorId']);
        Route::post('/', [ConfiguracionControlador::class, 'Crear']);
        Route::put('/{id}', [ConfiguracionControlador::class, 'Actualizar']);
        Route::get('/clave/{clave}', [ConfiguracionControlador::class, 'ObtenerPorClave']);
        Route::put('/clave/{clave}', [ConfiguracionControlador::class, 'ActualizarPorClave']);
        Route::get('/categoria/{categoria}', [ConfiguracionControlador::class, 'ObtenerPorCategoria']);
        Route::put('/{id}/estado', [ConfiguracionControlador::class, 'CambiarEstado']);
        Route::post('/{id}/restablecer-defecto', [ConfiguracionControlador::class, 'RestablecerPorDefecto']);
        Route::post('/limpiar-cache', [ConfiguracionControlador::class, 'LimpiarCache']);
        Route::get('/estadisticas', [ConfiguracionControlador::class, 'ObtenerEstadisticas']);
    });

    // Rutas de estadísticas
    Route::prefix('estadisticas')->group(function () {
        Route::get('/generales', [EstadisticasController::class, 'generales']);
        Route::get('/dashboard', [EstadisticasController::class, 'dashboard']);
        Route::get('/actividad-reciente', [EstadisticasController::class, 'actividadReciente']);
    });

    // Rutas de dashboard y estadísticas generales
    Route::prefix('dashboard')->group(function () {
        Route::get('/resumen', function () {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_clientes' => \App\Models\Cliente::count(),
                    'total_operadores' => \App\Models\Operador::count(),
                    'total_pqrsfd' => \App\Models\PQRSFD::count(),
                    'total_donaciones' => \App\Models\Donacion::count(),
                    'total_usuarios' => \App\Models\UsuarioSistema::count(),
                    'registros_pendientes' => \App\Models\RegistroPendiente::where('estado', 'pendiente')->count(),
                    'pqrsfd_pendientes' => \App\Models\PQRSFD::where('estado', 'pendiente')->count(),
                    'donaciones_pendientes' => \App\Models\Donacion::where('estado', 'pendiente')->count(),
                ],
                'message' => 'Resumen del dashboard obtenido exitosamente',
            ]);
        });

        Route::get('/estadisticas-generales', function () {
            return response()->json([
                'success' => true,
                'data' => [
                    'pqrsfd_por_estado' => \App\Models\PQRSFD::selectRaw('estado, COUNT(*) as total')
                        ->groupBy('estado')
                        ->get(),
                    'pqrsfd_por_tipo' => \App\Models\PQRSFD::selectRaw('tipo_pqrsfd, COUNT(*) as total')
                        ->groupBy('tipo_pqrsfd')
                        ->get(),
                    'donaciones_por_estado' => \App\Models\Donacion::selectRaw('estado, COUNT(*) as total')
                        ->groupBy('estado')
                        ->get(),
                    'usuarios_por_rol' => \App\Models\UsuarioSistema::selectRaw('rol, COUNT(*) as total')
                        ->groupBy('rol')
                        ->get(),
                ],
                'message' => 'Estadísticas generales obtenidas exitosamente',
            ]);
        });
    });
});

// Rutas de servicios de voz avanzados
Route::prefix('voz')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/texto-a-voz', [VozController::class, 'textoAVoz']);
    Route::post('/voz-a-texto', [VozController::class, 'vozATexto']);
    Route::post('/conversacion', [VozController::class, 'conversacionVoz']);
    Route::get('/configuracion', [VozController::class, 'configuracionVoz']);
    Route::post('/probar', [VozController::class, 'probarVoz']);
    Route::get('/estadisticas', [VozController::class, 'estadisticasVoz']);
});

// Rutas de voz inteligente avanzada con integración de IAs
Route::prefix('voz-inteligente')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/procesar-comando', [VozInteligenteController::class, 'procesarComando']);
    Route::get('/comandos-disponibles', [VozInteligenteController::class, 'comandosDisponibles']);
    Route::get('/historial', [VozInteligenteController::class, 'historialInteracciones']);
    Route::post('/preferencias', [VozInteligenteController::class, 'configurarPreferencias']);
    Route::get('/estadisticas', [VozInteligenteController::class, 'estadisticasUso']);
    Route::post('/probar-sistema', [VozInteligenteController::class, 'probarSistema']);
});

// Rutas públicas de voz (sin autenticación requerida para funcionalidades básicas)
Route::prefix('voz-publico')->group(function () {
    Route::get('/configuracion', [VozController::class, 'configuracionVoz']);
    Route::post('/texto-a-voz', [VozController::class, 'textoAVoz'])
        ->middleware('throttle:voz_publico'); // Limitar uso público
});

// Rutas eliminadas (controladores no existen)


// Ruta de fallback para rutas no encontradas
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Ruta no encontrada',
        'error' => 'La ruta solicitada no existe en la API',
    ], 404);
});
