<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rol_permiso', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('rol_id')->constrained('roles', 'id')->onDelete('cascade');
            $table->foreignId('permiso_id')->constrained('permisos', 'id')->onDelete('cascade');
            $table->boolean('Permitido')->default(true);
            $table->json('Restricciones')->nullable(); // Condiciones adicionales
            $table->timestamps();

            // Índices
            $table->unique(['rol_id', 'permiso_id']);
            $table->index(['rol_id']);
            $table->index(['permiso_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('RolPermisos');
    }
};
