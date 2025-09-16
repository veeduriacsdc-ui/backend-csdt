<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla de PQRSFD (Peticiones, Quejas, Reclamos, Sugerencias, Felicitaciones y Denuncias)
 *
 * Esta tabla almacena todas las solicitudes ciudadanas del sistema CSDT
 */
return new class extends Migration
{
    /**
     * Ejecutar la migración
     */
    public function up(): void
    {
        Schema::create('pqrsfd', function (Blueprint $table) {
            // Llave primaria
            $table->id('id');

            // Llaves foráneas
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('operador_asignado_id')->nullable()->constrained('operadores')->onDelete('set null');

            // Información básica de la PQRSFD
            $table->enum('tipo_pqrsfd', [
                'peticion', 'queja', 'reclamo', 'sugerencia', 'felicitacion', 'denuncia', 'solicitud_informacion',
            ])->comment('Tipo de solicitud según clasificación PQRSFD');

            $table->string('asunto', 200)->comment('Asunto principal de la solicitud');
            $table->text('narracion_cliente')->comment('Descripción detallada proporcionada por el cliente');
            $table->text('narracion_mejorada_ia')->nullable()->comment('Narración mejorada por IA para mayor claridad');

            // Estado y prioridad
            $table->enum('estado', [
                'pendiente', 'en_proceso', 'radicado', 'cerrado', 'cancelado', 'en_revision', 'pendiente_aprobacion'
            ])->default('pendiente')->comment('Estado actual del proceso');

            $table->enum('prioridad', ['baja', 'media', 'alta', 'urgente'])
                ->default('media')
                ->comment('Nivel de prioridad asignado');

            // Fechas importantes
            $table->timestamp('fecha_registro')->useCurrent()->comment('Fecha de creación de la solicitud');
            $table->timestamp('fecha_ultima_actualizacion')->useCurrent()->useCurrentOnUpdate()->comment('Última modificación');
            $table->timestamp('fecha_radicacion')->nullable()->comment('Fecha en que se radicó formalmente');
            $table->timestamp('fecha_cierre')->nullable()->comment('Fecha de cierre de la solicitud');

            // Información de radicación
            $table->string('numero_radicacion', 50)->nullable()->unique()->comment('Número único de radicación');

            // Información del operador
            $table->text('notas_operador')->nullable()->comment('Notas y observaciones del operador asignado');

            // Información de IA y análisis
            $table->json('recomendaciones_ia')->nullable()->comment('Recomendaciones generadas por IA');
            $table->decimal('presupuesto_estimado', 15, 2)->nullable()->comment('Presupuesto estimado para la solicitud');
            $table->json('analisis_precio_unitario')->nullable()->comment('Análisis detallado de precios unitarios');

            // Clasificación adicional
            $table->enum('categoria', [
                'infraestructura', 'servicios_publicos', 'seguridad', 'educacion',
                'salud', 'transporte', 'medio_ambiente', 'otros'
            ])->nullable()->comment('Categoría específica de la solicitud');

            $table->string('ubicacion_geografica', 200)->nullable()->comment('Ubicación geográfica relacionada');
            $table->json('archivos_adjuntos')->nullable()->comment('Referencias a archivos adjuntos');

            // Campos de auditoría
            $table->timestamps();
            $table->softDeletes();

            // Índices optimizados con nombres descriptivos
            $table->index(['cliente_id', 'estado'], 'idx_pqrsfd_cliente_estado');
            $table->index(['operador_asignado_id', 'estado'], 'idx_pqrsfd_operador_estado');
            $table->index(['tipo_pqrsfd', 'estado'], 'idx_pqrsfd_tipo_estado');
            $table->index(['fecha_registro'], 'idx_pqrsfd_fecha_registro');
            $table->index(['numero_radicacion'], 'idx_pqrsfd_numero_radicacion');
            $table->index(['estado', 'fecha_registro'], 'idx_pqrsfd_estado_fecha');
            $table->index(['prioridad', 'estado'], 'idx_pqrsfd_prioridad_estado');
            $table->index(['categoria', 'estado'], 'idx_pqrsfd_categoria_estado');

            // Índice compuesto para búsquedas avanzadas
            $table->index(['estado', 'prioridad', 'fecha_registro'], 'idx_pqrsfd_busqueda_avanzada');
        });
    }

    /**
     * Revertir la migración
     */
    public function down(): void
    {
        Schema::dropIfExists('pqrsfd');
    }
};
