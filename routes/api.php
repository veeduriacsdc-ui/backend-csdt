<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClienteControlador;
use App\Http\Controllers\Api\OperadorControlador;
use App\Http\Controllers\Api\AdministradorControlador;
use App\Http\Controllers\Api\OperacionControlador;
use App\Http\Controllers\Api\TareaControlador;
use App\Http\Controllers\Api\NarracionConsejoIaControlador;
use App\Http\Controllers\Api\DonacionControlador;
use App\Http\Controllers\Api\ArchivoControlador;
use App\Http\Controllers\Api\NotificacionControlador;
use App\Http\Controllers\Api\LogAuditoriaControlador;
use App\Http\Controllers\Api\ReporteControlador;
use App\Http\Controllers\Api\ConfiguracionControlador;
use App\Http\Controllers\Api\SesionControlador;
use App\Http\Controllers\Api\GestionUsuariosControlador;

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
        'version' => '1.0.0'
    ]);
});

// Rutas de autenticación unificada
Route::prefix('sesion')->group(function () {
    Route::post('/iniciar', [SesionControlador::class, 'iniciarSesion']);
    Route::post('/registrar', [SesionControlador::class, 'registrarUsuario']);
    Route::post('/recuperar-contrasena', [SesionControlador::class, 'recuperarContrasena']);
    Route::post('/cambiar-contrasena', [SesionControlador::class, 'cambiarContrasena']);
    Route::post('/renovar', [SesionControlador::class, 'renovarSesion']);
    Route::get('/info', [SesionControlador::class, 'obtenerSesion']);
    Route::post('/cerrar', [SesionControlador::class, 'cerrarSesion']);
    Route::get('/estadisticas', [SesionControlador::class, 'obtenerEstadisticas']);
    Route::post('/crear-admin-inicial', [SesionControlador::class, 'crearAdministradorInicial']);
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

// Rutas de autenticación legacy (mantener compatibilidad)
Route::prefix('auth')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
    Route::post('/verificar-email', [\App\Http\Controllers\Api\AuthController::class, 'verificarEmail']);
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/refresh', [\App\Http\Controllers\Api\AuthController::class, 'refresh'])->middleware('auth:sanctum');
    Route::get('/me', [\App\Http\Controllers\Api\AuthController::class, 'me'])->middleware('auth:sanctum');
    Route::post('/cambiar-password', [\App\Http\Controllers\Api\AuthController::class, 'cambiarPassword'])->middleware('auth:sanctum');
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

    // Rutas de donaciones públicas
    Route::prefix('donaciones')->group(function () {
        Route::get('/', [DonacionControlador::class, 'ObtenerLista']);
        Route::get('/{id}', [DonacionControlador::class, 'ObtenerPorId']);
        Route::post('/', [DonacionControlador::class, 'Crear']);
        Route::get('/estadisticas', [DonacionControlador::class, 'ObtenerEstadisticas']);
    });

    // Rutas de clientes públicas
    Route::prefix('clientes')->group(function () {
        Route::post('/', [ClienteControlador::class, 'Crear']);
        Route::post('/verificar-correo', [ClienteControlador::class, 'VerificarCorreo']);
    });

    // Rutas de operadores públicas
    Route::prefix('operadores')->group(function () {
        Route::post('/', [OperadorControlador::class, 'Crear']);
        Route::post('/verificar-perfil', [OperadorControlador::class, 'VerificarPerfil']);
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
                'administrativa' => 'Administrativa'
            ],
            'message' => 'Tipos de veeduría obtenidos exitosamente'
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
                'archivada' => 'Archivada'
            ],
            'message' => 'Estados de veeduría obtenidos exitosamente'
    ]);
});

    Route::get('/prioridades-tarea', function () {
    return response()->json([
        'success' => true,
        'data' => [
            1 => 'Baja',
            2 => 'Media',
            3 => 'Alta',
            4 => 'Crítica'
        ],
            'message' => 'Prioridades de tarea obtenidas exitosamente'
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
                'desarrollo_social' => 'Desarrollo Social'
            ],
            'message' => 'Categorías de veeduría obtenidas exitosamente'
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
        Route::get('/', [OperadorControlador::class, 'ObtenerLista']);
        Route::get('/{id}', [OperadorControlador::class, 'ObtenerPorId']);
        Route::put('/{id}', [OperadorControlador::class, 'Actualizar']);
        Route::delete('/{id}', [OperadorControlador::class, 'Eliminar']);
        Route::post('/{id}/restaurar', [OperadorControlador::class, 'Restaurar']);
        Route::put('/{id}/estado', [OperadorControlador::class, 'CambiarEstado']);
        Route::post('/{id}/verificar', [OperadorControlador::class, 'Verificar']);
        Route::post('/{id}/asignar-supervisor', [OperadorControlador::class, 'AsignarSupervisor']);
        Route::get('/{id}/estadisticas', [OperadorControlador::class, 'ObtenerEstadisticas']);
        Route::get('/{id}/veedurias-asignadas', [OperadorControlador::class, 'ObtenerVeeduriasAsignadas']);
        Route::get('/{id}/tareas-asignadas', [OperadorControlador::class, 'ObtenerTareasAsignadas']);
        Route::get('/{id}/subordinados', [OperadorControlador::class, 'ObtenerSubordinados']);
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

    // Rutas de veedurías
    Route::prefix('veedurias')->group(function () {
        Route::get('/', [VeeduriaControlador::class, 'ObtenerLista']);
        Route::get('/{id}', [VeeduriaControlador::class, 'ObtenerPorId']);
        Route::post('/', [VeeduriaControlador::class, 'Crear']);
        Route::put('/{id}', [VeeduriaControlador::class, 'Actualizar']);
        Route::delete('/{id}', [VeeduriaControlador::class, 'Eliminar']);
        Route::post('/{id}/restaurar', [VeeduriaControlador::class, 'Restaurar']);
        Route::post('/{id}/asignar-operador', [VeeduriaControlador::class, 'AsignarOperador']);
        Route::put('/{id}/estado', [VeeduriaControlador::class, 'CambiarEstado']);
        Route::post('/{id}/agregar-documento', [VeeduriaControlador::class, 'AgregarDocumento']);
        Route::post('/{id}/agregar-etiqueta', [VeeduriaControlador::class, 'AgregarEtiqueta']);
        Route::get('/{id}/estadisticas', [VeeduriaControlador::class, 'ObtenerEstadisticas']);
        Route::get('/{id}/tareas', [VeeduriaControlador::class, 'ObtenerTareas']);
        Route::get('/{id}/archivos', [VeeduriaControlador::class, 'ObtenerArchivos']);
    });

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
        Route::put('/{id}', [DonacionControlador::class, 'Actualizar']);
        Route::delete('/{id}', [DonacionControlador::class, 'Eliminar']);
        Route::put('/{id}/estado', [DonacionControlador::class, 'CambiarEstado']);
        Route::post('/{id}/validar', [DonacionControlador::class, 'Validar']);
        Route::post('/{id}/rechazar', [DonacionControlador::class, 'Rechazar']);
        Route::get('/{id}/estadisticas', [DonacionControlador::class, 'ObtenerEstadisticas']);
        Route::get('/por-cliente/{clienteId}', [DonacionControlador::class, 'ObtenerPorCliente']);
        Route::get('/por-operador/{operadorId}', [DonacionControlador::class, 'ObtenerPorOperador']);
        Route::get('/pendientes-validacion', [DonacionControlador::class, 'ObtenerPendientesValidacion']);
        Route::get('/validadas', [DonacionControlador::class, 'ObtenerValidadas']);
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

    // Rutas de notificaciones
    Route::prefix('notificaciones')->group(function () {
        Route::get('/', [NotificacionControlador::class, 'ObtenerLista']);
        Route::get('/{id}', [NotificacionControlador::class, 'ObtenerPorId']);
        Route::post('/', [NotificacionControlador::class, 'Crear']);
        Route::put('/{id}', [NotificacionControlador::class, 'Actualizar']);
        Route::delete('/{id}', [NotificacionControlador::class, 'Eliminar']);
        Route::post('/{id}/marcar-leida', [NotificacionControlador::class, 'MarcarComoLeida']);
        Route::post('/{id}/reintentar-envio', [NotificacionControlador::class, 'ReintentarEnvio']);
        Route::get('/mis-notificaciones', [NotificacionControlador::class, 'ObtenerMisNotificaciones']);
        Route::get('/{id}/estadisticas', [NotificacionControlador::class, 'ObtenerEstadisticas']);
        Route::get('/por-usuario/{usuarioId}', [NotificacionControlador::class, 'ObtenerPorUsuario']);
        Route::get('/no-leidas', [NotificacionControlador::class, 'ObtenerNoLeidas']);
    });

    // Rutas de logs de auditoría (solo administradores)
    Route::prefix('logs-auditoria')->middleware('verificar.rol:administrador')->group(function () {
        Route::get('/', [LogAuditoriaControlador::class, 'ObtenerLista']);
        Route::get('/{id}', [LogAuditoriaControlador::class, 'ObtenerPorId']);
        Route::post('/', [LogAuditoriaControlador::class, 'Crear']);
        Route::get('/mis-logs', [LogAuditoriaControlador::class, 'ObtenerMisLogs']);
        Route::post('/exportar', [LogAuditoriaControlador::class, 'Exportar']);
        Route::post('/limpiar-logs-antiguos', [LogAuditoriaControlador::class, 'LimpiarLogsAntiguos']);
        Route::get('/estadisticas', [LogAuditoriaControlador::class, 'ObtenerEstadisticas']);
        Route::get('/por-usuario/{usuarioId}', [LogAuditoriaControlador::class, 'ObtenerPorUsuario']);
        Route::get('/por-accion/{accion}', [LogAuditoriaControlador::class, 'ObtenerPorAccion']);
    });

    // Rutas de reportes (solo administradores y operadores)
    Route::prefix('reportes')->middleware('verificar.rol:administrador,operador')->group(function () {
        Route::get('/', [ReporteControlador::class, 'ObtenerLista']);
        Route::get('/{id}', [ReporteControlador::class, 'ObtenerPorId']);
        Route::post('/generar-veedurias', [ReporteControlador::class, 'GenerarReporteVeedurias']);
        Route::post('/generar-donaciones', [ReporteControlador::class, 'GenerarReporteDonaciones']);
        Route::post('/generar-actividad-sistema', [ReporteControlador::class, 'GenerarReporteActividadSistema']);
        Route::get('/{id}/descargar', [ReporteControlador::class, 'DescargarReporte']);
        Route::get('/{id}/estadisticas', [ReporteControlador::class, 'ObtenerEstadisticas']);
        Route::get('/por-tipo/{tipo}', [ReporteControlador::class, 'ObtenerPorTipo']);
        Route::get('/por-fecha/{fecha}', [ReporteControlador::class, 'ObtenerPorFecha']);
    });

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

    // Rutas de dashboard y estadísticas generales
    Route::prefix('dashboard')->group(function () {
        Route::get('/resumen', function () {
    return response()->json([
        'success' => true,
                'data' => [
                    'total_clientes' => \App\Models\Cliente::count(),
                    'total_operadores' => \App\Models\Operador::count(),
                    'total_veedurias' => \App\Models\Veeduria::count(),
                    'total_tareas' => \App\Models\Tarea::count(),
                    'total_donaciones' => \App\Models\Donacion::count(),
                    'total_narraciones' => \App\Models\NarracionConsejoIa::count(),
                    'veedurias_pendientes' => \App\Models\Veeduria::where('estado', 'pendiente')->count(),
                    'tareas_pendientes' => \App\Models\Tarea::where('estado', 'pendiente')->count(),
                    'donaciones_pendientes' => \App\Models\Donacion::where('estado', 'pendiente')->count(),
                ],
                'message' => 'Resumen del dashboard obtenido exitosamente'
            ]);
        });

        Route::get('/estadisticas-generales', function () {
            return response()->json([
                'success' => true,
                'data' => [
                    'veedurias_por_estado' => \App\Models\Veeduria::selectRaw('estado, COUNT(*) as total')
                        ->groupBy('estado')
                        ->get(),
                    'veedurias_por_categoria' => \App\Models\Veeduria::selectRaw('categoria, COUNT(*) as total')
                        ->groupBy('categoria')
                        ->get(),
                    'tareas_por_prioridad' => \App\Models\Tarea::selectRaw('prioridad, COUNT(*) as total')
                        ->groupBy('prioridad')
                        ->get(),
                    'donaciones_por_estado' => \App\Models\Donacion::selectRaw('estado, COUNT(*) as total')
                        ->groupBy('estado')
                        ->get(),
                ],
                'message' => 'Estadísticas generales obtenidas exitosamente'
            ]);
        });
    });
});

// Ruta de fallback para rutas no encontradas
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Ruta no encontrada',
        'error' => 'La ruta solicitada no existe en la API'
    ], 404);
});
