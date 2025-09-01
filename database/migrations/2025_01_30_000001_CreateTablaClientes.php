<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Clientes', function (Blueprint $table) {
            $table->id('IdCliente');
            $table->string('Nombres', 100);
            $table->string('Apellidos', 100);
            $table->string('Correo', 150)->unique();
            $table->string('Contrasena', 255);
            $table->string('Telefono', 20)->nullable();
            $table->string('DocumentoIdentidad', 20)->unique()->nullable();
            $table->enum('TipoDocumento', ['CC', 'CE', 'TI', 'PP', 'NIT'])->nullable();
            $table->date('FechaNacimiento')->nullable();
            $table->string('Direccion', 200)->nullable();
            $table->string('Ciudad', 100)->nullable();
            $table->string('Departamento', 100)->nullable();
            $table->string('CodigoPostal', 10)->nullable();
            $table->enum('Genero', ['Masculino', 'Femenino', 'Otro', 'NoEspecificado'])->nullable();
            $table->boolean('AceptoTerminos')->default(false);
            $table->boolean('AceptoPoliticas')->default(false);
            $table->boolean('CorreoVerificado')->default(false);
            $table->timestamp('CorreoVerificadoEn')->nullable();
            $table->timestamp('UltimoAcceso')->nullable();
            $table->enum('Estado', ['Activo', 'Inactivo', 'Suspendido', 'PendienteVerificacion'])->default('PendienteVerificacion');
            $table->text('Notas')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimización
            $table->index(['Correo', 'Estado']);
            $table->index(['DocumentoIdentidad']);
            $table->index(['Ciudad', 'Departamento']);
            $table->index(['FechaNacimiento']);
            $table->index(['Genero', 'Estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Clientes');
    }
};
