<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsuarioSistemaSeeder extends Seeder
{
    public function run(): void
    {
        try {
            // Crear roles básicos usando DB directo
            $roles = [
                [
                    'nombre' => 'Cliente',
                    'slug' => 'cliente',
                    'descripcion' => 'Usuario cliente del sistema',
                    'tipo' => 'Sistema',
                    'nivel_acceso' => 1,
                    'activo' => true,
                    'editable' => false,
                    'es_activo' => true,
                    'es_sistema' => true,
                    'permisos_especiales' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'nombre' => 'Operador',
                    'slug' => 'operador',
                    'descripcion' => 'Operador del sistema',
                    'tipo' => 'Sistema',
                    'nivel_acceso' => 2,
                    'activo' => true,
                    'editable' => false,
                    'es_activo' => true,
                    'es_sistema' => true,
                    'permisos_especiales' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'nombre' => 'Administrador',
                    'slug' => 'administrador',
                    'descripcion' => 'Administrador del sistema',
                    'tipo' => 'Sistema',
                    'nivel_acceso' => 3,
                    'activo' => true,
                    'editable' => false,
                    'es_activo' => true,
                    'es_sistema' => true,
                    'permisos_especiales' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'nombre' => 'Administrador General',
                    'slug' => 'super_admin',
                    'descripcion' => 'Administrador general con acceso completo',
                    'tipo' => 'Sistema',
                    'nivel_acceso' => 4,
                    'activo' => true,
                    'editable' => false,
                    'es_activo' => true,
                    'es_sistema' => true,
                    'permisos_especiales' => null,
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

            // Crear permisos básicos usando DB directo
            $permisos = [
                // Permisos de gestión de usuarios
                [
                    'nombre' => 'Gestionar Usuarios',
                    'slug' => 'gestionar_usuarios',
                    'descripcion' => 'Crear, editar y eliminar usuarios',
                    'modulo' => 'usuarios',
                    'activo' => true,
                    'funcion' => 'gestionar_usuarios',
                    'recurso' => 'usuarios',
                    'accion' => 'manage',
                    'es_activo' => true,
                    'nivel_requerido' => 1,
                    'orden' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'nombre' => 'Ver Usuarios',
                    'slug' => 'ver_usuarios',
                    'descripcion' => 'Ver lista de usuarios',
                    'modulo' => 'usuarios',
                    'activo' => true,
                    'funcion' => 'ver_usuarios',
                    'recurso' => 'usuarios',
                    'accion' => 'view',
                    'es_activo' => true,
                    'nivel_requerido' => 1,
                    'orden' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'nombre' => 'Cambiar Roles',
                    'slug' => 'cambiar_roles',
                    'descripcion' => 'Cambiar roles de usuarios',
                    'modulo' => 'usuarios',
                    'activo' => true,
                    'funcion' => 'cambiar_roles',
                    'recurso' => 'usuarios',
                    'accion' => 'manage',
                    'es_activo' => true,
                    'nivel_requerido' => 2,
                    'orden' => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'nombre' => 'Cambiar Estados',
                    'slug' => 'cambiar_estados',
                    'descripcion' => 'Activar/suspender usuarios',
                    'modulo' => 'usuarios',
                    'activo' => true,
                    'funcion' => 'cambiar_estados',
                    'recurso' => 'usuarios',
                    'accion' => 'manage',
                    'es_activo' => true,
                    'nivel_requerido' => 2,
                    'orden' => 4,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            foreach ($permisos as $permisoData) {
                $existing = DB::table('permisos')->where('slug', $permisoData['slug'])->first();
                if (!$existing) {
                    DB::table('permisos')->insert($permisoData);
                    $this->command->info('Permiso creado: ' . $permisoData['nombre']);
                }
            }

            // Crear usuarios de ejemplo usando DB directo
            $usuarios = [
                [
                    'Nombre' => 'Juan Pérez',
                    'Correo' => 'cliente@ejemplo.com',
                    'Contrasena' => Hash::make('cliente123'),
                    'CorreoVerificadoEn' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'Nombre' => 'María González',
                    'Correo' => 'operador@ejemplo.com',
                    'Contrasena' => Hash::make('operador123'),
                    'CorreoVerificadoEn' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'Nombre' => 'Carlos Rodríguez',
                    'Correo' => 'admin@ejemplo.com',
                    'Contrasena' => Hash::make('admin123'),
                    'CorreoVerificadoEn' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'Nombre' => 'Ana Martínez',
                    'Correo' => 'superadmin@ejemplo.com',
                    'Contrasena' => Hash::make('superadmin123'),
                    'CorreoVerificadoEn' => now(),
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

            $this->command->info('Usuarios de ejemplo creados exitosamente:');
            $this->command->info('- Cliente: cliente@ejemplo.com / cliente123');
            $this->command->info('- Operador: operador@ejemplo.com / operador123');
            $this->command->info('- Administrador: admin@ejemplo.com / admin123');
            $this->command->info('- Super Admin: superadmin@ejemplo.com / superadmin123');

        } catch (\Exception $e) {
            $this->command->error('Error en UsuarioSistemaSeeder: ' . $e->getMessage());
            $this->command->warn('Continuando con otros seeders...');
        }
    }
}
