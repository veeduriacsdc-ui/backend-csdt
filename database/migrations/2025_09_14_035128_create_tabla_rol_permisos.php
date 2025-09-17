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
        if (!Schema::hasTable('rol_permisos')) {
            Schema::create('rol_permisos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rol_id');
                $table->unsignedBigInteger('permiso_id');
                $table->boolean('otorgado')->default(true);
                $table->timestamp('asignado_en')->useCurrent();
                $table->unsignedBigInteger('asignado_por')->nullable();
                $table->timestamps();
                
                $table->foreign('rol_id')->references('id')->on('roles')->onDelete('cascade');
                $table->foreign('permiso_id')->references('id')->on('permisos')->onDelete('cascade');
                $table->foreign('asignado_por')->references('IdUsuario')->on('usuariossistema')->onDelete('set null');
                
                $table->unique(['rol_id', 'permiso_id']);
                $table->index(['otorgado', 'asignado_en'], 'idx_rol_perm_otorgado_asig');
            });
        } else {
            // Si la tabla existe, solo agregar columnas faltantes
            Schema::table('rol_permisos', function (Blueprint $table) {
                if (!Schema::hasColumn('rol_permisos', 'otorgado')) {
                    $table->boolean('otorgado')->default(true)->after('permiso_id');
                }
                if (!Schema::hasColumn('rol_permisos', 'asignado_en')) {
                    $table->timestamp('asignado_en')->useCurrent()->after('otorgado');
                }
                if (!Schema::hasColumn('rol_permisos', 'asignado_por')) {
                    $table->unsignedBigInteger('asignado_por')->nullable()->after('asignado_en');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rol_permisos');
    }
};
