<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear tabla de narraciones de IA
        Schema::create('narraciones_consejo_ia', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_narracion', 50)->unique();
            $table->enum('tipo_narracion', ['acta', 'resumen', 'informe', 'comunicado']);
            $table->text('contenido');
            $table->longText('narracion_generada')->nullable();
            $table->integer('confianza')->default(0);
            $table->json('datos_cliente')->nullable();
            $table->json('ubicacion')->nullable();
            $table->bigInteger('usu_id')->unsigned()->nullable();
            $table->bigInteger('vee_id')->unsigned()->nullable();
            $table->enum('est', ['act', 'ina', 'sus'])->default('act');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tipo_narracion', 'est']);
            $table->index(['usu_id', 'vee_id']);
        });

        // Crear tabla de archivos
        Schema::create('archivos', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('usu_id')->unsigned();
            $table->bigInteger('vee_id')->unsigned()->nullable();
            $table->string('nom', 255);
            $table->string('tip', 50);
            $table->bigInteger('tam');
            $table->string('ruta', 500);
            $table->text('des')->nullable();
            $table->enum('est', ['act', 'ina', 'sus'])->default('act');
            $table->string('hash_archivo', 64)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['usu_id', 'vee_id']);
            $table->index(['tip', 'est']);
            $table->index('hash_archivo');
        });

        // Crear tabla de configuraciones
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100)->unique();
            $table->text('valor');
            $table->string('tipo', 50)->default('string');
            $table->string('categoria', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->enum('est', ['act', 'ina'])->default('act');
            $table->bigInteger('usu_id')->unsigned()->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['categoria', 'est']);
            $table->index('clave');
        });

        // Crear tabla de análisis de IA
        Schema::create('analisis_ia', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('vee_id')->unsigned();
            $table->text('contexto_adicional')->nullable();
            $table->text('analisis_generado');
            $table->string('prioridad_sugerida', 20);
            $table->string('categoria_sugerida', 50);
            $table->integer('confianza')->default(0);
            $table->json('recomendaciones')->nullable();
            $table->json('metadatos')->nullable();
            $table->enum('est', ['act', 'ina', 'sus'])->default('act');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['vee_id', 'est']);
            $table->index('prioridad_sugerida');
        });

        // Crear tabla de estadísticas de IA
        Schema::create('estadisticas_ia', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('tipo_metrica', 50);
            $table->string('categoria', 100)->nullable();
            $table->decimal('valor', 15, 2);
            $table->text('descripcion')->nullable();
            $table->json('metadatos')->nullable();
            $table->timestamps();
            
            $table->index(['fecha', 'tipo_metrica']);
            $table->index('categoria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estadisticas_ia');
        Schema::dropIfExists('analisis_ia');
        Schema::dropIfExists('configuraciones');
        Schema::dropIfExists('archivos');
        Schema::dropIfExists('narraciones_consejo_ia');
    }
};
