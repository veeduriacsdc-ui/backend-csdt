<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tareas', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('vee_id')->constrained('veedurias')->onDelete('cascade')->comment('Veeduría');
            $table->foreignId('asig_por')->constrained('usuarios')->onDelete('cascade')->comment('Asignado por');
            $table->foreignId('asig_a')->nullable()->constrained('usuarios')->onDelete('set null')->comment('Asignado a');
            $table->string('tit', 200)->comment('Título');
            $table->text('des')->comment('Descripción');
            $table->enum('est', ['pen', 'pro', 'com', 'can', 'sus'])->default('pen')->comment('Estado');
            $table->enum('pri', ['baj', 'med', 'alt', 'urg'])->default('med')->comment('Prioridad');
            $table->timestamp('fec_ini')->nullable()->comment('Fecha inicio');
            $table->timestamp('fec_fin')->nullable()->comment('Fecha fin');
            $table->timestamp('fec_ven')->nullable()->comment('Fecha vencimiento');
            $table->text('not')->nullable()->comment('Notas');
            $table->json('arc')->nullable()->comment('Archivos');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['vee_id', 'est'], 'idx_tar_vee_est');
            $table->index(['asig_a', 'est'], 'idx_tar_asig_est');
            $table->index(['est', 'pri'], 'idx_tar_est_pri');
            $table->index(['fec_ven'], 'idx_tar_fec_ven');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tareas');
    }
};
