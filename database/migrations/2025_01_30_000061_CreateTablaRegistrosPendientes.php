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
        Schema::create('registros_pendientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('email')->unique();
            $table->string('telefono')->nullable();
            $table->string('documento_identidad');
            $table->string('tipo_documento')->default('CC');
            $table->string('rol_solicitado'); // cliente, operador, administrador
            $table->text('motivacion')->nullable();
            $table->text('experiencia')->nullable();
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->text('observaciones_admin')->nullable();
            $table->unsignedBigInteger('aprobado_por')->nullable();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->string('token_verificacion')->unique();
            $table->timestamp('email_verificado_at')->nullable();
            $table->timestamps();

            // Índices optimizados con nombres descriptivos
            $table->index(['estado', 'rol_solicitado'], 'idx_reg_pend_estado_rol');
            $table->index(['email'], 'idx_reg_pend_email');
            $table->index(['token_verificacion'], 'idx_reg_pend_token');
            $table->index(['fecha_aprobacion'], 'idx_reg_pend_fecha_aprob');

            // Clave foránea
            $table->foreign('aprobado_por')->references('id')->on('usuarios')->onDelete('set null');
        });
    }

    /**
     * Revertir las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('registros_pendientes');
    }
};
