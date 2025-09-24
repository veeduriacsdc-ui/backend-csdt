<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('usu_id')->nullable()->constrained('usuarios')->onDelete('set null')->comment('Usuario');
            $table->string('acc', 100)->comment('Acción');
            $table->string('tab', 100)->nullable()->comment('Tabla');
            $table->string('reg_id', 50)->nullable()->comment('ID registro');
            $table->text('des')->nullable()->comment('Descripción');
            $table->json('dat_ant')->nullable()->comment('Datos anteriores');
            $table->json('dat_nue')->nullable()->comment('Datos nuevos');
            $table->string('ip', 45)->nullable()->comment('IP');
            $table->text('age_usu')->nullable()->comment('Agente usuario');
            $table->timestamp('fec')->useCurrent()->comment('Fecha');
            $table->timestamps();

            // Índices
            $table->index(['usu_id', 'fec'], 'idx_log_usu_fec');
            $table->index(['acc', 'fec'], 'idx_log_acc_fec');
            $table->index(['tab', 'reg_id'], 'idx_log_tab_reg');
            $table->index(['fec'], 'idx_log_fec');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
