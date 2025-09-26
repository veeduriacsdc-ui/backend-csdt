<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class OperadoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            $this->command->info('🚀 Creando operadores del sistema...');

            // Verificar si ya existen usuarios
            $existentes = DB::table('usu')->count();
            if ($existentes > 0) {
                $this->command->info('⚠️ Ya existen usuarios en la base de datos.');
                return;
            }

            $this->crearOperadores();

            $this->command->info('✅ Operadores creados exitosamente.');

        } catch (\Exception $e) {
            $this->command->error('❌ Error en OperadoresSeeder: ' . $e->getMessage());
        }
    }

    private function crearOperadores()
    {
        $operadores = [
            [
                'nom' => 'Esteban',
                'ape' => 'Administrador',
                'cor' => 'esteban.41m@gmail.com',
                'con' => Hash::make('password123'),
                'tel' => '+57 300 123 4567',
                'dir' => 'Dirección del Administrador',
                'ciu' => 'Bogotá',
                'dep' => 'Cundinamarca',
                'rol' => 'adm',
                'est' => 'act',
                'cor_ver' => true,
                'cor_ver_en' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Usuario',
                'ape' => 'Cliente',
                'cor' => 'cliente@ejemplo.com',
                'con' => Hash::make('cliente123'),
                'tel' => '+57 300 000 0001',
                'dir' => 'Dirección del Cliente',
                'ciu' => 'Bogotá',
                'dep' => 'Cundinamarca',
                'rol' => 'cli',
                'est' => 'act',
                'cor_ver' => true,
                'cor_ver_en' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Usuario',
                'ape' => 'Operador',
                'cor' => 'operador@ejemplo.com',
                'con' => Hash::make('operador123'),
                'tel' => '+57 300 000 0002',
                'dir' => 'Dirección del Operador',
                'ciu' => 'Bogotá',
                'dep' => 'Cundinamarca',
                'rol' => 'ope',
                'est' => 'act',
                'cor_ver' => true,
                'cor_ver_en' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Usuario',
                'ape' => 'Admin',
                'cor' => 'admin@ejemplo.com',
                'con' => Hash::make('admin123'),
                'tel' => '+57 300 000 0003',
                'dir' => 'Dirección del Admin',
                'ciu' => 'Bogotá',
                'dep' => 'Cundinamarca',
                'rol' => 'adm',
                'est' => 'act',
                'cor_ver' => true,
                'cor_ver_en' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Usuario',
                'ape' => 'Super Admin',
                'cor' => 'superadmin@ejemplo.com',
                'con' => Hash::make('superadmin123'),
                'tel' => '+57 300 000 0004',
                'dir' => 'Dirección del Super Admin',
                'ciu' => 'Bogotá',
                'dep' => 'Cundinamarca',
                'rol' => 'adm',
                'est' => 'act',
                'cor_ver' => true,
                'cor_ver_en' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($operadores as $operador) {
            try {
                $id = DB::table('usu')->insertGetId($operador);
                $this->command->info("✅ Usuario creado: {$operador['cor']}");
            } catch (\Exception $e) {
                $this->command->error("❌ Error creando usuario {$operador['cor']}: " . $e->getMessage());
            }
        }

        $this->command->info('📋 USUARIOS CREADOS EXITOSAMENTE');
    }
}
