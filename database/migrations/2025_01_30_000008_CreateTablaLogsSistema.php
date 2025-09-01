<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('LogsSistema', function (Blueprint $table) {
            $table->id('IdLog');
            $table->enum('Nivel', ['Info', 'Warning', 'Error', 'Critical', 'Debug', 'Trace'])->default('Info');
            $table->enum('Categoria', ['Autenticacion', 'Seguridad', 'Operacion', 'Sistema', 'BaseDatos', 'API', 'Archivo', 'Email', 'Notificacion', 'Auditoria', 'Rendimiento'])->default('Sistema');
            $table->string('Accion', 200);
            $table->text('Descripcion');
            $table->string('Usuario', 100)->nullable();
            $table->enum('TipoUsuario', ['Cliente', 'Operador', 'Administrador', 'Sistema', 'Anonimo'])->nullable();
            $table->unsignedBigInteger('IdUsuario')->nullable();
            $table->string('TipoUsuarioTabla', 50)->nullable();
            $table->json('DatosAnteriores')->nullable();
            $table->json('DatosNuevos')->nullable();
            $table->string('DireccionIP', 45)->nullable();
            $table->string('AgenteUsuario', 500)->nullable();
            $table->timestamp('FechaCreacion')->useCurrent();
            $table->json('Contexto')->nullable();
            $table->string('Modulo', 100)->nullable();
            $table->string('Funcion', 100)->nullable();
            $table->integer('TiempoEjecucion')->nullable(); // En milisegundos
            $table->enum('Estado', ['Exitoso', 'Fallido', 'Parcial'])->default('Exitoso');
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimización
            $table->index(['Nivel', 'FechaCreacion']);
            $table->index(['Categoria', 'Accion']);
            $table->index(['Usuario', 'FechaCreacion']);
            $table->index(['TipoUsuario', 'FechaCreacion']);
            $table->index(['FechaCreacion']);
            $table->index(['Modulo', 'Categoria']);
            $table->index(['Estado', 'FechaCreacion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('LogsSistema');
    }
};
