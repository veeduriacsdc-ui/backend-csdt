<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminar tablas existentes si existen
        Schema::dropIfExists('usuarios_roles');
        Schema::dropIfExists('rol_permisos');
        Schema::dropIfExists('logs_usuarios');
        Schema::dropIfExists('logs');
        Schema::dropIfExists('configuraciones');
        Schema::dropIfExists('archivos');
        Schema::dropIfExists('tareas');
        Schema::dropIfExists('donaciones');
        Schema::dropIfExists('veedurias');
        Schema::dropIfExists('permisos');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('usuarios_sistema');
        Schema::dropIfExists('registros_pendientes');
        Schema::dropIfExists('pqrsfd');
        Schema::dropIfExists('operadores');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('administradores');
        Schema::dropIfExists('narracion_consejo_ia');
        Schema::dropIfExists('analisis_ia');

        // Crear tabla de usuarios optimizada
        Schema::create('usu', function (Blueprint $table) {
            $table->id('id');
            $table->string('nom', 100)->comment('Nombre');
            $table->string('ape', 100)->comment('Apellidos');
            $table->string('cor', 150)->unique()->comment('Correo');
            $table->string('con', 255)->comment('Contraseña');
            $table->string('tel', 20)->nullable()->comment('Teléfono');
            $table->string('doc', 20)->unique()->nullable()->comment('Documento');
            $table->enum('tip_doc', ['cc', 'ce', 'ti', 'pp', 'nit'])->nullable()->comment('Tipo documento');
            $table->date('fec_nac')->nullable()->comment('Fecha nacimiento');
            $table->string('dir', 200)->nullable()->comment('Dirección');
            $table->string('ciu', 100)->nullable()->comment('Ciudad');
            $table->string('dep', 100)->nullable()->comment('Departamento');
            $table->enum('gen', ['m', 'f', 'o', 'n'])->nullable()->comment('Género');
            $table->enum('rol', ['cli', 'ope', 'adm'])->comment('Rol usuario');
            $table->enum('est', ['act', 'ina', 'sus', 'pen'])->default('pen')->comment('Estado');
            $table->boolean('cor_ver')->default(false)->comment('Correo verificado');
            $table->timestamp('cor_ver_en')->nullable()->comment('Correo verificado en');
            $table->timestamp('ult_acc')->nullable()->comment('Último acceso');
            $table->text('not')->nullable()->comment('Notas');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['cor', 'est'], 'idx_usu_cor_est');
            $table->index(['doc'], 'idx_usu_doc');
            $table->index(['rol', 'est'], 'idx_usu_rol_est');
        });

        // Crear tabla de roles optimizada
        Schema::create('rol', function (Blueprint $table) {
            $table->id('id');
            $table->string('nom', 100)->comment('Nombre del rol');
            $table->string('des', 255)->nullable()->comment('Descripción');
            $table->enum('est', ['act', 'ina'])->default('act')->comment('Estado');
            $table->json('perm')->nullable()->comment('Permisos del rol');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['est'], 'idx_rol_est');
        });

        // Crear tabla pivot usuarios-roles
        Schema::create('usu_rol', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('usu_id')->constrained('usu')->onDelete('cascade');
            $table->foreignId('rol_id')->constrained('rol')->onDelete('cascade');
            $table->boolean('act')->default(true)->comment('Activo');
            $table->foreignId('asig_por')->constrained('usu')->onDelete('cascade');
            $table->timestamp('asig_en')->useCurrent();
            $table->text('not')->nullable()->comment('Notas');
            $table->timestamps();

            $table->unique(['usu_id', 'rol_id'], 'uk_usu_rol');
            $table->index(['usu_id', 'act'], 'idx_usu_rol_usu_act');
        });

        // Crear tabla de permisos
        Schema::create('perm', function (Blueprint $table) {
            $table->id('id');
            $table->string('nom', 100)->comment('Nombre del permiso');
            $table->string('des', 255)->nullable()->comment('Descripción');
            $table->string('mod', 50)->comment('Módulo');
            $table->enum('est', ['act', 'ina'])->default('act')->comment('Estado');
            $table->timestamps();

            $table->index(['mod', 'est'], 'idx_perm_mod_est');
        });

        // Crear tabla pivot roles-permisos
        Schema::create('rol_perm', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('rol_id')->constrained('rol')->onDelete('cascade');
            $table->foreignId('perm_id')->constrained('perm')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['rol_id', 'perm_id'], 'uk_rol_perm');
        });

        // Crear tabla de veedurías optimizada
        Schema::create('vee', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('usu_id')->constrained('usu')->onDelete('cascade');
            $table->foreignId('ope_id')->nullable()->constrained('usu')->onDelete('set null');
            $table->string('tit', 200)->comment('Título');
            $table->text('des')->comment('Descripción');
            $table->enum('tip', ['pet', 'que', 'rec', 'sug', 'fel', 'den'])->comment('Tipo');
            $table->enum('est', ['pen', 'pro', 'rad', 'cer', 'can'])->default('pen')->comment('Estado');
            $table->enum('pri', ['baj', 'med', 'alt', 'urg'])->default('med')->comment('Prioridad');
            $table->enum('cat', ['inf', 'ser', 'seg', 'edu', 'sal', 'tra', 'amb', 'otr'])->nullable()->comment('Categoría');
            $table->string('ubi', 200)->nullable()->comment('Ubicación');
            $table->decimal('pre', 15, 2)->nullable()->comment('Presupuesto');
            $table->timestamp('fec_reg')->useCurrent()->comment('Fecha registro');
            $table->timestamp('fec_rad')->nullable()->comment('Fecha radicación');
            $table->timestamp('fec_cer')->nullable()->comment('Fecha cierre');
            $table->string('num_rad', 50)->nullable()->unique()->comment('Número radicación');
            $table->text('not_ope')->nullable()->comment('Notas operador');
            $table->json('rec_ia')->nullable()->comment('Recomendaciones IA');
            $table->json('arc')->nullable()->comment('Archivos');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['usu_id', 'est'], 'idx_vee_usu_est');
            $table->index(['ope_id', 'est'], 'idx_vee_ope_est');
            $table->index(['tip', 'est'], 'idx_vee_tip_est');
            $table->index(['fec_reg'], 'idx_vee_fec_reg');
            $table->index(['num_rad'], 'idx_vee_num_rad');
        });

        // Crear tabla de donaciones optimizada
        Schema::create('don', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('usu_id')->constrained('usu')->onDelete('cascade');
            $table->decimal('mon', 15, 2)->comment('Monto');
            $table->enum('tip', ['efec', 'tran', 'cheq', 'otr'])->comment('Tipo pago');
            $table->enum('est', ['pen', 'pro', 'con', 'rej', 'can'])->default('pen')->comment('Estado');
            $table->string('ref', 100)->nullable()->comment('Referencia');
            $table->text('des')->nullable()->comment('Descripción');
            $table->timestamp('fec_don')->useCurrent()->comment('Fecha donación');
            $table->timestamp('fec_con')->nullable()->comment('Fecha confirmación');
            $table->text('not')->nullable()->comment('Notas');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['usu_id', 'est'], 'idx_don_usu_est');
            $table->index(['fec_don'], 'idx_don_fec_don');
        });

        // Crear tabla de tareas optimizada
        Schema::create('tar', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('vee_id')->nullable()->constrained('vee')->onDelete('cascade');
            $table->foreignId('asig_por')->constrained('usu')->onDelete('cascade');
            $table->foreignId('asig_a')->constrained('usu')->onDelete('cascade');
            $table->string('tit', 200)->comment('Título');
            $table->text('des')->comment('Descripción');
            $table->enum('est', ['pen', 'pro', 'com', 'can', 'sus'])->default('pen')->comment('Estado');
            $table->enum('pri', ['baj', 'med', 'alt', 'urg'])->default('med')->comment('Prioridad');
            $table->timestamp('fec_ini')->nullable()->comment('Fecha inicio');
            $table->timestamp('fec_ven')->nullable()->comment('Fecha vencimiento');
            $table->timestamp('fec_com')->nullable()->comment('Fecha completado');
            $table->text('not')->nullable()->comment('Notas');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['asig_a', 'est'], 'idx_tar_asig_est');
            $table->index(['fec_ven'], 'idx_tar_fec_ven');
        });

        // Crear tabla de archivos optimizada
        Schema::create('arc', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('usu_id')->constrained('usu')->onDelete('cascade');
            $table->foreignId('vee_id')->nullable()->constrained('vee')->onDelete('cascade');
            $table->foreignId('tar_id')->nullable()->constrained('tar')->onDelete('cascade');
            $table->string('nom_ori', 255)->comment('Nombre original');
            $table->string('nom_arc', 255)->comment('Nombre archivo');
            $table->string('rut', 500)->comment('Ruta');
            $table->string('tip', 100)->comment('Tipo MIME');
            $table->bigInteger('tam')->comment('Tamaño bytes');
            $table->enum('est', ['act', 'ina', 'eli'])->default('act')->comment('Estado');
            $table->text('des')->nullable()->comment('Descripción');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['usu_id'], 'idx_arc_usu');
            $table->index(['vee_id'], 'idx_arc_vee');
            $table->index(['tar_id'], 'idx_arc_tar');
        });

        // Crear tabla de configuraciones optimizada
        Schema::create('cfg', function (Blueprint $table) {
            $table->id('id');
            $table->string('cla', 100)->unique()->comment('Clave');
            $table->text('val')->comment('Valor');
            $table->string('des', 255)->nullable()->comment('Descripción');
            $table->string('cat', 50)->nullable()->comment('Categoría');
            $table->enum('tip', ['str', 'int', 'bool', 'json'])->default('str')->comment('Tipo');
            $table->enum('est', ['act', 'ina'])->default('act')->comment('Estado');
            $table->timestamps();

            $table->index(['cla'], 'idx_cfg_cla');
            $table->index(['cat', 'est'], 'idx_cfg_cat_est');
        });

        // Crear tabla de logs optimizada
        Schema::create('log', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('usu_id')->nullable()->constrained('usu')->onDelete('set null');
            $table->string('acc', 100)->comment('Acción');
            $table->string('tab', 50)->comment('Tabla');
            $table->unsignedBigInteger('reg_id')->nullable()->comment('ID registro');
            $table->text('des')->nullable()->comment('Descripción');
            $table->json('dat_ant')->nullable()->comment('Datos anteriores');
            $table->json('dat_nue')->nullable()->comment('Datos nuevos');
            $table->string('ip', 45)->nullable()->comment('IP');
            $table->text('age_usu')->nullable()->comment('User Agent');
            $table->timestamps();

            $table->index(['usu_id'], 'idx_log_usu');
            $table->index(['acc'], 'idx_log_acc');
            $table->index(['tab', 'reg_id'], 'idx_log_tab_reg');
            $table->index(['created_at'], 'idx_log_created');
        });

        // Crear tabla de análisis IA
        Schema::create('ai_ana', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('usu_id')->constrained('usu')->onDelete('cascade');
            $table->foreignId('vee_id')->nullable()->constrained('vee')->onDelete('cascade');
            $table->string('tip', 50)->comment('Tipo análisis');
            $table->text('tex')->comment('Texto analizado');
            $table->json('res')->comment('Resultado');
            $table->decimal('con', 5, 2)->comment('Confianza');
            $table->enum('est', ['pen', 'pro', 'com', 'err'])->default('pen')->comment('Estado');
            $table->timestamps();

            $table->index(['usu_id'], 'idx_ai_ana_usu');
            $table->index(['vee_id'], 'idx_ai_ana_vee');
            $table->index(['tip', 'est'], 'idx_ai_ana_tip_est');
        });

        // Crear tabla de narraciones IA
        Schema::create('ai_nar', function (Blueprint $table) {
            $table->id('id');
            $table->foreignId('usu_id')->constrained('usu')->onDelete('cascade');
            $table->string('cod', 50)->unique()->comment('Código');
            $table->text('tex')->comment('Texto narración');
            $table->json('dat_cli')->nullable()->comment('Datos cliente');
            $table->json('ubi')->nullable()->comment('Ubicación');
            $table->json('res_ai')->nullable()->comment('Respuestas IA');
            $table->enum('est', ['pen', 'pro', 'com', 'can'])->default('pen')->comment('Estado');
            $table->timestamps();

            $table->index(['usu_id'], 'idx_ai_nar_usu');
            $table->index(['cod'], 'idx_ai_nar_cod');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_nar');
        Schema::dropIfExists('ai_ana');
        Schema::dropIfExists('log');
        Schema::dropIfExists('cfg');
        Schema::dropIfExists('arc');
        Schema::dropIfExists('tar');
        Schema::dropIfExists('don');
        Schema::dropIfExists('vee');
        Schema::dropIfExists('rol_perm');
        Schema::dropIfExists('perm');
        Schema::dropIfExists('usu_rol');
        Schema::dropIfExists('rol');
        Schema::dropIfExists('usu');
    }
};