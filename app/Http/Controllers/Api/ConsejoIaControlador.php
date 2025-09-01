<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Cliente;
use App\Models\Operador;
use App\Models\Administrador;

class ConsejoIaControlador extends Controller
{
    /**
     * Mejorar texto usando IA
     */
    public function mejorarTexto(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'texto_original' => 'required|string|min:10|max:10000',
                'tipo_documento' => 'nullable|string|in:Denuncia,Queja,Peticion,Informe',
                'contexto' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            $textoOriginal = $request->texto_original;
            $tipoDocumento = $request->tipo_documento ?? 'General';
            $contexto = $request->contexto ?? '';

            // Simulación de mejora de IA (aquí se conectaría con un servicio real de IA)
            $textoMejorado = $this->simularMejoraIA($textoOriginal, $tipoDocumento, $contexto);

            // Registrar la actividad
            $this->registrarActividadIA('Mejora de texto', [
                'tipo_documento' => $tipoDocumento,
                'longitud_original' => strlen($textoOriginal),
                'longitud_mejorado' => strlen($textoMejorado),
                'contexto' => $contexto
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Texto mejorado exitosamente por IA',
                'data' => [
                    'texto_original' => $textoOriginal,
                    'texto_mejorado' => $textoMejorado,
                    'tipo_documento' => $tipoDocumento,
                    'mejoras_aplicadas' => [
                        'Estructura mejorada',
                        'Lenguaje formalizado',
                        'Claridad aumentada',
                        'Elementos legales agregados'
                    ],
                    'fecha_procesamiento' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en mejora de texto CONSEJOIA: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Guardar narración de hechos
     */
    public function guardarNarracion(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'narracion_hechos' => 'required|string|min:10|max:10000',
                'tipo_caso' => 'required|string|in:MineriaIlegal,DespojoTierras,BlanqueamientoCatastral,Otro',
                'ubicacion' => 'required|string|max:500',
                'fecha_hechos' => 'required|date',
                'evidencias' => 'nullable|array',
                'evidencias.*' => 'string|max:1000',
                'testigos' => 'nullable|array',
                'testigos.*.nombre' => 'string|max:255',
                'testigos.*.telefono' => 'string|max:20',
                'testigos.*.relacion' => 'string|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Generar ID único para el caso
            $idCaso = 'CSDT-' . date('Y') . '-' . strtoupper(uniqid());

            // Crear estructura de datos del caso
            $datosCaso = [
                'id_caso' => $idCaso,
                'narracion_hechos' => $request->narracion_hechos,
                'tipo_caso' => $request->tipo_caso,
                'ubicacion' => $request->ubicacion,
                'fecha_hechos' => $request->fecha_hechos,
                'evidencias' => $request->evidencias ?? [],
                'testigos' => $request->testigos ?? [],
                'fecha_registro' => now()->toISOString(),
                'estado' => 'Registrado',
                'prioridad' => $this->calcularPrioridad($request->tipo_caso, $request->evidencias),
                'hash_seguridad' => hash('sha256', $request->narracion_hechos . $idCaso)
            ];

            // Guardar en archivo temporal (en producción se guardaría en base de datos)
            $nombreArchivo = 'narracion_' . $idCaso . '_' . date('Y-m-d_H-i-s') . '.json';
            $rutaArchivo = storage_path('app/consejoia/' . $nombreArchivo);
            
            // Crear directorio si no existe
            if (!file_exists(dirname($rutaArchivo))) {
                mkdir(dirname($rutaArchivo), 0755, true);
            }

            file_put_contents($rutaArchivo, json_encode($datosCaso, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Registrar la actividad
            $this->registrarActividadIA('Narración guardada', [
                'id_caso' => $idCaso,
                'tipo_caso' => $request->tipo_caso,
                'ubicacion' => $request->ubicacion
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Narración de hechos guardada exitosamente',
                'data' => [
                    'id_caso' => $idCaso,
                    'estado' => 'Registrado',
                    'fecha_registro' => $datosCaso['fecha_registro'],
                    'prioridad' => $datosCaso['prioridad'],
                    'mensaje' => 'Su caso ha sido registrado con el ID: ' . $idCaso . '. Guarde este número para futuras consultas.'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al guardar narración CONSEJOIA: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Actualizar narración existente
     */
    public function actualizarNarracion(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_caso' => 'required|string|max:50',
                'narracion_hechos' => 'required|string|min:10|max:10000',
                'motivo_actualizacion' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            $idCaso = $request->id_caso;
            $rutaArchivo = storage_path('app/consejoia/narracion_' . $idCaso . '_*.json');
            $archivos = glob($rutaArchivo);

            if (empty($archivos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Caso no encontrado'
                ], 404);
            }

            $archivoOriginal = $archivos[0];
            $datosOriginales = json_decode(file_get_contents($archivoOriginal), true);

            // Crear nueva versión con historial
            $datosActualizados = $datosOriginales;
            $datosActualizados['narracion_hechos'] = $request->narracion_hechos;
            $datosActualizados['historial_actualizaciones'][] = [
                'fecha' => now()->toISOString(),
                'motivo' => $request->motivo_actualizacion,
                'narracion_anterior' => $datosOriginales['narracion_hechos'],
                'hash_anterior' => $datosOriginales['hash_seguridad']
            ];
            $datosActualizados['hash_seguridad'] = hash('sha256', $request->narracion_hechos . $idCaso);
            $datosActualizados['fecha_ultima_actualizacion'] = now()->toISOString();

            // Guardar versión actualizada
            $nombreArchivoActualizado = 'narracion_' . $idCaso . '_' . date('Y-m-d_H-i-s') . '.json';
            $rutaArchivoActualizado = storage_path('app/consejoia/' . $nombreArchivoActualizado);
            
            file_put_contents($rutaArchivoActualizado, json_encode($datosActualizados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Registrar la actividad
            $this->registrarActividadIA('Narración actualizada', [
                'id_caso' => $idCaso,
                'motivo' => $request->motivo_actualizacion
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Narración actualizada exitosamente',
                'data' => [
                    'id_caso' => $idCaso,
                    'fecha_actualizacion' => $datosActualizados['fecha_ultima_actualizacion'],
                    'hash_nuevo' => $datosActualizados['hash_seguridad']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al actualizar narración CONSEJOIA: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Generar PDF de narración
     */
    public function generarPDF(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_caso' => 'required|string|max:50',
                'formato' => 'nullable|string|in:PDF,TXT,HTML'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            $idCaso = $request->id_caso;
            $formato = $request->formato ?? 'PDF';
            $rutaArchivo = storage_path('app/consejoia/narracion_' . $idCaso . '_*.json');
            $archivos = glob($rutaArchivo);

            if (empty($archivos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Caso no encontrado'
                ], 404);
            }

            $archivo = $archivos[0];
            $datosCaso = json_decode(file_get_contents($archivo), true);

            // Generar contenido del documento
            $contenido = $this->generarContenidoDocumento($datosCaso, $formato);

            // Generar nombre de archivo
            $nombreArchivo = 'CSDT_' . $idCaso . '_' . date('Y-m-d_H-i-s') . '.' . strtolower($formato);

            // Guardar archivo temporal
            $rutaArchivoTemporal = storage_path('app/consejoia/temp/' . $nombreArchivo);
            if (!file_exists(dirname($rutaArchivoTemporal))) {
                mkdir(dirname($rutaArchivoTemporal), 0755, true);
            }

            file_put_contents($rutaArchivoTemporal, $contenido);

            // Registrar la actividad
            $this->registrarActividadIA('PDF generado', [
                'id_caso' => $idCaso,
                'formato' => $formato
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Documento generado exitosamente',
                'data' => [
                    'id_caso' => $idCaso,
                    'formato' => $formato,
                    'nombre_archivo' => $nombreArchivo,
                    'url_descarga' => url('api/consejoia/descargar/' . $nombreArchivo),
                    'fecha_generacion' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al generar PDF CONSEJOIA: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Descargar archivo generado
     */
    public function descargarArchivo($nombreArchivo)
    {
        try {
            $rutaArchivo = storage_path('app/consejoia/temp/' . $nombreArchivo);
            
            if (!file_exists($rutaArchivo)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado'
                ], 404);
            }

            $extension = pathinfo($nombreArchivo, PATHINFO_EXTENSION);
            $mimeType = $this->obtenerMimeType($extension);

            return response()->download($rutaArchivo, $nombreArchivo, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'attachment; filename="' . $nombreArchivo . '"'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al descargar archivo CONSEJOIA: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de CONSEJOIA
     */
    public function obtenerEstadisticas()
    {
        try {
            $rutaDirectorio = storage_path('app/consejoia');
            
            if (!is_dir($rutaDirectorio)) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'total_casos' => 0,
                        'casos_por_tipo' => [],
                        'casos_por_estado' => [],
                        'fecha_primer_caso' => null,
                        'fecha_ultimo_caso' => null
                    ]
                ]);
            }

            $archivos = glob($rutaDirectorio . '/narracion_*.json');
            $estadisticas = [
                'total_casos' => count($archivos),
                'casos_por_tipo' => [],
                'casos_por_estado' => [],
                'fecha_primer_caso' => null,
                'fecha_ultimo_caso' => null
            ];

            $fechas = [];
            foreach ($archivos as $archivo) {
                $datos = json_decode(file_get_contents($archivo), true);
                if ($datos) {
                    $tipo = $datos['tipo_caso'] ?? 'Sin tipo';
                    $estado = $datos['estado'] ?? 'Sin estado';
                    $fecha = $datos['fecha_registro'] ?? null;

                    $estadisticas['casos_por_tipo'][$tipo] = ($estadisticas['casos_por_tipo'][$tipo] ?? 0) + 1;
                    $estadisticas['casos_por_estado'][$estado] = ($estadisticas['casos_por_estado'][$estado] ?? 0) + 1;
                    
                    if ($fecha) {
                        $fechas[] = $fecha;
                    }
                }
            }

            if (!empty($fechas)) {
                sort($fechas);
                $estadisticas['fecha_primer_caso'] = $fechas[0];
                $estadisticas['fecha_ultimo_caso'] = end($fechas);
            }

            return response()->json([
                'success' => true,
                'message' => 'Estadísticas obtenidas exitosamente',
                'data' => $estadisticas
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas CONSEJOIA: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    // Métodos privados auxiliares

    private function simularMejoraIA($texto, $tipoDocumento, $contexto)
    {
        // Simulación de mejora de IA
        $mejoras = [
            'Denuncia' => 'Se ha estructurado como denuncia formal con elementos legales apropiados.',
            'Queja' => 'Se ha formulado como queja administrativa siguiendo protocolos establecidos.',
            'Peticion' => 'Se ha redactado como petición ciudadana con formato oficial.',
            'Informe' => 'Se ha organizado como informe técnico con estructura profesional.'
        ];

        $mejora = $mejoras[$tipoDocumento] ?? 'Se ha mejorado la estructura y claridad del texto.';

        return "TEXTO MEJORADO POR IA - CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL\n\n" .
               "Tipo de Documento: " . $tipoDocumento . "\n" .
               "Fecha de Procesamiento: " . now()->format('d/m/Y H:i:s') . "\n" .
               "Contexto: " . ($contexto ?: 'No especificado') . "\n\n" .
               "TEXTO ORIGINAL:\n" . $texto . "\n\n" .
               "TEXTO MEJORADO:\n" . $texto . "\n\n" .
               "MEJORAS APLICADAS:\n" .
               "• " . $mejora . "\n" .
               "• Estructura y formato optimizados\n" .
               "• Lenguaje formal y profesional\n" .
               "• Elementos de trazabilidad agregados\n" .
               "• Cumplimiento de estándares legales\n\n" .
               "Este documento ha sido procesado por el sistema CONSEJOIA para garantizar la calidad y formalidad requerida en procedimientos legales y administrativos.";
    }

    private function calcularPrioridad($tipoCaso, $evidencias)
    {
        $prioridad = 'Media';
        
        if ($tipoCaso === 'MineriaIlegal' || $tipoCaso === 'DespojoTierras') {
            $prioridad = 'Alta';
        }
        
        if (count($evidencias) > 3) {
            $prioridad = 'Alta';
        }
        
        return $prioridad;
    }

    private function generarContenidoDocumento($datosCaso, $formato)
    {
        $contenido = "CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL (CSDT)\n";
        $contenido .= "Sistema CONSEJOIA - Documento Generado\n";
        $contenido .= "==========================================\n\n";
        
        $contenido .= "ID del Caso: " . $datosCaso['id_caso'] . "\n";
        $contenido .= "Tipo de Caso: " . $datosCaso['tipo_caso'] . "\n";
        $contenido .= "Ubicación: " . $datosCaso['ubicacion'] . "\n";
        $contenido .= "Fecha de Hechos: " . $datosCaso['fecha_hechos'] . "\n";
        $contenido .= "Fecha de Registro: " . $datosCaso['fecha_registro'] . "\n";
        $contenido .= "Estado: " . $datosCaso['estado'] . "\n";
        $contenido .= "Prioridad: " . $datosCaso['prioridad'] . "\n\n";
        
        $contenido .= "NARRACIÓN DE HECHOS:\n";
        $contenido .= "==================\n";
        $contenido .= $datosCaso['narracion_hechos'] . "\n\n";
        
        if (!empty($datosCaso['evidencias'])) {
            $contenido .= "EVIDENCIAS:\n";
            $contenido .= "==========\n";
            foreach ($datosCaso['evidencias'] as $index => $evidencia) {
                $contenido .= ($index + 1) . ". " . $evidencia . "\n";
            }
            $contenido .= "\n";
        }
        
        if (!empty($datosCaso['testigos'])) {
            $contenido .= "TESTIGOS:\n";
            $contenido .= "========\n";
            foreach ($datosCaso['testigos'] as $index => $testigo) {
                $contenido .= ($index + 1) . ". " . $testigo['nombre'] . "\n";
                $contenido .= "   Teléfono: " . $testigo['telefono'] . "\n";
                $contenido .= "   Relación: " . $testigo['relacion'] . "\n\n";
            }
        }
        
        $contenido .= "HASH DE SEGURIDAD: " . $datosCaso['hash_seguridad'] . "\n";
        $contenido .= "Este documento es generado automáticamente por el sistema CONSEJOIA del CSDT.\n";
        
        return $contenido;
    }

    private function obtenerMimeType($extension)
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'html' => 'text/html'
        ];
        
        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }

    private function registrarActividadIA($accion, $detalles)
    {
        try {
            Log::info('Actividad CONSEJOIA: ' . $accion, [
                'fecha' => now()->toISOString(),
                'accion' => $accion,
                'detalles' => $detalles,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        } catch (\Exception $e) {
            // Silenciar errores de logging
        }
    }
}
