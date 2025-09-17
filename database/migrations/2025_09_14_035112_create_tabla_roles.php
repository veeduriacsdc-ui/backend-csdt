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
        // Verificar si la tabla ya existe
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 100)->unique();
                $table->string('descripcion', 255)->nullable();
                $table->integer('nivel')->default(1);
                $table->boolean('es_activo')->default(true);
                $table->boolean('es_sistema')->default(false);
                $table->json('permisos_especiales')->nullable();
                $table->timestamps();
                
                $table->index(['nivel', 'es_activo'], 'idx_roles_nivel_activo');
            });
        } else {
            // Si la tabla existe, solo agregar columnas faltantes
            Schema::table('roles', function (Blueprint $table) {
                if (!Schema::hasColumn('roles', 'es_activo')) {
                    $table->boolean('es_activo')->default(true)->after('nivel_acceso');
                }
                if (!Schema::hasColumn('roles', 'es_sistema')) {
                    $table->boolean('es_sistema')->default(false)->after('es_activo');
                }
                if (!Schema::hasColumn('roles', 'permisos_especiales')) {
                    $table->json('permisos_especiales')->nullable()->after('es_sistema');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
