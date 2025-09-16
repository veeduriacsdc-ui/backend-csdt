<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla de donaciones
 *
 * Esta tabla registra todas las donaciones realizadas por ciudadanos al sistema CSDT
 */
return new class extends Migration
{
    /**
     * Ejecutar la migración
     */
    public function up(): void
    {
        Schema::create('donaciones', function (Blueprint $table) {
            // Llave primaria
            $table->id('id');

            // Llaves foráneas
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('pqrsfd_asociado_id')->nullable()->constrained('pqrsfd')->onDelete('set null');
            $table->foreignId('validada_por_operador_id')->nullable()->constrained('operadores')->onDelete('set null');

            // Información financiera básica
            $table->decimal('monto', 15, 2)->comment('Monto de la donación');
            $table->enum('moneda', ['cop', 'usd', 'eur'])
                ->default('cop')
                ->comment('Moneda en la que se realizó la donación');

            $table->enum('metodo_pago', [
                'tarjeta_credito', 'tarjeta_debito', 'transferencia_bancaria',
                'efectivo', 'billetera_digital', 'otros'
            ])->default('transferencia_bancaria')->comment('Método utilizado para el pago');

            // Fechas importantes
            $table->timestamp('fecha_donacion')->useCurrent()->comment('Fecha y hora de la donación');
            $table->timestamp('fecha_validacion')->nullable()->comment('Fecha en que fue validada por operador');

            // Estado y validación
            $table->enum('estado', [
                'pendiente', 'confirmada', 'rechazada', 'en_proceso', 'cancelada'
            ])->default('pendiente')->comment('Estado actual de la donación');

            // Información adicional
            $table->text('notas')->nullable()->comment('Notas adicionales sobre la donación');
            $table->boolean('anonima')->default(false)->comment('Indica si la donación es anónima');
            $table->string('comprobante_pago', 500)->nullable()->comment('Ruta al comprobante de pago');

            // Tipo y motivo de la donación
            $table->enum('tipo_donacion', ['unica', 'recurrente', 'mensual', 'anual'])
                ->default('unica')
                ->comment('Tipo de donación (única o recurrente)');

            $table->string('motivo_donacion', 300)->nullable()->comment('Motivo específico de la donación');

            // Información de procesamiento
            $table->string('referencia_pago', 100)->nullable()->comment('Referencia única del pago');
            $table->json('metadatos_pago')->nullable()->comment('Información adicional del procesamiento de pago');

            // Información de campaña (si aplica)
            $table->string('campana_asociada', 100)->nullable()->comment('Campaña a la que pertenece la donación');
            $table->string('codigo_promocional', 50)->nullable()->comment('Código promocional utilizado');

            // Campos de auditoría
            $table->timestamps();
            $table->softDeletes();

            // Índices optimizados con nombres descriptivos
            $table->index(['cliente_id', 'estado'], 'idx_donaciones_cliente_estado');
            $table->index(['estado', 'fecha_donacion'], 'idx_donaciones_estado_fecha');
            $table->index(['metodo_pago', 'estado'], 'idx_donaciones_metodo_estado');
            $table->index(['moneda', 'estado'], 'idx_donaciones_moneda_estado');
            $table->index(['tipo_donacion', 'estado'], 'idx_donaciones_tipo_estado');
            $table->index(['fecha_donacion'], 'idx_donaciones_fecha_donacion');
            $table->index(['anonima', 'estado'], 'idx_donaciones_anonima_estado');
            $table->index(['pqrsfd_asociado_id'], 'idx_donaciones_pqrsfd_asociado');

            // Índice compuesto para reportes financieros
            $table->index(['estado', 'moneda', 'fecha_donacion'], 'idx_donaciones_reportes_financieros');
        });
    }

    /**
     * Revertir la migración
     */
    public function down(): void
    {
        Schema::dropIfExists('donaciones');
    }
};
