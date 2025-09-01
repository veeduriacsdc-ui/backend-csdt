<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ReportesSistema', function (Blueprint $table) {
            $table->id('IdReporte');
            $table->string('Titulo', 200);
            $table->text('Descripcion')->nullable();
            $table->enum('Tipo', ['PQRSFD', 'Donaciones', 'Operadores', 'Financiero', 'Estadisticas', 'Auditoria', 'Sistema', 'Rendimiento', 'Seguridad', 'Usuarios'])->default('Estadisticas');
            $table->enum('Formato', ['PDF', 'Excel', 'CSV', 'JSON', 'XML', 'HTML'])->default('PDF');
            $table->enum('Estado', ['Pendiente', 'EnProceso', 'Completado', 'Error', 'Cancelado', 'EnCola', 'Expirado'])->default('Pendiente');
            $table->unsignedBigInteger('IdUsuarioSolicitante')->nullable();
            $table->enum('TipoUsuarioSolicitante', ['Cliente', 'Operador', 'Administrador', 'Sistema'])->nullable();
            $table->json('Parametros')->nullable();
            $table->json('Resultados')->nullable();
            $table->string('RutaArchivo', 500)->nullable();
            $table->bigInteger('TamanoArchivo')->nullable(); // En bytes
            $table->timestamp('FechaSolicitud')->useCurrent();
            $table->timestamp('FechaInicioProcesamiento')->nullable();
            $table->timestamp('FechaCompletado')->nullable();
            $table->text('Error')->nullable();
            $table->integer('TiempoProcesamiento')->nullable(); // En segundos
            $table->json('Metadatos')->nullable();
            $table->enum('Prioridad', ['Baja', 'Normal', 'Alta', 'Urgente'])->default('Normal');
            $table->timestamp('FechaExpiracion')->nullable();
            $table->boolean('Programado')->default(false);
            $table->string('CronExpression', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimización
            $table->index(['Tipo', 'Estado']);
            $table->index(['IdUsuarioSolicitante', 'TipoUsuarioSolicitante']);
            $table->index(['Estado', 'FechaSolicitud']);
            $table->index(['FechaSolicitud']);
            $table->index(['Tipo', 'FechaSolicitud']);
            $table->index(['Prioridad', 'Estado']);
            $table->index(['Programado', 'Estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ReportesSistema');
    }
};
