<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donacion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class DonacionController extends Controller
{
    /**
     * Obtener lista de donaciones
     */
    public function obtenerLista(Request $request): JsonResponse
    {
        try {
            $query = Donacion::with(['validador', 'certificador']);

            // Filtros
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->filled('tipo_donacion')) {
                $query->where('tipo_donacion', $request->tipo_donacion);
            }

            if ($request->filled('fecha_desde')) {
                $query->whereDate('created_at', '>=', $request->fecha_desde);
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('created_at', '<=', $request->fecha_hasta);
            }

            // Ordenamiento
            $query->orderBy('created_at', 'desc');

            $donaciones = $query->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $donaciones,
                'message' => 'Donaciones obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener donaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nueva donación
     */
    public function crear(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre_donante' => 'required|string|max:255',
                'email_donante' => 'required|email|max:255',
                'telefono_donante' => 'nullable|string|max:20',
                'monto' => 'required|numeric|min:10000',
                'tipo_donacion' => 'required|in:unica,mensual,trimestral,anual',
                'metodo_pago' => 'required|in:tarjeta,transferencia,nequi,daviplata,movii,bitcoin,ethereum,usdt',
                'mensaje' => 'nullable|string',
                'referencia_donacion' => 'nullable|string|max:255',
                'archivo_comprobante' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $donacion = new Donacion();
            $donacion->numero_referencia = Donacion::generarNumeroReferencia();
            $donacion->nombre_donante = $request->nombre_donante;
            $donacion->email_donante = $request->email_donante;
            $donacion->telefono_donante = $request->telefono_donante;
            $donacion->monto = $request->monto;
            $donacion->tipo_donacion = $request->tipo_donacion;
            $donacion->metodo_pago = $request->metodo_pago;
            $donacion->mensaje = $request->mensaje;
            $donacion->referencia_donacion = $request->referencia_donacion;
            $donacion->estado = 'pendiente';

            // Manejar archivo de comprobante
            if ($request->hasFile('archivo_comprobante')) {
                $archivo = $request->file('archivo_comprobante');
                $nombreArchivo = time() . '_' . $donacion->numero_referencia . '.' . $archivo->getClientOriginalExtension();
                
                $ruta = $archivo->storeAs('donaciones/comprobantes', $nombreArchivo, 'public');
                $donacion->archivo_comprobante = $nombreArchivo;
                $donacion->archivo_original = $archivo->getClientOriginalName();
            }

            $donacion->save();

            return response()->json([
                'success' => true,
                'data' => $donacion->load(['validador', 'certificador']),
                'message' => 'Donación creada exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear donación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener donación por ID
     */
    public function obtenerPorId($id): JsonResponse
    {
        try {
            $donacion = Donacion::with(['validador', 'certificador'])->find($id);

            if (!$donacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donación no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $donacion,
                'message' => 'Donación obtenida exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener donación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar donación
     */
    public function validar(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'estado' => 'required|in:validado,rechazado',
                'observaciones_admin' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $donacion = Donacion::find($id);

            if (!$donacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donación no encontrada'
                ], 404);
            }

            $donacion->estado = $request->estado;
            $donacion->observaciones_admin = $request->observaciones_admin;
            $donacion->fecha_validacion = now();
            $donacion->validado_por = auth()->id();
            $donacion->save();

            return response()->json([
                'success' => true,
                'data' => $donacion->load(['validador', 'certificador']),
                'message' => 'Donación validada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al validar donación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Certificar donación y generar PDF
     */
    public function certificar(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'observaciones_certificacion' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $donacion = Donacion::find($id);

            if (!$donacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donación no encontrada'
                ], 404);
            }

            if ($donacion->estado !== 'validado') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden certificar donaciones validadas'
                ], 400);
            }

            // Generar certificado PDF
            $pdf = Pdf::loadView('donaciones.certificado', [
                'donacion' => $donacion,
                'observaciones' => $request->observaciones_certificacion
            ]);

            $nombreCertificado = 'certificado_' . $donacion->numero_referencia . '.pdf';
            $rutaCertificado = 'donaciones/certificados/' . $nombreCertificado;
            
            Storage::disk('public')->put($rutaCertificado, $pdf->output());

            // Actualizar donación
            $donacion->estado = 'certificado';
            $donacion->certificado_pdf = $nombreCertificado;
            $donacion->fecha_certificacion = now();
            $donacion->certificado_por = auth()->id();
            $donacion->save();

            return response()->json([
                'success' => true,
                'data' => $donacion->load(['validador', 'certificador']),
                'message' => 'Donación certificada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al certificar donación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de donaciones
     */
    public function obtenerEstadisticas(): JsonResponse
    {
        try {
            $estadisticas = Donacion::obtenerEstadisticas();

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar donaciones por palabras clave (solo validadas)
     */
    public function buscarPorPalabrasClave(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'palabras' => 'required|string|min:2'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Palabras de búsqueda requeridas (mínimo 2 caracteres)'
                ], 422);
            }

            $palabras = $request->palabras;
            
            $donaciones = Donacion::where('estado', 'validado')
                ->where(function($query) use ($palabras) {
                    $query->where('nombre_donante', 'like', "%{$palabras}%")
                          ->orWhere('email_donante', 'like', "%{$palabras}%")
                          ->orWhere('mensaje', 'like', "%{$palabras}%")
                          ->orWhere('referencia_donacion', 'like', "%{$palabras}%")
                          ->orWhere('numero_referencia', 'like', "%{$palabras}%");
                })
                ->with(['validador', 'certificador'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $donaciones,
                'message' => "Se encontraron {$donaciones->count()} donaciones validadas"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar donaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar donación por número de referencia
     */
    public function buscarPorReferencia(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'numero_referencia' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Número de referencia requerido'
                ], 422);
            }

            $donacion = Donacion::with(['validador', 'certificador'])
                ->where('numero_referencia', $request->numero_referencia)
                ->first();

            if (!$donacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donación no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $donacion,
                'message' => 'Donación encontrada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar donación: ' . $e->getMessage()
            ], 500);
        }
    }
}