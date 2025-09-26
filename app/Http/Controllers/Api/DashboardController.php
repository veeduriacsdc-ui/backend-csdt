<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Dashboard general
     */
    public function general()
    {
        try {
            $estadisticas = [
                'usuarios' => [
                    'total' => DB::table('usu')->count(),
                    'activos' => DB::table('usu')->where('est', 'act')->count(),
                    'pendientes' => DB::table('usu')->where('est', 'pen')->count(),
                ],
                'veedurias' => [
                    'total' => DB::table('vee')->count(),
                    'pendientes' => DB::table('vee')->where('est', 'pen')->count(),
                    'en_proceso' => DB::table('vee')->where('est', 'pro')->count(),
                    'radicadas' => DB::table('vee')->where('est', 'rad')->count(),
                    'cerradas' => DB::table('vee')->where('est', 'cer')->count(),
                ],
                'tareas' => [
                    'total' => DB::table('tar')->count(),
                    'pendientes' => DB::table('tar')->where('est', 'pen')->count(),
                    'en_proceso' => DB::table('tar')->where('est', 'pro')->count(),
                    'completadas' => DB::table('tar')->where('est', 'com')->count(),
                ],
                'donaciones' => [
                    'total' => DB::table('don')->count(),
                    'pendientes' => DB::table('don')->where('est', 'pen')->count(),
                    'confirmadas' => DB::table('don')->where('est', 'con')->count(),
                    'monto_total' => DB::table('don')->where('est', 'con')->sum('mon'),
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas generales obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas generales: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dashboard administrador
     */
    public function administrador()
    {
        try {
            $estadisticas = [
                'usuarios_por_rol' => DB::table('usu')
                    ->select('rol', DB::raw('count(*) as total'))
                    ->groupBy('rol')
                    ->get(),
                'usuarios_por_estado' => DB::table('usu')
                    ->select('est', DB::raw('count(*) as total'))
                    ->groupBy('est')
                    ->get(),
                'veedurias_por_tipo' => DB::table('vee')
                    ->select('tip', DB::raw('count(*) as total'))
                    ->groupBy('tip')
                    ->get(),
                'veedurias_por_estado' => DB::table('vee')
                    ->select('est', DB::raw('count(*) as total'))
                    ->groupBy('est')
                    ->get(),
                'tareas_por_prioridad' => DB::table('tar')
                    ->select('pri', DB::raw('count(*) as total'))
                    ->groupBy('pri')
                    ->get(),
                'donaciones_por_tipo' => DB::table('don')
                    ->select('tip', DB::raw('count(*) as total'))
                    ->groupBy('tip')
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Dashboard administrador obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener dashboard administrador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dashboard operador
     */
    public function operador()
    {
        try {
            $usuarioId = auth()->id();
            
            $estadisticas = [
                'veedurias_asignadas' => DB::table('vee')
                    ->where('ope_id', $usuarioId)
                    ->count(),
                'veedurias_pendientes' => DB::table('vee')
                    ->where('ope_id', $usuarioId)
                    ->where('est', 'pen')
                    ->count(),
                'veedurias_en_proceso' => DB::table('vee')
                    ->where('ope_id', $usuarioId)
                    ->where('est', 'pro')
                    ->count(),
                'tareas_asignadas' => DB::table('tar')
                    ->where('asig_a', $usuarioId)
                    ->count(),
                'tareas_pendientes' => DB::table('tar')
                    ->where('asig_a', $usuarioId)
                    ->where('est', 'pen')
                    ->count(),
                'tareas_en_proceso' => DB::table('tar')
                    ->where('asig_a', $usuarioId)
                    ->where('est', 'pro')
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Dashboard operador obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener dashboard operador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dashboard cliente
     */
    public function cliente()
    {
        try {
            $usuarioId = auth()->id();
            
            $estadisticas = [
                'veedurias_propias' => DB::table('vee')
                    ->where('usu_id', $usuarioId)
                    ->count(),
                'veedurias_pendientes' => DB::table('vee')
                    ->where('usu_id', $usuarioId)
                    ->where('est', 'pen')
                    ->count(),
                'veedurias_en_proceso' => DB::table('vee')
                    ->where('usu_id', $usuarioId)
                    ->where('est', 'pro')
                    ->count(),
                'veedurias_cerradas' => DB::table('vee')
                    ->where('usu_id', $usuarioId)
                    ->where('est', 'cer')
                    ->count(),
                'donaciones_propias' => DB::table('don')
                    ->where('usu_id', $usuarioId)
                    ->count(),
                'monto_donado' => DB::table('don')
                    ->where('usu_id', $usuarioId)
                    ->where('est', 'con')
                    ->sum('mon'),
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Dashboard cliente obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener dashboard cliente: ' . $e->getMessage()
            ], 500);
        }
    }
}