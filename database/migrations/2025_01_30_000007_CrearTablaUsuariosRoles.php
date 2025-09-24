<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios_roles', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('usu_id')->constrained('usuarios')->onDelete('cascade')->comment('Usuario');
            $table->foreignId('rol_id')->constrained('roles')->onDelete('cascade')->comment('Rol');
            $table->boolean('act')->default(true)->comment('Activo');
            $table->timestamp('asig_en')->useCurrent()->comment('Asignado en');
            $table->foreignId('asig_por')->nullable()->constrained('usuarios')->onDelete('set null')->comment('Asignado por');
            $table->text('not')->nullable()->comment('Notas');
            $table->timestamps();

            // Índices
            $table->index(['usu_id', 'act'], 'idx_usu_rol_usu_act');
            $table->index(['rol_id', 'act'], 'idx_usu_rol_rol_act');
            $table->unique(['usu_id', 'rol_id'], 'idx_usu_rol_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios_roles');
    }
};
