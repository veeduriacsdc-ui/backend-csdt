<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Operadores', function (Blueprint $table) {
            $table->id('IdOperador');
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
            $table->string('Profesion', 100);
            $table->string('Especializacion', 150);
            $table->string('NumeroMatricula', 50)->nullable();
            $table->string('EntidadMatricula', 100)->nullable();
            $table->integer('AnosExperiencia')->default(0);
            $table->text('PerfilProfesional')->nullable();
            $table->json('AreasExpertise')->nullable();
            $table->string('Linkedin', 200)->nullable();
            $table->string('SitioWeb', 200)->nullable();
            $table->boolean('AceptoTerminos')->default(false);
            $table->boolean('AceptoPoliticas')->default(false);
            $table->boolean('CorreoVerificado')->default(false);
            $table->timestamp('CorreoVerificadoEn')->nullable();
            $table->boolean('PerfilVerificado')->default(false);
            $table->timestamp('PerfilVerificadoEn')->nullable();
            $table->timestamp('UltimoAcceso')->nullable();
            $table->enum('Estado', ['Activo', 'Pendiente', 'Inactivo', 'Suspendido', 'EnRevision'])->default('Pendiente');
            $table->text('Notas')->nullable();
            $table->enum('Rol', ['Operador', 'Administrador', 'Supervisor', 'Auditor'])->default('Operador');
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimización
            $table->index(['Correo', 'Estado']);
            $table->index(['DocumentoIdentidad']);
            $table->index(['Profesion', 'Especializacion']);
            $table->index(['Rol', 'Estado']);
            $table->index(['Ciudad', 'Departamento']);
            $table->index(['AnosExperiencia', 'Estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Operadores');
    }
};
