<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ActividadesCaso', function (Blueprint $table) {
            $table->id('IdActividad');
            $table->foreignId('IdPQRSFD')->constrained('PQRSFD', 'IdPQRSFD')->onDelete('cascade');
            $table->foreignId('IdOperadorResponsable')->constrained('Operadores', 'IdOperador')->onDelete('cascade');
            $table->string('NombreActividad', 200);
            $table->text('Descripcion');
            $table->date('FechaInicioEstimada');
            $table->date('FechaFinEstimada');
            $table->timestamp('FechaInicioReal')->nullable();
            $table->timestamp('FechaFinReal')->nullable();
            $table->enum('Estado', [
                'Pendiente', 'EnProgreso', 'Completada', 'Retrasada', 'Cancelada', 'EnPausa', 'Revisada'
            ])->default('Pendiente');
            $table->decimal('PresupuestoAsignado', 15, 2)->nullable();
            $table->json('Dependencias')->nullable();
            $table->enum('Prioridad', ['Baja', 'Media', 'Alta', 'Urgente'])->default('Media');
            $table->text('Observaciones')->nullable();
            $table->enum('TipoActividad', ['Investigacion', 'Inspeccion', 'Coordinacion', 'Documentacion', 'Seguimiento', 'Otros'])->default('Investigacion');
            $table->integer('PorcentajeCompletado')->default(0);
            $table->string('UbicacionActividad', 200)->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimización
            $table->index(['IdPQRSFD', 'Estado']);
            $table->index(['IdOperadorResponsable', 'Estado']);
            $table->index(['Estado', 'FechaInicioEstimada']);
            $table->index(['FechaFinEstimada']);
            $table->index(['Prioridad', 'Estado']);
            $table->index(['TipoActividad', 'Estado']);
            $table->index(['PorcentajeCompletado', 'Estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ActividadesCaso');
    }
};
