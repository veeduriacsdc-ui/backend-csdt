<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operadores', function (Blueprint $table) {
            $table->id();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('usuario', 50)->unique();
            $table->string('correo', 150)->unique();
            $table->string('contrasena', 255);
            $table->string('telefono', 20)->nullable();
            $table->string('documento_identidad', 20)->unique()->nullable();
            $table->enum('tipo_documento', ['cc', 'ce', 'ti', 'pp', 'nit'])->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('direccion', 200)->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->string('departamento', 100)->nullable();
            $table->string('codigo_postal', 10)->nullable();
            $table->enum('genero', ['masculino', 'femenino', 'otro', 'no_especificado'])->nullable();
            $table->string('profesion', 100);
            $table->string('especializacion', 150);
            $table->string('numero_matricula', 50)->nullable();
            $table->string('entidad_matricula', 100)->nullable();
            $table->integer('anos_experiencia')->default(0);
            $table->text('perfil_profesional')->nullable();
            $table->json('areas_expertise')->nullable();
            $table->string('linkedin', 200)->nullable();
            $table->string('sitio_web', 200)->nullable();
            $table->boolean('acepto_terminos')->default(false);
            $table->boolean('acepto_politicas')->default(false);
            $table->boolean('correo_verificado')->default(false);
            $table->timestamp('correo_verificado_en')->nullable();
            $table->boolean('perfil_verificado')->default(false);
            $table->timestamp('perfil_verificado_en')->nullable();
            $table->timestamp('ultimo_acceso')->nullable();
            $table->enum('estado', ['activo', 'pendiente', 'inactivo', 'suspendido', 'en_revision'])->default('pendiente');
            $table->text('notas')->nullable();
            $table->enum('rol', ['operador', 'administrador', 'supervisor', 'auditor'])->default('operador');
            $table->timestamps();
            $table->softDeletes();

            // Índices para optimización
            $table->index(['correo', 'estado'], 'idx_operadores_correo_estado');
            $table->index(['documento_identidad'], 'idx_operadores_documento');
            $table->index(['profesion', 'especializacion'], 'idx_operadores_profesion');
            $table->index(['rol', 'estado'], 'idx_operadores_rol_estado');
            $table->index(['ciudad', 'departamento'], 'idx_operadores_ubicacion');
            $table->index(['anos_experiencia', 'estado'], 'idx_operadores_experiencia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operadores');
    }
};
