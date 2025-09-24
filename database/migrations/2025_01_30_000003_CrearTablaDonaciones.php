<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donaciones', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('usu_id')->constrained('usuarios')->onDelete('cascade')->comment('Usuario');
            $table->foreignId('vee_id')->nullable()->constrained('veedurias')->onDelete('set null')->comment('Veeduría');
            $table->foreignId('val_por')->nullable()->constrained('usuarios')->onDelete('set null')->comment('Validado por');
            $table->decimal('mon', 15, 2)->comment('Monto');
            $table->enum('moneda', ['cop', 'usd', 'eur'])->default('cop')->comment('Moneda');
            $table->enum('met_pag', ['tar', 'tra', 'efe', 'bil', 'otr'])->default('tra')->comment('Método pago');
            $table->timestamp('fec_don')->useCurrent()->comment('Fecha donación');
            $table->timestamp('fec_val')->nullable()->comment('Fecha validación');
            $table->enum('est', ['pen', 'con', 'rec', 'pro', 'can'])->default('pen')->comment('Estado');
            $table->text('not')->nullable()->comment('Notas');
            $table->boolean('anon')->default(false)->comment('Anónima');
            $table->string('com_pag', 500)->nullable()->comment('Comprobante pago');
            $table->enum('tip_don', ['uni', 'rec', 'men', 'anu'])->default('uni')->comment('Tipo donación');
            $table->string('mot', 300)->nullable()->comment('Motivo');
            $table->string('ref_pag', 100)->nullable()->comment('Referencia pago');
            $table->json('met_pag_det')->nullable()->comment('Metadatos pago');
            $table->string('cam', 100)->nullable()->comment('Campaña');
            $table->string('cod_pro', 50)->nullable()->comment('Código promocional');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['usu_id', 'est'], 'idx_don_usu_est');
            $table->index(['est', 'fec_don'], 'idx_don_est_fec');
            $table->index(['met_pag', 'est'], 'idx_don_met_est');
            $table->index(['fec_don'], 'idx_don_fec_don');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donaciones');
    }
};
