<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SeederLocalSimplificado extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            $this->command->info('Iniciando seeder simplificado para base de datos local...');

            // Verificar si ya existen datos
            $operadoresCount = DB::table('operadores')->count();
            if ($operadoresCount > 0) {
                $this->command->info('Ya existen datos en la base de datos. Saltando seeder.');
                return;
            }

            // Crear administrador inicial
            $this->crearAdministrador();

            // Crear roles básicos
            $this->crearRoles();

            // Crear usuarios de ejemplo
            $this->crearUsuariosEjemplo();

            $this->command->info('Seeder simplificado completado exitosamente.');

        } catch (\Exception $e) {
            $this->command->error('Error en SeederLocalSimplificado: ' . $e->getMessage());
            $this->command->warn('Continuando...');
        }
    }

    private function crearAdministrador()
    {
        $existing = DB::table('operadores')->where('rol', 'administrador')->first();
        if ($existing) {
            $this->command->info('Administrador ya existe.');
            return;
        }

        $adminId = DB::table('operadores')->insertGetId([
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
            'rol' => 'administrador',
            'estado' => 'activo',
            'acepto_terminos' => true,
            'acepto_politicas' => true,
            'correo_verificado' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('Administrador creado: esteban.41m@gmail.com / 123456');
    }

    private function crearRoles()
    {
        $roles = [
            [
                'nombre' => 'Cliente',
                'slug' => 'cliente',
                'descripcion' => 'Usuario cliente del sistema',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Operador',
                'slug' => 'operador',
                'descripcion' => 'Operador del sistema',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Administrador',
                'slug' => 'administrador',
                'descripcion' => 'Administrador del sistema',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($roles as $rolData) {
            $existing = DB::table('roles')->where('slug', $rolData['slug'])->first();
            if (!$existing) {
                DB::table('roles')->insert($rolData);
                $this->command->info('Rol creado: ' . $rolData['nombre']);
            }
        }
    }

    private function crearUsuariosEjemplo()
    {
        $usuarios = [
            [
                'Nombre' => 'Usuario Cliente',
                'Correo' => 'cliente@ejemplo.com',
                'Contrasena' => Hash::make('cliente123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'Nombre' => 'Usuario Operador',
                'Correo' => 'operador@ejemplo.com',
                'Contrasena' => Hash::make('operador123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($usuarios as $usuarioData) {
            $existing = DB::table('usuarios_sistema')->where('Correo', $usuarioData['Correo'])->first();
            if (!$existing) {
                DB::table('usuarios_sistema')->insert($usuarioData);
                $this->command->info('Usuario creado: ' . $usuarioData['Nombre']);
            }
        }

        $this->command->info('Usuarios de ejemplo creados.');
    }
}
