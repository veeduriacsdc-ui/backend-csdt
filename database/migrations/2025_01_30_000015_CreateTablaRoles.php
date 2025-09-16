<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id('id');
            $table->string('nombre', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['Sistema', 'Personalizado'])->default('Sistema');
            $table->boolean('activo')->default(true);
            $table->boolean('editable')->default(true);
            $table->integer('nivel_acceso')->default(1); // 1=Cliente, 2=Operador, 3=Administrador, 4=SuperAdmin
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['tipo', 'activo'], 'idx_roles_tipo_activo');
            $table->index(['nivel_acceso'], 'idx_roles_nivel_acceso');
            $table->index(['slug'], 'idx_roles_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
