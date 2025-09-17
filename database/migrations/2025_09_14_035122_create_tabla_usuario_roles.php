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
        if (!Schema::hasTable('usuario_roles')) {
            Schema::create('usuario_roles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('usuario_id');
                $table->unsignedBigInteger('rol_id');
                $table->timestamp('asignado_en')->useCurrent();
                $table->unsignedBigInteger('asignado_por')->nullable();
                $table->timestamp('revocado_en')->nullable();
                $table->unsignedBigInteger('revocado_por')->nullable();
                $table->text('motivo_revocacion')->nullable();
                $table->timestamps();
                
                $table->foreign('usuario_id')->references('IdUsuario')->on('usuariossistema')->onDelete('cascade');
                $table->foreign('rol_id')->references('id')->on('roles')->onDelete('cascade');
                $table->foreign('asignado_por')->references('IdUsuario')->on('usuariossistema')->onDelete('set null');
                $table->foreign('revocado_por')->references('IdUsuario')->on('usuariossistema')->onDelete('set null');
                
                $table->unique(['usuario_id', 'rol_id']);
                $table->index(['asignado_en', 'revocado_en'], 'idx_usr_rol_asig_revoc');
            });
        } else {
            // Si la tabla existe, solo agregar columnas faltantes
            Schema::table('usuario_roles', function (Blueprint $table) {
                if (!Schema::hasColumn('usuario_roles', 'asignado_en')) {
                    $table->timestamp('asignado_en')->useCurrent()->after('rol_id');
                }
                if (!Schema::hasColumn('usuario_roles', 'asignado_por')) {
                    $table->unsignedBigInteger('asignado_por')->nullable()->after('asignado_en');
                }
                if (!Schema::hasColumn('usuario_roles', 'revocado_en')) {
                    $table->timestamp('revocado_en')->nullable()->after('asignado_por');
                }
                if (!Schema::hasColumn('usuario_roles', 'revocado_por')) {
                    $table->unsignedBigInteger('revocado_por')->nullable()->after('revocado_en');
                }
                if (!Schema::hasColumn('usuario_roles', 'motivo_revocacion')) {
                    $table->text('motivo_revocacion')->nullable()->after('revocado_por');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario_roles');
    }
};
