<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdministradorInicialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            // Verificar si ya existe un administrador usando DB directo
            $administradorExistente = DB::table('operadores')
                ->where('rol', 'administrador')
                ->first();

            if ($administradorExistente) {
                $this->command->info('Ya existe un administrador en el sistema.');
                return;
            }

            // Crear el usuario administrador inicial usando DB directo
            $administradorId = DB::table('operadores')->insertGetId([
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
                'especializacion' => 'Administración de Sistemas',
                'areas_expertise' => json_encode([
                    'gestionar_usuarios' => true,
                    'gestionar_roles' => true,
                    'gestionar_veedurias' => true,
                    'gestionar_donaciones' => true,
                    'gestionar_archivos' => true,
                    'gestionar_reportes' => true,
                    'gestionar_configuracion' => true,
                    'ver_logs_auditoria' => true,
                    'exportar_datos' => true,
                    'acceso_completo_sistema' => true,
                ]),
                'estado' => 'activo',
                'acepto_terminos' => true,
                'acepto_politicas' => true,
                'correo_verificado' => true,
                'correo_verificado_en' => now(),
                'notas' => 'Usuario administrador inicial del sistema',
                'ultimo_acceso' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('Usuario administrador inicial creado exitosamente.');
            $this->command->info('Nombre: Esteban Administrador');
            $this->command->info('Email: esteban.41m@gmail.com');
            $this->command->info('Contraseña: 123456');
            $this->command->info('Rol: Administrador (Super)');
            $this->command->info('ID del administrador: '.$administradorId);

        } catch (\Exception $e) {
            $this->command->error('Error al crear el administrador inicial: '.$e->getMessage());
            // No lanzar la excepción para que continúe con otros seeders
            $this->command->warn('Continuando con otros seeders...');
        }
    }
}
