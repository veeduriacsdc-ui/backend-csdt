<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar las migraciones.
     */
    public function up(): void
    {
        Schema::create('UsuariosSistema', function (Blueprint $table) {
            $table->id('IdUsuario');
            $table->string('Nombre');
            $table->string('Correo')->unique();
            $table->timestamp('CorreoVerificadoEn')->nullable();
            $table->string('Contrasena');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('TokensRestablecimientoContrasena', function (Blueprint $table) {
            $table->string('Correo')->primary();
            $table->string('Token');
            $table->timestamp('CreadoEn')->nullable();
        });

        Schema::create('SesionesSistema', function (Blueprint $table) {
            $table->string('IdSesion')->primary();
            $table->foreignId('IdUsuario')->nullable()->index();
            $table->string('DireccionIP', 45)->nullable();
            $table->text('AgenteUsuario')->nullable();
            $table->longText('DatosSesion');
            $table->integer('UltimaActividad')->index();
        });
    }

    /**
     * Revertir las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('UsuariosSistema');
        Schema::dropIfExists('TokensRestablecimientoContrasena');
        Schema::dropIfExists('SesionesSistema');
    }
};
