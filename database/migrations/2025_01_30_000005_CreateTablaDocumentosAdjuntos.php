<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('DocumentosAdjuntos', function (Blueprint $table) {
            $table->id('IdDocumento');
            $table->foreignId('IdPQRSFD')->constrained('PQRSFD', 'IdPQRSFD')->onDelete('cascade');
            $table->foreignId('IdCliente')->nullable()->constrained('Clientes', 'IdCliente')->onDelete('cascade');
            $table->foreignId('IdOperador')->nullable()->constrained('Operadores', 'IdOperador')->onDelete('set null');
            $table->string('NombreArchivo', 255);
            $table->string('RutaArchivo', 500);
            $table->string('NombreOriginal', 255)->nullable();
            $table->enum('TipoDocumento', [
                'Identificacion', 'Prueba', 'SoporteIA', 'Evidencia', 'Informe', 'Fotografia', 'Video', 'Audio', 'Otros'
            ])->default('Prueba');
            $table->string('ExtensionArchivo', 10)->nullable();
            $table->bigInteger('TamanoArchivo')->nullable(); // En bytes
            $table->string('MimeType', 100)->nullable();
            $table->json('AnalisisIA')->nullable();
            $table->enum('Estado', ['Activo', 'Eliminado', 'EnRevision'])->default('Activo');
            $table->timestamp('FechaCarga')->useCurrent();
            $table->timestamp('FechaEliminacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimización
            $table->index(['IdPQRSFD', 'TipoDocumento']);
            $table->index(['IdCliente', 'TipoDocumento']);
            $table->index(['TipoDocumento', 'Estado']);
            $table->index(['FechaCarga']);
            $table->index(['ExtensionArchivo', 'Estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('DocumentosAdjuntos');
    }
};
