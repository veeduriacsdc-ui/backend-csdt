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
            $existentes = DB::table('UsuariosSistema')->count();
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
        $usuarios = [
            [
                'Nombre' => 'Esteban Administrador',
                'Correo' => 'esteban.41m@gmail.com',
                'Contrasena' => Hash::make('123456'),
                'CorreoVerificadoEn' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'Nombre' => 'Usuario Cliente',
                'Correo' => 'cliente@ejemplo.com',
                'Contrasena' => Hash::make('cliente123'),
                'CorreoVerificadoEn' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'Nombre' => 'Usuario Operador',
                'Correo' => 'operador@ejemplo.com',
                'Contrasena' => Hash::make('operador123'),
                'CorreoVerificadoEn' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'Nombre' => 'Usuario Administrador',
                'Correo' => 'admin@ejemplo.com',
                'Contrasena' => Hash::make('admin123'),
                'CorreoVerificadoEn' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'Nombre' => 'Usuario Super Admin',
                'Correo' => 'superadmin@ejemplo.com',
                'Contrasena' => Hash::make('superadmin123'),
                'CorreoVerificadoEn' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($usuarios as $usuario) {
            try {
                $id = DB::table('UsuariosSistema')->insertGetId($usuario);
                $this->command->info("✅ Usuario del sistema creado: {$usuario['Correo']}");
            } catch (\Exception $e) {
                $this->command->error("❌ Error creando usuario {$usuario['Correo']}: " . $e->getMessage());
            }
        }

        $this->command->info('📋 USUARIOS DEL SISTEMA CREADOS:');
        $this->command->info('Admin: esteban.41m@gmail.com / 123456');
        $this->command->info('Cliente: cliente@ejemplo.com / cliente123');
        $this->command->info('Operador: operador@ejemplo.com / operador123');
        $this->command->info('Administrador: admin@ejemplo.com / admin123');
        $this->command->info('Super Admin: superadmin@ejemplo.com / superadmin123');
    }
}
