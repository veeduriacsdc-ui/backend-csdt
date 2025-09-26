<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Actualizar el ENUM del campo rol para incluir adm_gen
        DB::statement("ALTER TABLE usu MODIFY COLUMN rol ENUM('cli','ope','adm','adm_gen') NOT NULL");
    }

    public function down(): void
    {
        // Revertir el ENUM del campo rol
        DB::statement("ALTER TABLE usu MODIFY COLUMN rol ENUM('cli','ope','adm') NOT NULL");
    }
};