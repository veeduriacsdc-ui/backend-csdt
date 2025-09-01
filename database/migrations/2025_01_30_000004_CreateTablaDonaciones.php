<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('Donaciones', function (Blueprint $table) {
            $table->id('IdDonacion');
            $table->foreignId('IdCliente')->constrained('Clientes', 'IdCliente')->onDelete('cascade');
            $table->foreignId('IdPQRSFDAsociado')->nullable()->constrained('PQRSFD', 'IdPQRSFD')->onDelete('set null');
            $table->decimal('Monto', 15, 2);
            $table->enum('Moneda', ['COP', 'USD', 'EUR'])->default('COP');
            $table->enum('MetodoPago', ['TarjetaCredito', 'TarjetaDebito', 'TransferenciaBancaria', 'Efectivo', 'BilleteraDigital', 'Otros'])->default('TransferenciaBancaria');
            $table->timestamp('FechaDonacion');
            $table->enum('Estado', ['Pendiente', 'Confirmada', 'Rechazada', 'EnProceso', 'Cancelada'])->default('Pendiente');
            $table->foreignId('ValidadaPorOperador')->nullable()->constrained('Operadores', 'IdOperador')->onDelete('set null');
            $table->timestamp('FechaValidacion')->nullable();
            $table->text('Notas')->nullable();
            $table->boolean('Anonima')->default(false);
            $table->string('ComprobantePago', 200)->nullable();
            $table->enum('TipoDonacion', ['Unica', 'Recurrente', 'Mensual', 'Anual'])->default('Unica');
            $table->string('MotivoDonacion', 300)->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimización
            $table->index(['IdCliente', 'Estado']);
            $table->index(['Estado', 'FechaDonacion']);
            $table->index(['MetodoPago', 'Estado']);
            $table->index(['Moneda', 'Estado']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('Donaciones');
    }
};
