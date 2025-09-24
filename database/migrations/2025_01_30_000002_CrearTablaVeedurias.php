<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('veedurias', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('usu_id')->constrained('usuarios')->onDelete('cascade')->comment('Usuario');
            $table->foreignId('ope_id')->nullable()->constrained('usuarios')->onDelete('set null')->comment('Operador');
            $table->string('tit', 200)->comment('Título');
            $table->text('des')->comment('Descripción');
            $table->enum('tip', ['pet', 'que', 'rec', 'sug', 'fel', 'den'])->comment('Tipo');
            $table->enum('est', ['pen', 'pro', 'rad', 'cer', 'can'])->default('pen')->comment('Estado');
            $table->enum('pri', ['baj', 'med', 'alt', 'urg'])->default('med')->comment('Prioridad');
            $table->enum('cat', ['inf', 'ser', 'seg', 'edu', 'sal', 'tra', 'amb', 'otr'])->nullable()->comment('Categoría');
            $table->string('ubi', 200)->nullable()->comment('Ubicación');
            $table->decimal('pre', 15, 2)->nullable()->comment('Presupuesto');
            $table->timestamp('fec_reg')->useCurrent()->comment('Fecha registro');
            $table->timestamp('fec_rad')->nullable()->comment('Fecha radicación');
            $table->timestamp('fec_cer')->nullable()->comment('Fecha cierre');
            $table->string('num_rad', 50)->nullable()->unique()->comment('Número radicación');
            $table->text('not_ope')->nullable()->comment('Notas operador');
            $table->json('rec_ia')->nullable()->comment('Recomendaciones IA');
            $table->json('arc')->nullable()->comment('Archivos');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['usu_id', 'est'], 'idx_vee_usu_est');
            $table->index(['ope_id', 'est'], 'idx_vee_ope_est');
            $table->index(['tip', 'est'], 'idx_vee_tip_est');
            $table->index(['fec_reg'], 'idx_vee_fec_reg');
            $table->index(['num_rad'], 'idx_vee_num_rad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('veedurias');
    }
};
