<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id('id');
            $table->string('nom', 100)->comment('Nombre');
            $table->string('ape', 100)->comment('Apellidos');
            $table->string('cor', 150)->unique()->comment('Correo');
            $table->string('con', 255)->comment('Contraseña');
            $table->string('tel', 20)->nullable()->comment('Teléfono');
            $table->string('doc', 20)->unique()->nullable()->comment('Documento');
            $table->enum('tip_doc', ['cc', 'ce', 'ti', 'pp', 'nit'])->nullable()->comment('Tipo documento');
            $table->date('fec_nac')->nullable()->comment('Fecha nacimiento');
            $table->string('dir', 200)->nullable()->comment('Dirección');
            $table->string('ciu', 100)->nullable()->comment('Ciudad');
            $table->string('dep', 100)->nullable()->comment('Departamento');
            $table->enum('gen', ['m', 'f', 'o', 'n'])->nullable()->comment('Género');
            $table->enum('rol', ['cli', 'ope', 'adm'])->comment('Rol usuario');
            $table->enum('est', ['act', 'ina', 'sus', 'pen'])->default('pen')->comment('Estado');
            $table->boolean('cor_ver')->default(false)->comment('Correo verificado');
            $table->timestamp('cor_ver_en')->nullable()->comment('Correo verificado en');
            $table->timestamp('ult_acc')->nullable()->comment('Último acceso');
            $table->text('not')->nullable()->comment('Notas');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['cor', 'est'], 'idx_usu_cor_est');
            $table->index(['doc'], 'idx_usu_doc');
            $table->index(['rol', 'est'], 'idx_usu_rol_est');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
