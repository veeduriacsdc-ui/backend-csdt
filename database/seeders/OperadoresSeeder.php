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

            // Verificar si ya existen operadores
            $existentes = DB::table('operadores')->count();
            if ($existentes > 0) {
                $this->command->info('⚠️ Ya existen operadores en la base de datos.');
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
                'nombres' => 'Esteban',
                'apellidos' => 'Administrador',
                'usuario' => 'admin',
                'correo' => 'esteban.41m@gmail.com',
                'contrasena' => Hash::make('123456'),
                'telefono' => '+57 300 123 4567',
                'direccion' => 'Dirección del Administrador',
                'ciudad' => 'Bogotá',
                'departamento' => 'Cundinamarca',
                'profesion' => 'Administrador de Sistemas',
                'especializacion' => 'Administración de Sistemas',
                'rol' => 'administrador',
                'estado' => 'activo',
                'acepto_terminos' => true,
                'acepto_politicas' => true,
                'correo_verificado' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombres' => 'Usuario',
                'apellidos' => 'Cliente',
                'usuario' => 'cliente',
                'correo' => 'cliente@ejemplo.com',
                'contrasena' => Hash::make('cliente123'),
                'telefono' => '+57 300 000 0001',
                'direccion' => 'Dirección del Cliente',
                'ciudad' => 'Bogotá',
                'departamento' => 'Cundinamarca',
                'profesion' => 'Cliente',
                'especializacion' => 'Cliente General',
                'rol' => 'operador',
                'estado' => 'activo',
                'acepto_terminos' => true,
                'acepto_politicas' => true,
                'correo_verificado' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombres' => 'Usuario',
                'apellidos' => 'Operador',
                'usuario' => 'operador',
                'correo' => 'operador@ejemplo.com',
                'contrasena' => Hash::make('operador123'),
                'telefono' => '+57 300 000 0002',
                'direccion' => 'Dirección del Operador',
                'ciudad' => 'Bogotá',
                'departamento' => 'Cundinamarca',
                'profesion' => 'Operador',
                'especializacion' => 'Operación de Sistemas',
                'rol' => 'operador',
                'estado' => 'activo',
                'acepto_terminos' => true,
                'acepto_politicas' => true,
                'correo_verificado' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombres' => 'Usuario',
                'apellidos' => 'Admin',
                'usuario' => 'admin_user',
                'correo' => 'admin@ejemplo.com',
                'contrasena' => Hash::make('admin123'),
                'telefono' => '+57 300 000 0003',
                'direccion' => 'Dirección del Admin',
                'ciudad' => 'Bogotá',
                'departamento' => 'Cundinamarca',
                'profesion' => 'Administrador',
                'especializacion' => 'Administración General',
                'rol' => 'administrador',
                'estado' => 'activo',
                'acepto_terminos' => true,
                'acepto_politicas' => true,
                'correo_verificado' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombres' => 'Usuario',
                'apellidos' => 'Super Admin',
                'usuario' => 'superadmin',
                'correo' => 'superadmin@ejemplo.com',
                'contrasena' => Hash::make('superadmin123'),
                'telefono' => '+57 300 000 0004',
                'direccion' => 'Dirección del Super Admin',
                'ciudad' => 'Bogotá',
                'departamento' => 'Cundinamarca',
                'profesion' => 'Super Administrador',
                'especializacion' => 'Super Administración',
                'rol' => 'administrador',
                'estado' => 'activo',
                'acepto_terminos' => true,
                'acepto_politicas' => true,
                'correo_verificado' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($operadores as $operador) {
            try {
                $id = DB::table('operadores')->insertGetId($operador);
                $this->command->info("✅ Operador creado: {$operador['correo']}");
            } catch (\Exception $e) {
                $this->command->error("❌ Error creando operador {$operador['correo']}: " . $e->getMessage());
            }
        }

        $this->command->info('📋 USUARIOS CREADOS:');
        $this->command->info('Admin: esteban.41m@gmail.com / 123456');
        $this->command->info('Cliente: cliente@ejemplo.com / cliente123');
        $this->command->info('Operador: operador@ejemplo.com / operador123');
        $this->command->info('Administrador: admin@ejemplo.com / admin123');
        $this->command->info('Super Admin: superadmin@ejemplo.com / superadmin123');
    }
}
