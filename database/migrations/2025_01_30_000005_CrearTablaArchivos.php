<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archivos', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('usu_id')->constrained('usuarios')->onDelete('cascade')->comment('Usuario');
            $table->foreignId('vee_id')->nullable()->constrained('veedurias')->onDelete('cascade')->comment('Veeduría');
            $table->foreignId('tar_id')->nullable()->constrained('tareas')->onDelete('cascade')->comment('Tarea');
            $table->string('nom', 255)->comment('Nombre archivo');
            $table->string('nom_ori', 255)->comment('Nombre original');
            $table->string('ruta', 500)->comment('Ruta archivo');
            $table->string('tip', 100)->comment('Tipo archivo');
            $table->bigInteger('tam')->comment('Tamaño bytes');
            $table->enum('est', ['act', 'eli', 'err'])->default('act')->comment('Estado');
            $table->text('des')->nullable()->comment('Descripción');
            $table->json('met')->nullable()->comment('Metadatos');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['usu_id'], 'idx_arc_usu');
            $table->index(['vee_id'], 'idx_arc_vee');
            $table->index(['tar_id'], 'idx_arc_tar');
            $table->index(['tip', 'est'], 'idx_arc_tip_est');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archivos');
    }
};
