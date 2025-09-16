<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar campos a la tabla clientes
        Schema::table('clientes', function (Blueprint $table) {
            if (!Schema::hasColumn('clientes', 'usuario')) {
                $table->string('usuario', 50)->unique()->after('correo');
            }
        });

        // Agregar campos a la tabla operadores
        Schema::table('operadores', function (Blueprint $table) {
            if (!Schema::hasColumn('operadores', 'usuario')) {
                $table->string('usuario', 50)->unique()->after('correo');
            }
            if (!Schema::hasColumn('operadores', 'contrasena')) {
                $table->string('contrasena')->after('usuario');
            }
            if (!Schema::hasColumn('operadores', 'rol')) {
                $table->string('rol', 20)->default('operador')->after('contrasena');
            }
            if (!Schema::hasColumn('operadores', 'acepto_terminos')) {
                $table->boolean('acepto_terminos')->default(false)->after('rol');
            }
            if (!Schema::hasColumn('operadores', 'acepto_politicas')) {
                $table->boolean('acepto_politicas')->default(false)->after('acepto_terminos');
            }
            if (!Schema::hasColumn('operadores', 'ultimo_acceso')) {
                $table->timestamp('ultimo_acceso')->nullable()->after('acepto_politicas');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar campos de la tabla clientes
        Schema::table('clientes', function (Blueprint $table) {
            if (Schema::hasColumn('clientes', 'usuario')) {
                $table->dropColumn('usuario');
            }
        });

        // Eliminar campos de la tabla operadores
        Schema::table('operadores', function (Blueprint $table) {
            if (Schema::hasColumn('operadores', 'usuario')) {
                $table->dropColumn('usuario');
            }
            if (Schema::hasColumn('operadores', 'contrasena')) {
                $table->dropColumn('contrasena');
            }
            if (Schema::hasColumn('operadores', 'rol')) {
                $table->dropColumn('rol');
            }
            if (Schema::hasColumn('operadores', 'acepto_terminos')) {
                $table->dropColumn('acepto_terminos');
            }
            if (Schema::hasColumn('operadores', 'acepto_politicas')) {
                $table->dropColumn('acepto_politicas');
            }
            if (Schema::hasColumn('operadores', 'ultimo_acceso')) {
                $table->dropColumn('ultimo_acceso');
            }
        });
    }
};
