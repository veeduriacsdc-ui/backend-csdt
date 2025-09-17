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
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100)->unique();
            $table->text('valor')->nullable();
            $table->string('tipo', 50)->default('string');
            $table->string('categoria', 100)->default('general');
            $table->text('descripcion')->nullable();
            $table->boolean('es_publica')->default(false);
            $table->boolean('es_editable')->default(true);
            $table->json('opciones')->nullable();
            $table->string('grupo', 50)->nullable();
            $table->integer('orden')->default(0);
            $table->timestamps();
            
            $table->index(['categoria', 'grupo'], 'idx_config_cat_grupo');
            $table->index(['es_publica', 'es_editable'], 'idx_config_publica_editable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuraciones');
    }
};
