<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PQRSFD', function (Blueprint $table) {
            $table->id('IdPQRSFD');
            $table->foreignId('IdCliente')->constrained('Clientes', 'IdCliente')->onDelete('cascade');
            $table->foreignId('IdOperadorAsignado')->nullable()->constrained('Operadores', 'IdOperador')->onDelete('set null');
            $table->enum('TipoPQRSFD', [
                'Peticion', 'Queja', 'Reclamo', 'Sugerencia', 'Felicitacion', 'Denuncia', 'SolicitudInformacion'
            ]);
            $table->string('Asunto', 200);
            $table->text('NarracionCliente');
            $table->text('NarracionMejoradaIA')->nullable();
            $table->enum('Estado', ['Pendiente', 'EnProceso', 'Radicado', 'Cerrado', 'Cancelado', 'EnRevision', 'PendienteAprobacion'])->default('Pendiente');
            $table->enum('Prioridad', ['Baja', 'Media', 'Alta', 'Urgente'])->default('Media');
            $table->timestamp('FechaRegistro')->useCurrent();
            $table->timestamp('FechaUltimaActualizacion')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('FechaRadicacion')->nullable();
            $table->string('NumeroRadicacion', 50)->nullable()->unique();
            $table->text('NotasOperador')->nullable();
            $table->json('RecomendacionesIA')->nullable();
            $table->decimal('PresupuestoEstimado', 15, 2)->nullable();
            $table->json('AnalisisPrecioUnitario')->nullable();
            $table->enum('Categoria', ['Infraestructura', 'ServiciosPublicos', 'Seguridad', 'Educacion', 'Salud', 'Transporte', 'MedioAmbiente', 'Otros'])->nullable();
            $table->string('UbicacionGeografica', 200)->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimización
            $table->index(['IdCliente', 'Estado']);
            $table->index(['IdOperadorAsignado', 'Estado']);
            $table->index(['TipoPQRSFD', 'Estado']);
            $table->index(['FechaRegistro']);
            $table->index(['NumeroRadicacion']);
            $table->index(['Estado', 'FechaRegistro']);
            $table->index(['Prioridad', 'Estado']);
            $table->index(['Categoria', 'Estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PQRSFD');
    }
};
