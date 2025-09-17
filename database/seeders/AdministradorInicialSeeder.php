<?php

namespace Database\Seeders;

use App\Models\Operador;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdministradorInicialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            // Verificar si ya existe un administrador
            $administradorExistente = Operador::where('rol', 'administrador')->first();

            if ($administradorExistente) {
                $this->command->info('Ya existe un administrador en el sistema.');

                return;
            }

            // Crear el usuario administrador inicial
            $administrador = Operador::create([
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
            ]);

            // Nota: La tabla sesiones no existe en las migraciones actuales
            // Se omite la creación de sesión por ahora

            // Log de auditoría - Nota: Se omite por ahora ya que la tabla puede no existir

            $this->command->info('Usuario administrador inicial creado exitosamente.');
            $this->command->info('Nombre: Esteban Administrador');
            $this->command->info('Email: esteban.41m@gmail.com');
            $this->command->info('Contraseña: 123456');
            $this->command->info('Rol: Administrador (Super)');
            $this->command->info('ID del administrador: '.$administrador->id);

        } catch (\Exception $e) {
            $this->command->error('Error al crear el administrador inicial: '.$e->getMessage());
            throw $e;
        }
    }
}
