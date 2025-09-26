<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsuariosSistemaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            $this->command->info('🚀 Creando usuarios del sistema...');

            // Verificar si ya existen usuarios del sistema
            $existentes = DB::table('usu')->where('rol', 'adm')->count();
            if ($existentes > 0) {
                $this->command->info('⚠️ Ya existen usuarios del sistema en la base de datos.');
                return;
            }

            $this->crearUsuariosSistema();

            $this->command->info('✅ Usuarios del sistema creados exitosamente.');

        } catch (\Exception $e) {
            $this->command->error('❌ Error en UsuariosSistemaSeeder: ' . $e->getMessage());
        }
    }

    private function crearUsuariosSistema()
    {
        // Este seeder ya no es necesario ya que OperadoresSeeder crea todos los usuarios
        $this->command->info('ℹ️ Los usuarios del sistema ya fueron creados por OperadoresSeeder.');
        return;

        $this->command->info('📋 USUARIOS DEL SISTEMA CREADOS EXITOSAMENTE');
    }
}
