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
            $table->string('nom', 100)->unique()->comment('Nombre rol');
            $table->string('des', 255)->nullable()->comment('Descripción');
            $table->enum('est', ['act', 'ina'])->default('act')->comment('Estado');
            $table->json('per')->nullable()->comment('Permisos');
            $table->timestamps();

            // Índices
            $table->index(['est'], 'idx_rol_est');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
