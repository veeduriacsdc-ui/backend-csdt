<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
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
            $table->boolean('acepto_terminos')->default(false);
            $table->boolean('acepto_politicas')->default(false);
            $table->boolean('correo_verificado')->default(false);
            $table->timestamp('correo_verificado_en')->nullable();
            $table->timestamp('ultimo_acceso')->nullable();
            $table->enum('estado', ['activo', 'inactivo', 'suspendido', 'pendiente_verificacion'])->default('pendiente_verificacion');
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices para optimización
            $table->index(['correo', 'estado'], 'idx_clientes_correo_estado');
            $table->index(['documento_identidad'], 'idx_clientes_documento');
            $table->index(['ciudad', 'departamento'], 'idx_clientes_ubicacion');
            $table->index(['fecha_nacimiento'], 'idx_clientes_fecha_nac');
            $table->index(['genero', 'estado'], 'idx_clientes_genero_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
