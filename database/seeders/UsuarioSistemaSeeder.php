<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UsuarioSistema;
use App\Models\Rol;
use App\Models\Permiso;
use Illuminate\Support\Facades\Hash;

class UsuarioSistemaSeeder extends Seeder
{
    public function run(): void
    {
        // Crear roles básicos
        $roles = [
            [
                'nombre' => 'Cliente',
                'slug' => 'cliente',
                'descripcion' => 'Usuario cliente del sistema',
                'tipo' => 'Sistema',
                'nivel_acceso' => 1,
                'activo' => true,
                'editable' => false,
            ],
            [
                'nombre' => 'Operador',
                'slug' => 'operador',
                'descripcion' => 'Operador del sistema',
                'tipo' => 'Sistema',
                'nivel_acceso' => 2,
                'activo' => true,
                'editable' => false,
            ],
            [
                'nombre' => 'Administrador',
                'slug' => 'administrador',
                'descripcion' => 'Administrador del sistema',
                'tipo' => 'Sistema',
                'nivel_acceso' => 3,
                'activo' => true,
                'editable' => false,
            ],
            [
                'nombre' => 'Administrador General',
                'slug' => 'super_admin',
                'descripcion' => 'Administrador general con acceso completo',
                'tipo' => 'Sistema',
                'nivel_acceso' => 4,
                'activo' => true,
                'editable' => false,
            ],
        ];

        foreach ($roles as $rolData) {
            // Agregar campos faltantes
            $rolData['es_activo'] = true;
            $rolData['es_sistema'] = true;
            $rolData['permisos_especiales'] = null;
            
            Rol::firstOrCreate(
                ['slug' => $rolData['slug']],
                $rolData
            );
        }

        // Crear permisos básicos
        $permisos = [
            // Permisos de gestión de usuarios
            [
                'nombre' => 'Gestionar Usuarios',
                'slug' => 'gestionar_usuarios',
                'descripcion' => 'Crear, editar y eliminar usuarios',
                'modulo' => 'usuarios',
                'activo' => true,
            ],
            [
                'nombre' => 'Ver Usuarios',
                'slug' => 'ver_usuarios',
                'descripcion' => 'Ver lista de usuarios',
                'modulo' => 'usuarios',
                'activo' => true,
            ],
            [
                'nombre' => 'Cambiar Roles',
                'slug' => 'cambiar_roles',
                'descripcion' => 'Cambiar roles de usuarios',
                'modulo' => 'usuarios',
                'activo' => true,
            ],
            [
                'nombre' => 'Cambiar Estados',
                'slug' => 'cambiar_estados',
                'descripcion' => 'Activar/suspender usuarios',
                'modulo' => 'usuarios',
                'activo' => true,
            ],

            // Permisos de gestión de permisos
            [
                'nombre' => 'Gestionar Permisos',
                'slug' => 'gestionar_permisos',
                'descripcion' => 'Crear, editar y eliminar permisos',
                'modulo' => 'permisos',
                'activo' => true,
            ],
            [
                'nombre' => 'Otorgar Permisos Especiales',
                'slug' => 'otorgar_permisos_especiales',
                'descripcion' => 'Otorgar permisos especiales a usuarios',
                'modulo' => 'permisos',
                'activo' => true,
            ],

            // Permisos de configuración
            [
                'nombre' => 'Configurar Sistema',
                'slug' => 'configurar_sistema',
                'descripcion' => 'Configurar parámetros del sistema',
                'modulo' => 'configuracion',
                'activo' => true,
            ],
            [
                'nombre' => 'Gestionar Páginas',
                'slug' => 'gestionar_paginas',
                'descripcion' => 'Habilitar/deshabilitar páginas del sistema',
                'modulo' => 'configuracion',
                'activo' => true,
            ],

            // Permisos de reportes
            [
                'nombre' => 'Ver Reportes',
                'slug' => 'ver_reportes',
                'descripcion' => 'Acceder a reportes del sistema',
                'modulo' => 'reportes',
                'activo' => true,
            ],
            [
                'nombre' => 'Exportar Datos',
                'slug' => 'exportar_datos',
                'descripcion' => 'Exportar datos del sistema',
                'modulo' => 'reportes',
                'activo' => true,
            ],

            // Permisos de auditoría
            [
                'nombre' => 'Ver Logs',
                'slug' => 'ver_logs',
                'descripcion' => 'Ver logs del sistema',
                'modulo' => 'auditoria',
                'activo' => true,
            ],
            [
                'nombre' => 'Auditar Cambios',
                'slug' => 'auditar_cambios',
                'descripcion' => 'Auditar cambios en el sistema',
                'modulo' => 'auditoria',
                'activo' => true,
            ],
        ];

        foreach ($permisos as $permisoData) {
            // Agregar campos faltantes
            $permisoData['funcion'] = $permisoData['slug'];
            $permisoData['recurso'] = $permisoData['modulo'];
            $permisoData['accion'] = 'manage';
            $permisoData['es_activo'] = true;
            $permisoData['nivel_requerido'] = 1;
            $permisoData['orden'] = 1;
            
            Permiso::firstOrCreate(
                ['slug' => $permisoData['slug']],
                $permisoData
            );
        }

        // Asignar permisos a roles
        $this->asignarPermisosARoles();

        // Crear usuarios de ejemplo
        $this->crearUsuariosEjemplo();
    }

    private function asignarPermisosARoles()
    {
        // Obtener roles
        $rolCliente = Rol::where('slug', 'cliente')->first();
        $rolOperador = Rol::where('slug', 'operador')->first();
        $rolAdministrador = Rol::where('slug', 'administrador')->first();
        $rolSuperAdmin = Rol::where('slug', 'super_admin')->first();

        // Obtener permisos
        $permisos = Permiso::all()->keyBy('slug');

        // Cliente - permisos básicos
        if ($rolCliente) {
            $rolCliente->permisos()->sync([
                $permisos['ver_usuarios']->id => ['otorgado' => true],
            ]);
        }

        // Operador - permisos intermedios
        if ($rolOperador) {
            $rolOperador->permisos()->sync([
                $permisos['ver_usuarios']->id => ['otorgado' => true],
                $permisos['ver_reportes']->id => ['otorgado' => true],
            ]);
        }

        // Administrador - permisos avanzados
        if ($rolAdministrador) {
            $rolAdministrador->permisos()->sync([
                $permisos['gestionar_usuarios']->id => ['otorgado' => true],
                $permisos['ver_usuarios']->id => ['otorgado' => true],
                $permisos['cambiar_roles']->id => ['otorgado' => true],
                $permisos['cambiar_estados']->id => ['otorgado' => true],
                $permisos['gestionar_permisos']->id => ['otorgado' => true],
                $permisos['gestionar_paginas']->id => ['otorgado' => true],
                $permisos['ver_reportes']->id => ['otorgado' => true],
                $permisos['exportar_datos']->id => ['otorgado' => true],
                $permisos['ver_logs']->id => ['otorgado' => true],
            ]);
        }

        // Super Admin - todos los permisos
        if ($rolSuperAdmin) {
            $rolSuperAdmin->permisos()->sync(
                $permisos->mapWithKeys(function ($permiso) {
                    return [$permiso->id => ['otorgado' => true]];
                })->toArray()
            );
        }
    }

    private function crearUsuariosEjemplo()
    {
        // Usuario Cliente de ejemplo
        $cliente = UsuarioSistema::firstOrCreate(
            ['Correo' => 'cliente@ejemplo.com'],
            [
                'Nombre' => 'Juan Pérez',
                'Contrasena' => Hash::make('cliente123'),
                'CorreoVerificadoEn' => now(),
            ]
        );

        // Usuario Operador de ejemplo
        $operador = UsuarioSistema::firstOrCreate(
            ['Correo' => 'operador@ejemplo.com'],
            [
                'Nombre' => 'María González',
                'Contrasena' => Hash::make('operador123'),
                'CorreoVerificadoEn' => now(),
            ]
        );

        // Usuario Administrador de ejemplo
        $administrador = UsuarioSistema::firstOrCreate(
            ['Correo' => 'admin@ejemplo.com'],
            [
                'Nombre' => 'Carlos Rodríguez',
                'Contrasena' => Hash::make('admin123'),
                'CorreoVerificadoEn' => now(),
            ]
        );


        // Usuario Administrador General de ejemplo
        $superAdmin = UsuarioSistema::firstOrCreate(
            ['Correo' => 'superadmin@ejemplo.com'],
            [
                'Nombre' => 'Ana Martínez',
                'Contrasena' => Hash::make('superadmin123'),
                'CorreoVerificadoEn' => now(),
            ]
        );

        $this->command->info('Usuarios de ejemplo creados exitosamente:');
        $this->command->info('- Cliente: cliente@ejemplo.com / cliente123');
        $this->command->info('- Operador: operador@ejemplo.com / operador123');
        $this->command->info('- Administrador: admin@ejemplo.com / admin123');
        $this->command->info('- Super Admin: superadmin@ejemplo.com / superadmin123');
    }
}
