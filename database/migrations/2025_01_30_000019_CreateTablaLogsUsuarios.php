<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('LogsUsuarios', function (Blueprint $table) {
            $table->id('IdLogUsuario');
            $table->morphs('usuario'); // Polymorphic: puede ser Cliente o Operador
            $table->enum('TipoAccion', [
                'Login', 'Logout', 'CambioPassword', 'ActualizacionPerfil', 'CambioRol',
                'AsignacionPermiso', 'RevocacionPermiso', 'CreacionUsuario', 'EliminacionUsuario',
                'SuspensionUsuario', 'ActivacionUsuario', 'AccesoDenegado', 'IntentoAcceso',
                'CambioConfiguracion', 'ExportacionDatos', 'ImportacionDatos', 'Otros',
            ]);
            $table->string('Accion', 200);
            $table->text('Descripcion')->nullable();
            $table->json('DatosAnteriores')->nullable();
            $table->json('DatosNuevos')->nullable();
            $table->string('DireccionIP', 45)->nullable();
            $table->string('UserAgent', 500)->nullable();
            $table->string('Modulo', 100)->nullable();
            $table->string('Funcion', 100)->nullable();
            $table->enum('NivelImportancia', ['Bajo', 'Normal', 'Alto', 'Critico'])->default('Normal');
            $table->enum('Estado', ['Exitoso', 'Fallido', 'Parcial'])->default('Exitoso');
            $table->timestamp('FechaAccion')->useCurrent();
            $table->timestamps();

            // Índices
            $table->index(['FechaAccion']);
            $table->index(['TipoAccion', 'FechaAccion']);
            $table->index(['Modulo', 'Funcion']);
            $table->index(['NivelImportancia', 'FechaAccion']);
            $table->index(['Estado', 'FechaAccion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('LogsUsuarios');
    }
};
