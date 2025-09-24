<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id('id');
            $table->string('cla', 100)->unique()->comment('Clave');
            $table->text('val')->comment('Valor');
            $table->string('des', 255)->nullable()->comment('Descripción');
            $table->string('cat', 100)->nullable()->comment('Categoría');
            $table->enum('tip', ['str', 'int', 'bool', 'json', 'dec'])->default('str')->comment('Tipo');
            $table->enum('est', ['act', 'ina'])->default('act')->comment('Estado');
            $table->timestamps();

            // Índices
            $table->index(['cla'], 'idx_con_cla');
            $table->index(['cat', 'est'], 'idx_con_cat_est');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones');
    }
};
