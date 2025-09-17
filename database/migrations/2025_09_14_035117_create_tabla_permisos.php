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
        if (!Schema::hasTable('permisos')) {
            Schema::create('permisos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 100)->unique();
                $table->string('descripcion', 255)->nullable();
                $table->string('categoria', 50)->default('general');
                $table->string('recurso', 100)->nullable();
                $table->string('accion', 50)->nullable();
                $table->boolean('es_activo')->default(true);
                $table->integer('nivel_requerido')->default(1);
                $table->timestamps();
                
                $table->index(['categoria', 'recurso', 'accion'], 'idx_perm_cat_rec_acc');
                $table->index(['es_activo', 'nivel_requerido'], 'idx_perm_activo_nivel');
            });
        } else {
            // Si la tabla existe, solo agregar columnas faltantes
            Schema::table('permisos', function (Blueprint $table) {
                if (!Schema::hasColumn('permisos', 'categoria')) {
                    $table->string('categoria', 50)->default('general')->after('descripcion');
                }
                if (!Schema::hasColumn('permisos', 'recurso')) {
                    $table->string('recurso', 100)->nullable()->after('categoria');
                }
                if (!Schema::hasColumn('permisos', 'accion')) {
                    $table->string('accion', 50)->nullable()->after('recurso');
                }
                if (!Schema::hasColumn('permisos', 'es_activo')) {
                    $table->boolean('es_activo')->default(true)->after('accion');
                }
                if (!Schema::hasColumn('permisos', 'nivel_requerido')) {
                    $table->integer('nivel_requerido')->default(1)->after('es_activo');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permisos');
    }
};
