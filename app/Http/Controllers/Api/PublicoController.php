<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PublicoController extends Controller
{
    /**
     * Obtener tipos de veeduría
     */
    public function tiposVeeduria()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'pet' => 'Petición',
                'que' => 'Queja',
                'rec' => 'Reclamo',
                'sug' => 'Sugerencia',
                'fel' => 'Felicitación',
                'den' => 'Denuncia'
            ],
            'message' => 'Tipos de veeduría obtenidos exitosamente'
        ]);
    }

    /**
     * Obtener estados de veeduría
     */
    public function estadosVeeduria()
    {
        return response()->json([
            'success' => true,
            'data' => [
                ['codigo' => 'pen', 'nombre' => 'Pendiente'],
                ['codigo' => 'pro', 'nombre' => 'En Proceso'],
                ['codigo' => 'rad', 'nombre' => 'Radicada'],
                ['codigo' => 'cer', 'nombre' => 'Cerrada'],
                ['codigo' => 'can', 'nombre' => 'Cancelada']
            ],
            'message' => 'Estados de veeduría obtenidos exitosamente'
        ]);
    }

    /**
     * Obtener categorías de veeduría
     */
    public function categoriasVeeduria()
    {
        return response()->json([
            'success' => true,
            'data' => [
                ['codigo' => 'inf', 'nombre' => 'Infraestructura'],
                ['codigo' => 'ser', 'nombre' => 'Servicios'],
                ['codigo' => 'seg', 'nombre' => 'Seguridad'],
                ['codigo' => 'edu', 'nombre' => 'Educación'],
                ['codigo' => 'sal', 'nombre' => 'Salud'],
                ['codigo' => 'tra', 'nombre' => 'Transporte'],
                ['codigo' => 'amb', 'nombre' => 'Ambiente'],
                ['codigo' => 'otr', 'nombre' => 'Otros']
            ],
            'message' => 'Categorías de veeduría obtenidas exitosamente'
        ]);
    }

    /**
     * Obtener tipos de documento
     */
    public function tiposDocumento()
    {
        return response()->json([
            'success' => true,
            'data' => [
                ['codigo' => 'cc', 'nombre' => 'Cédula de Ciudadanía'],
                ['codigo' => 'ce', 'nombre' => 'Cédula de Extranjería'],
                ['codigo' => 'ti', 'nombre' => 'Tarjeta de Identidad'],
                ['codigo' => 'pp', 'nombre' => 'Pasaporte'],
                ['codigo' => 'nit', 'nombre' => 'NIT']
            ],
            'message' => 'Tipos de documento obtenidos exitosamente'
        ]);
    }

    /**
     * Obtener géneros
     */
    public function generos()
    {
        return response()->json([
            'success' => true,
            'data' => [
                ['codigo' => 'm', 'nombre' => 'Masculino'],
                ['codigo' => 'f', 'nombre' => 'Femenino'],
                ['codigo' => 'o', 'nombre' => 'Otro'],
                ['codigo' => 'n', 'nombre' => 'No especifica']
            ],
            'message' => 'Géneros obtenidos exitosamente'
        ]);
    }

    /**
     * Obtener prioridades de tarea
     */
    public function prioridadesTarea()
    {
        return response()->json([
            'success' => true,
            'data' => [
                ['codigo' => 'baj', 'nombre' => 'Baja'],
                ['codigo' => 'med', 'nombre' => 'Media'],
                ['codigo' => 'alt', 'nombre' => 'Alta'],
                ['codigo' => 'urg', 'nombre' => 'Urgente']
            ],
            'message' => 'Prioridades de tarea obtenidas exitosamente'
        ]);
    }

    /**
     * Obtener estados de tarea
     */
    public function estadosTarea()
    {
        return response()->json([
            'success' => true,
            'data' => [
                ['codigo' => 'pen', 'nombre' => 'Pendiente'],
                ['codigo' => 'pro', 'nombre' => 'En Proceso'],
                ['codigo' => 'com', 'nombre' => 'Completada'],
                ['codigo' => 'can', 'nombre' => 'Cancelada'],
                ['codigo' => 'sus', 'nombre' => 'Suspendida']
            ],
            'message' => 'Estados de tarea obtenidos exitosamente'
        ]);
    }

    /**
     * Obtener tipos de donación
     */
    public function tiposDonacion()
    {
        return response()->json([
            'success' => true,
            'data' => [
                ['codigo' => 'efec', 'nombre' => 'Efectivo'],
                ['codigo' => 'tran', 'nombre' => 'Transferencia'],
                ['codigo' => 'tar', 'nombre' => 'Tarjeta'],
                ['codigo' => 'otr', 'nombre' => 'Otros']
            ],
            'message' => 'Tipos de donación obtenidos exitosamente'
        ]);
    }

    /**
     * Obtener estados de donación
     */
    public function estadosDonacion()
    {
        return response()->json([
            'success' => true,
            'data' => [
                ['codigo' => 'pen', 'nombre' => 'Pendiente'],
                ['codigo' => 'pro', 'nombre' => 'Procesando'],
                ['codigo' => 'con', 'nombre' => 'Confirmada'],
                ['codigo' => 'rec', 'nombre' => 'Rechazada'],
                ['codigo' => 'can', 'nombre' => 'Cancelada']
            ],
            'message' => 'Estados de donación obtenidos exitosamente'
        ]);
    }
}
