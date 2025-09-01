<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('NotificacionesSistema', function (Blueprint $table) {
            $table->id('IdNotificacion');
            $table->string('Titulo', 200);
            $table->text('Mensaje');
            $table->enum('Tipo', ['Informacion', 'Exito', 'Advertencia', 'Error', 'Sistema', 'Seguridad', 'Actualizacion', 'Recordatorio', 'Solicitud'])->default('Informacion');
            $table->enum('Destinatario', ['Cliente', 'Operador', 'Administrador', 'Sistema', 'Todos', 'GrupoEspecifico'])->default('Sistema');
            $table->unsignedBigInteger('IdDestinatario')->nullable();
            $table->enum('TipoDestinatario', ['Cliente', 'Operador', 'Administrador', 'Grupo'])->nullable();
            $table->enum('CanalEnvio', ['Email', 'SMS', 'Push', 'Sistema', 'Todos'])->default('Sistema');
            $table->boolean('Leida')->default(false);
            $table->timestamp('FechaCreacion')->useCurrent();
            $table->timestamp('FechaLeida')->nullable();
            $table->json('DatosAdicionales')->nullable();
            $table->enum('Prioridad', ['Baja', 'Media', 'Alta', 'Urgente'])->default('Media');
            $table->boolean('Enviada')->default(false);
            $table->timestamp('FechaEnvio')->nullable();
            $table->enum('Estado', ['Pendiente', 'Enviada', 'Entregada', 'Leida', 'Fallida'])->default('Pendiente');
            $table->integer('IntentosEnvio')->default(0);
            $table->text('ErrorEnvio')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimización
            $table->index(['Destinatario', 'Leida']);
            $table->index(['IdDestinatario', 'TipoDestinatario']);
            $table->index(['Tipo', 'Prioridad']);
            $table->index(['FechaCreacion']);
            $table->index(['Leida', 'FechaCreacion']);
            $table->index(['CanalEnvio', 'Estado']);
            $table->index(['Estado', 'FechaCreacion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('NotificacionesSistema');
    }
};
