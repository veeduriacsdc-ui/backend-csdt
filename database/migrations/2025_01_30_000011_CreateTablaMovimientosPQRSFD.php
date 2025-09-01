<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('MovimientosPQRSFD', function (Blueprint $table) {
            $table->id('IdMovimiento');
            $table->foreignId('IdPQRSFD')->constrained('PQRSFD', 'IdPQRSFD')->onDelete('cascade');
            $table->foreignId('IdOperador')->nullable()->constrained('Operadores', 'IdOperador')->onDelete('set null');
            $table->foreignId('IdCliente')->nullable()->constrained('Clientes', 'IdCliente')->onDelete('cascade');
            $table->enum('TipoAccion', [
                'Creacion', 'ActualizacionEstado', 'AsignacionOperador', 'ComentarioOperador', 
                'ComentarioCliente', 'CambioPrioridad', 'CambioCategoria', 'DocumentoAdjuntado',
                'DocumentoEliminado', 'NotificacionEnviada', 'PresupuestoActualizado', 'Otros'
            ]);
            $table->string('Accion', 200); // Descripción de la acción realizada
            $table->text('Descripcion')->nullable(); // Detalles adicionales sobre la acción
            $table->json('DatosAnteriores')->nullable(); // Datos antes del cambio
            $table->json('DatosNuevos')->nullable(); // Datos después del cambio
            $table->enum('NivelImportancia', ['Bajo', 'Normal', 'Alto', 'Critico'])->default('Normal');
            $table->timestamp('FechaMovimiento')->useCurrent();
            $table->string('DireccionIP', 45)->nullable();
            $table->string('UserAgent', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimización
            $table->index(['IdPQRSFD', 'TipoAccion']);
            $table->index(['IdOperador', 'FechaMovimiento']);
            $table->index(['IdCliente', 'FechaMovimiento']);
            $table->index(['TipoAccion', 'FechaMovimiento']);
            $table->index(['NivelImportancia', 'FechaMovimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('MovimientosPQRSFD');
    }
};
