<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LogController extends Controller
{
    /**
     * Obtener lista de logs del sistema
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);
            $search = $request->get('search');
            $level = $request->get('level');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            // Simular logs del sistema (en producción vendrían de la tabla de logs)
            $logs = $this->generarLogsSimulados($search, $level, $dateFrom, $dateTo);
            
            // Paginación manual
            $total = count($logs);
            $offset = ($page - 1) * $perPage;
            $paginatedLogs = array_slice($logs, $offset, $perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'logs' => $paginatedLogs,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'last_page' => ceil($total / $perPage),
                        'from' => $offset + 1,
                        'to' => min($offset + $perPage, $total)
                    ]
                ],
                'message' => 'Logs obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener logs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener logs recientes
     */
    public function recientes(Request $request, $dias = 7): JsonResponse
    {
        try {
            $dias = min($dias, 30); // Máximo 30 días
            $fechaInicio = now()->subDays($dias);
            
            // Simular logs recientes
            $logs = $this->generarLogsSimulados(null, null, $fechaInicio->format('Y-m-d'), now()->format('Y-m-d'));
            
            // Filtrar solo los últimos N días
            $logsRecientes = array_slice($logs, 0, 20); // Máximo 20 logs recientes

            return response()->json([
                'success' => true,
                'data' => [
                    'logs' => $logsRecientes,
                    'periodo' => "Últimos {$dias} días",
                    'total' => count($logsRecientes)
                ],
                'message' => 'Logs recientes obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener logs recientes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener logs recientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de logs
     */
    public function estadisticas(): JsonResponse
    {
        try {
            $estadisticas = [
                'total_logs' => 150,
                'por_nivel' => [
                    'info' => 85,
                    'warning' => 35,
                    'error' => 20,
                    'debug' => 10
                ],
                'por_dia' => [
                    'hoy' => 25,
                    'ayer' => 18,
                    'semana' => 120,
                    'mes' => 450
                ],
                'errores_recientes' => 5,
                'warnings_recientes' => 12,
                'logs_por_hora' => [
                    '00:00' => 2, '01:00' => 1, '02:00' => 0, '03:00' => 1,
                    '04:00' => 0, '05:00' => 1, '06:00' => 3, '07:00' => 5,
                    '08:00' => 8, '09:00' => 12, '10:00' => 15, '11:00' => 18,
                    '12:00' => 20, '13:00' => 22, '14:00' => 25, '15:00' => 28,
                    '16:00' => 30, '17:00' => 25, '18:00' => 20, '19:00' => 15,
                    '20:00' => 10, '21:00' => 8, '22:00' => 5, '23:00' => 3
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas de logs obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas de logs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas de logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar logs simulados para pruebas
     */
    private function generarLogsSimulados($search = null, $level = null, $dateFrom = null, $dateTo = null)
    {
        $logs = [
            [
                'id' => 1,
                'level' => 'info',
                'message' => 'Usuario admin@csdt.gov.co inició sesión correctamente',
                'context' => ['user_id' => 1, 'ip' => '192.168.1.100'],
                'created_at' => now()->subMinutes(5)->toISOString(),
                'updated_at' => now()->subMinutes(5)->toISOString()
            ],
            [
                'id' => 2,
                'level' => 'info',
                'message' => 'Nueva veeduría creada: Problema de infraestructura vial',
                'context' => ['veeduria_id' => 1, 'user_id' => 2],
                'created_at' => now()->subMinutes(15)->toISOString(),
                'updated_at' => now()->subMinutes(15)->toISOString()
            ],
            [
                'id' => 3,
                'level' => 'warning',
                'message' => 'Intento de acceso no autorizado detectado',
                'context' => ['ip' => '192.168.1.200', 'user_agent' => 'Mozilla/5.0'],
                'created_at' => now()->subMinutes(30)->toISOString(),
                'updated_at' => now()->subMinutes(30)->toISOString()
            ],
            [
                'id' => 4,
                'level' => 'error',
                'message' => 'Error al procesar donación: Datos incompletos',
                'context' => ['donacion_id' => 1, 'error_code' => 'VALIDATION_ERROR'],
                'created_at' => now()->subHour()->toISOString(),
                'updated_at' => now()->subHour()->toISOString()
            ],
            [
                'id' => 5,
                'level' => 'info',
                'message' => 'Sistema de IA generó narración exitosamente',
                'context' => ['narracion_id' => 1, 'tipo' => 'acta'],
                'created_at' => now()->subHours(2)->toISOString(),
                'updated_at' => now()->subHours(2)->toISOString()
            ],
            [
                'id' => 6,
                'level' => 'info',
                'message' => 'Tarea completada: Revisión de documentación',
                'context' => ['tarea_id' => 1, 'usuario_id' => 3],
                'created_at' => now()->subHours(3)->toISOString(),
                'updated_at' => now()->subHours(3)->toISOString()
            ],
            [
                'id' => 7,
                'level' => 'warning',
                'message' => 'Alto uso de memoria detectado en servidor',
                'context' => ['memory_usage' => '85%', 'server' => 'web-01'],
                'created_at' => now()->subHours(4)->toISOString(),
                'updated_at' => now()->subHours(4)->toISOString()
            ],
            [
                'id' => 8,
                'level' => 'info',
                'message' => 'Backup de base de datos completado exitosamente',
                'context' => ['backup_size' => '2.5GB', 'duration' => '15min'],
                'created_at' => now()->subHours(6)->toISOString(),
                'updated_at' => now()->subHours(6)->toISOString()
            ],
            [
                'id' => 9,
                'level' => 'error',
                'message' => 'Error de conexión a base de datos',
                'context' => ['error_code' => 'DB_CONNECTION_FAILED', 'retry_count' => 3],
                'created_at' => now()->subHours(8)->toISOString(),
                'updated_at' => now()->subHours(8)->toISOString()
            ],
            [
                'id' => 10,
                'level' => 'info',
                'message' => 'Usuario Carlos Rodríguez registrado como cliente',
                'context' => ['user_id' => 2, 'rol' => 'cli'],
                'created_at' => now()->subHours(12)->toISOString(),
                'updated_at' => now()->subHours(12)->toISOString()
            ]
        ];

        // Aplicar filtros
        if ($search) {
            $logs = array_filter($logs, function($log) use ($search) {
                return stripos($log['message'], $search) !== false;
            });
        }

        if ($level) {
            $logs = array_filter($logs, function($log) use ($level) {
                return $log['level'] === $level;
            });
        }

        // Ordenar por fecha descendente
        usort($logs, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return array_values($logs);
    }
}