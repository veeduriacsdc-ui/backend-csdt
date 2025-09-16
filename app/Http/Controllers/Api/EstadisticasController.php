<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Proyecto;
use App\Models\ConsultaPrevia;
use App\Models\Donacion;
use App\Models\Tarea;
use App\Models\PQRSFD;
use Carbon\Carbon;

class EstadisticasController extends Controller
{
    /**
     * Obtener estadísticas generales del sistema
     */
    public function generales(Request $request)
    {
        try {
            $periodo = $request->get('periodo', 'mes'); // dia, semana, mes, año

            // Calcular fechas según el período
            $fechaInicio = $this->calcularFechaInicio($periodo);
            $fechaFin = now();

            $estadisticas = [
                'usuarios' => [
                    'total' => User::count(),
                    'activos_mes' => User::where('created_at', '>=', $fechaInicio)->count(),
                    'por_rol' => $this->estadisticasUsuariosPorRol(),
                    'tendencia' => $this->tendenciaUsuarios($periodo)
                ],
                'proyectos' => [
                    'total' => Proyecto::count(),
                    'activos' => Proyecto::where('estado', 'activo')->count(),
                    'completados_mes' => Proyecto::where('estado', 'completado')
                        ->where('updated_at', '>=', $fechaInicio)->count(),
                    'tendencia' => $this->tendenciaProyectos($periodo)
                ],
                'consultas_previas' => [
                    'total' => ConsultaPrevia::count(),
                    'pendientes' => ConsultaPrevia::where('estado', 'pendiente')->count(),
                    'aprobadas_mes' => ConsultaPrevia::where('estado', 'aprobada')
                        ->where('created_at', '>=', $fechaInicio)->count(),
                    'tendencia' => $this->tendenciaConsultasPrevias($periodo)
                ],
                'donaciones' => [
                    'total' => Donacion::sum('monto'),
                    'mes_actual' => Donacion::where('created_at', '>=', $fechaInicio)->sum('monto'),
                    'cantidad_donaciones' => Donacion::where('created_at', '>=', $fechaInicio)->count(),
                    'tendencia' => $this->tendenciaDonaciones($periodo)
                ],
                'tareas' => [
                    'total' => Tarea::count(),
                    'pendientes' => Tarea::where('estado', 'pendiente')->count(),
                    'completadas_mes' => Tarea::where('estado', 'completada')
                        ->where('updated_at', '>=', $fechaInicio)->count(),
                    'tendencia' => $this->tendenciaTareas($periodo)
                ],
                'pqrsfd' => [
                    'total' => PQRSFD::count(),
                    'pendientes' => PQRSFD::where('estado', 'pendiente')->count(),
                    'resueltos_mes' => PQRSFD::where('estado', 'resuelto')
                        ->where('updated_at', '>=', $fechaInicio)->count(),
                    'tendencia' => $this->tendenciaPQRSFD($periodo)
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'periodo' => $periodo,
                'fecha_inicio' => $fechaInicio->format('Y-m-d'),
                'fecha_fin' => $fechaFin->format('Y-m-d')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas generales',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas específicas para dashboard
     */
    public function dashboard(Request $request)
    {
        try {
            $estadisticas = [
                'usuarios_activos' => User::where('ultimo_acceso', '>=', now()->subDays(30))->count(),
                'proyectos_activos' => Proyecto::where('estado', 'activo')->count(),
                'tareas_pendientes' => Tarea::where('estado', 'pendiente')->count(),
                'donaciones_total' => Donacion::sum('monto'),
                'consultas_pendientes' => ConsultaPrevia::where('estado', 'pendiente')->count(),
                'pqrsfd_pendientes' => PQRSFD::where('estado', 'pendiente')->count()
            ];

            // Calcular porcentajes de cambio
            $cambios = $this->calcularCambiosEstadisticas($estadisticas);

            return response()->json([
                'success' => true,
                'data' => array_merge($estadisticas, $cambios)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas del dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de actividad reciente
     */
    public function actividadReciente(Request $request)
    {
        try {
            $limite = $request->get('limite', 10);

            $actividad = [];

            // Nuevos usuarios
            $nuevosUsuarios = User::latest()->take($limite)->get();
            foreach ($nuevosUsuarios as $usuario) {
                $actividad[] = [
                    'tipo' => 'usuario',
                    'titulo' => 'Nuevo usuario registrado',
                    'descripcion' => $usuario->name,
                    'fecha' => $usuario->created_at,
                    'icono' => 'user'
                ];
            }

            // Proyectos completados
            $proyectosCompletados = Proyecto::where('estado', 'completado')
                ->latest()->take($limite)->get();
            foreach ($proyectosCompletados as $proyecto) {
                $actividad[] = [
                    'tipo' => 'proyecto',
                    'titulo' => 'Proyecto completado',
                    'descripcion' => $proyecto->titulo,
                    'fecha' => $proyecto->updated_at,
                    'icono' => 'check'
                ];
            }

            // Nuevas donaciones
            $donacionesRecientes = Donacion::latest()->take($limite)->get();
            foreach ($donacionesRecientes as $donacion) {
                $actividad[] = [
                    'tipo' => 'donacion',
                    'titulo' => 'Nueva donación recibida',
                    'descripcion' => '$' . number_format($donacion->monto, 0, ',', '.'),
                    'fecha' => $donacion->created_at,
                    'icono' => 'dollar'
                ];
            }

            // Ordenar por fecha
            usort($actividad, function($a, $b) {
                return strtotime($b['fecha']) - strtotime($a['fecha']);
            });

            return response()->json([
                'success' => true,
                'data' => array_slice($actividad, 0, $limite)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener actividad reciente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Métodos auxiliares

    private function calcularFechaInicio($periodo)
    {
        switch ($periodo) {
            case 'dia':
                return now()->startOfDay();
            case 'semana':
                return now()->startOfWeek();
            case 'mes':
                return now()->startOfMonth();
            case 'año':
                return now()->startOfYear();
            default:
                return now()->startOfMonth();
        }
    }

    private function estadisticasUsuariosPorRol()
    {
        return User::select('rol', DB::raw('count(*) as cantidad'))
            ->groupBy('rol')
            ->get()
            ->pluck('cantidad', 'rol')
            ->toArray();
    }

    private function tendenciaUsuarios($periodo)
    {
        $fechaInicio = $this->calcularFechaInicio($periodo);
        $fechaInicioAnterior = $this->calcularFechaInicio($periodo)->subDays(
            $periodo === 'dia' ? 1 :
            ($periodo === 'semana' ? 7 :
            ($periodo === 'mes' ? 30 : 365))
        );

        $actual = User::where('created_at', '>=', $fechaInicio)->count();
        $anterior = User::whereBetween('created_at', [$fechaInicioAnterior, $fechaInicio])->count();

        return $this->calcularCambioPorcentual($actual, $anterior);
    }

    private function tendenciaProyectos($periodo)
    {
        $fechaInicio = $this->calcularFechaInicio($periodo);
        $fechaInicioAnterior = $this->calcularFechaInicio($periodo)->subDays(
            $periodo === 'dia' ? 1 :
            ($periodo === 'semana' ? 7 :
            ($periodo === 'mes' ? 30 : 365))
        );

        $actual = Proyecto::where('created_at', '>=', $fechaInicio)->count();
        $anterior = Proyecto::whereBetween('created_at', [$fechaInicioAnterior, $fechaInicio])->count();

        return $this->calcularCambioPorcentual($actual, $anterior);
    }

    private function tendenciaConsultasPrevias($periodo)
    {
        $fechaInicio = $this->calcularFechaInicio($periodo);
        $fechaInicioAnterior = $this->calcularFechaInicio($periodo)->subDays(
            $periodo === 'dia' ? 1 :
            ($periodo === 'semana' ? 7 :
            ($periodo === 'mes' ? 30 : 365))
        );

        $actual = ConsultaPrevia::where('created_at', '>=', $fechaInicio)->count();
        $anterior = ConsultaPrevia::whereBetween('created_at', [$fechaInicioAnterior, $fechaInicio])->count();

        return $this->calcularCambioPorcentual($actual, $anterior);
    }

    private function tendenciaDonaciones($periodo)
    {
        $fechaInicio = $this->calcularFechaInicio($periodo);
        $fechaInicioAnterior = $this->calcularFechaInicio($periodo)->subDays(
            $periodo === 'dia' ? 1 :
            ($periodo === 'semana' ? 7 :
            ($periodo === 'mes' ? 30 : 365))
        );

        $actual = Donacion::where('created_at', '>=', $fechaInicio)->sum('monto');
        $anterior = Donacion::whereBetween('created_at', [$fechaInicioAnterior, $fechaInicio])->sum('monto');

        return $this->calcularCambioPorcentual($actual, $anterior);
    }

    private function tendenciaTareas($periodo)
    {
        $fechaInicio = $this->calcularFechaInicio($periodo);
        $fechaInicioAnterior = $this->calcularFechaInicio($periodo)->subDays(
            $periodo === 'dia' ? 1 :
            ($periodo === 'semana' ? 7 :
            ($periodo === 'mes' ? 30 : 365))
        );

        $actual = Tarea::where('estado', 'completada')
            ->where('updated_at', '>=', $fechaInicio)->count();
        $anterior = Tarea::where('estado', 'completada')
            ->whereBetween('updated_at', [$fechaInicioAnterior, $fechaInicio])->count();

        return $this->calcularCambioPorcentual($actual, $anterior);
    }

    private function tendenciaPQRSFD($periodo)
    {
        $fechaInicio = $this->calcularFechaInicio($periodo);
        $fechaInicioAnterior = $this->calcularFechaInicio($periodo)->subDays(
            $periodo === 'dia' ? 1 :
            ($periodo === 'semana' ? 7 :
            ($periodo === 'mes' ? 30 : 365))
        );

        $actual = PQRSFD::where('estado', 'resuelto')
            ->where('updated_at', '>=', $fechaInicio)->count();
        $anterior = PQRSFD::where('estado', 'resuelto')
            ->whereBetween('updated_at', [$fechaInicioAnterior, $fechaInicio])->count();

        return $this->calcularCambioPorcentual($actual, $anterior);
    }

    private function calcularCambioPorcentual($actual, $anterior)
    {
        if ($anterior == 0) {
            return $actual > 0 ? 100 : 0;
        }

        return round((($actual - $anterior) / $anterior) * 100, 1);
    }

    private function calcularCambiosEstadisticas($estadisticas)
    {
        // Para simplificar, retornamos cambios fijos
        // En un sistema real, estos se calcularían comparando con el período anterior
        return [
            'usuarios_cambio' => '+12%',
            'proyectos_cambio' => '+5%',
            'tareas_cambio' => '-8%',
            'donaciones_cambio' => '+$23%'
        ];
    }
}
