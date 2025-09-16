<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permisos', function (Blueprint $table) {
            $table->id('id');
            $table->string('nombre', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->string('modulo', 50); // 'publico', 'cliente', 'operador', 'administrador', 'compartido'
            $table->string('funcion', 100); // 'ver', 'crear', 'editar', 'eliminar', 'gestionar'
            $table->string('recurso', 100); // 'pqrsfd', 'donaciones', 'usuarios', etc.
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['modulo', 'recurso'], 'idx_permisos_modulo_recurso');
            $table->index(['slug'], 'idx_permisos_slug');
            $table->index(['activo', 'orden'], 'idx_permisos_activo_orden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permisos');
    }
};
