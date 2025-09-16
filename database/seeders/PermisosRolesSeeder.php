<?php

namespace Database\Seeders;

use App\Models\Operador;
use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Database\Seeder;

class PermisosRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Crear permisos básicos
        $permisos = [
            // Páginas públicas
            ['nombre' => 'Ver Inicio', 'slug' => 'ver-inicio', 'modulo' => 'publico', 'recurso' => 'inicio', 'funcion' => 'ver'],
            ['nombre' => 'Ver PQRSFD', 'slug' => 'ver-pqrsfd', 'modulo' => 'publico', 'recurso' => 'pqrsfd', 'funcion' => 'ver'],
            ['nombre' => 'Crear PQRSFD', 'slug' => 'crear-pqrsfd', 'modulo' => 'publico', 'recurso' => 'pqrsfd', 'funcion' => 'crear'],
            ['nombre' => 'Ver Donaciones', 'slug' => 'ver-donaciones', 'modulo' => 'publico', 'recurso' => 'donaciones', 'funcion' => 'ver'],
            ['nombre' => 'Crear Donaciones', 'slug' => 'crear-donaciones', 'modulo' => 'publico', 'recurso' => 'donaciones', 'funcion' => 'crear'],
            ['nombre' => 'Ver Consejo IA', 'slug' => 'ver-consejo-ia', 'modulo' => 'publico', 'recurso' => 'consejo-ia', 'funcion' => 'ver'],
            ['nombre' => 'Ver Acción Popular', 'slug' => 'ver-accion-popular', 'modulo' => 'publico', 'recurso' => 'accion-popular', 'funcion' => 'ver'],
            ['nombre' => 'Ver Manifiesto', 'slug' => 'ver-manifiesto', 'modulo' => 'publico', 'recurso' => 'manifiesto', 'funcion' => 'ver'],
            ['nombre' => 'Ver Monitor', 'slug' => 'ver-monitor', 'modulo' => 'publico', 'recurso' => 'monitor', 'funcion' => 'ver'],

            // Páginas de cliente
            ['nombre' => 'Ver Dashboard Cliente', 'slug' => 'ver-dashboard-cliente', 'modulo' => 'cliente', 'recurso' => 'dashboard', 'funcion' => 'ver'],
            ['nombre' => 'Gestionar PQRSFD Cliente', 'slug' => 'gestionar-pqrsfd-cliente', 'modulo' => 'cliente', 'recurso' => 'pqrsfd', 'funcion' => 'gestionar'],
            ['nombre' => 'Gestionar Donaciones Cliente', 'slug' => 'gestionar-donaciones-cliente', 'modulo' => 'cliente', 'recurso' => 'donaciones', 'funcion' => 'gestionar'],
            ['nombre' => 'Gestionar Proyectos Cliente', 'slug' => 'gestionar-proyectos-cliente', 'modulo' => 'cliente', 'recurso' => 'proyectos', 'funcion' => 'gestionar'],
            ['nombre' => 'Ver Reportes Cliente', 'slug' => 'ver-reportes-cliente', 'modulo' => 'cliente', 'recurso' => 'reportes', 'funcion' => 'ver'],

            // Páginas de operador
            ['nombre' => 'Ver Dashboard Operador', 'slug' => 'ver-dashboard-operador', 'modulo' => 'operador', 'recurso' => 'dashboard', 'funcion' => 'ver'],
            ['nombre' => 'Gestionar Tareas Operador', 'slug' => 'gestionar-tareas-operador', 'modulo' => 'operador', 'recurso' => 'tareas', 'funcion' => 'gestionar'],
            ['nombre' => 'Gestionar Proyectos Operador', 'slug' => 'gestionar-proyectos-operador', 'modulo' => 'operador', 'recurso' => 'proyectos', 'funcion' => 'gestionar'],
            ['nombre' => 'Ver Reportes Operador', 'slug' => 'ver-reportes-operador', 'modulo' => 'operador', 'recurso' => 'reportes', 'funcion' => 'ver'],

            // Páginas de administrador
            ['nombre' => 'Ver Dashboard Administrador', 'slug' => 'ver-dashboard-administrador', 'modulo' => 'administrador', 'recurso' => 'dashboard', 'funcion' => 'ver'],
            ['nombre' => 'Gestionar Usuarios', 'slug' => 'gestionar-usuarios', 'modulo' => 'administrador', 'recurso' => 'usuarios', 'funcion' => 'gestionar'],
            ['nombre' => 'Gestionar Operadores', 'slug' => 'gestionar-operadores', 'modulo' => 'administrador', 'recurso' => 'operadores', 'funcion' => 'gestionar'],
            ['nombre' => 'Gestionar Permisos', 'slug' => 'gestionar-permisos', 'modulo' => 'administrador', 'recurso' => 'permisos', 'funcion' => 'gestionar'],
            ['nombre' => 'Gestionar Roles', 'slug' => 'gestionar-roles', 'modulo' => 'administrador', 'recurso' => 'roles', 'funcion' => 'gestionar'],
            ['nombre' => 'Ver Logs Sistema', 'slug' => 'ver-logs-sistema', 'modulo' => 'administrador', 'recurso' => 'logs', 'funcion' => 'ver'],
            ['nombre' => 'Gestionar Configuraciones', 'slug' => 'gestionar-configuraciones', 'modulo' => 'administrador', 'recurso' => 'configuraciones', 'funcion' => 'gestionar'],

            // Páginas compartidas
            ['nombre' => 'Ver Perfil', 'slug' => 'ver-perfil', 'modulo' => 'compartido', 'recurso' => 'perfil', 'funcion' => 'ver'],
            ['nombre' => 'Editar Perfil', 'slug' => 'editar-perfil', 'modulo' => 'compartido', 'recurso' => 'perfil', 'funcion' => 'editar'],
            ['nombre' => 'Ver Notificaciones', 'slug' => 'ver-notificaciones', 'modulo' => 'compartido', 'recurso' => 'notificaciones', 'funcion' => 'ver'],
            ['nombre' => 'Gestionar Documentos', 'slug' => 'gestionar-documentos', 'modulo' => 'compartido', 'recurso' => 'documentos', 'funcion' => 'gestionar'],
            ['nombre' => 'Enviar Mensajes', 'slug' => 'enviar-mensajes', 'modulo' => 'compartido', 'recurso' => 'mensajes', 'funcion' => 'crear'],
            ['nombre' => 'Crear Observaciones', 'slug' => 'crear-observaciones', 'modulo' => 'compartido', 'recurso' => 'observaciones', 'funcion' => 'crear'],
        ];

        foreach ($permisos as $permiso) {
            Permiso::create([
                'Nombre' => $permiso['nombre'],
                'Slug' => $permiso['slug'],
                'Descripcion' => "Permiso para {$permiso['funcion']} {$permiso['recurso']} en {$permiso['modulo']}",
                'Modulo' => $permiso['modulo'],
                'Funcion' => $permiso['funcion'],
                'Recurso' => $permiso['recurso'],
                'EsActivo' => true,
                'Orden' => 0,
            ]);
        }

        // Crear roles básicos
        $roles = [
            [
                'nombre' => 'Cliente',
                'slug' => 'cliente',
                'descripcion' => 'Usuario cliente con acceso a páginas públicas y funcionalidades básicas',
                'tipo' => 'Sistema',
                'nivel_acceso' => 1,
                'permisos' => [
                    'ver-inicio', 'ver-pqrsfd', 'crear-pqrsfd', 'ver-donaciones', 'crear-donaciones',
                    'ver-consejo-ia', 'ver-accion-popular', 'ver-manifiesto', 'ver-monitor',
                    'ver-dashboard-cliente', 'gestionar-pqrsfd-cliente', 'gestionar-donaciones-cliente',
                    'gestionar-proyectos-cliente', 'ver-reportes-cliente',
                    'ver-perfil', 'editar-perfil', 'ver-notificaciones', 'gestionar-documentos',
                ],
            ],
            [
                'nombre' => 'Operador',
                'slug' => 'operador',
                'descripcion' => 'Operador con acceso a gestión de tareas y proyectos',
                'tipo' => 'Sistema',
                'nivel_acceso' => 2,
                'permisos' => [
                    'ver-inicio', 'ver-pqrsfd', 'crear-pqrsfd', 'ver-donaciones', 'crear-donaciones',
                    'ver-consejo-ia', 'ver-accion-popular', 'ver-manifiesto', 'ver-monitor',
                    'ver-dashboard-cliente', 'gestionar-pqrsfd-cliente', 'gestionar-donaciones-cliente',
                    'gestionar-proyectos-cliente', 'ver-reportes-cliente',
                    'ver-dashboard-operador', 'gestionar-tareas-operador', 'gestionar-proyectos-operador',
                    'ver-reportes-operador',
                    'ver-perfil', 'editar-perfil', 'ver-notificaciones', 'gestionar-documentos',
                    'enviar-mensajes', 'crear-observaciones',
                ],
            ],
            [
                'nombre' => 'Administrador',
                'slug' => 'administrador',
                'descripcion' => 'Administrador con acceso completo al sistema',
                'tipo' => 'Sistema',
                'nivel_acceso' => 3,
                'permisos' => [
                    'ver-inicio', 'ver-pqrsfd', 'crear-pqrsfd', 'ver-donaciones', 'crear-donaciones',
                    'ver-consejo-ia', 'ver-accion-popular', 'ver-manifiesto', 'ver-monitor',
                    'ver-dashboard-cliente', 'gestionar-pqrsfd-cliente', 'gestionar-donaciones-cliente',
                    'gestionar-proyectos-cliente', 'ver-reportes-cliente',
                    'ver-dashboard-operador', 'gestionar-tareas-operador', 'gestionar-proyectos-operador',
                    'ver-reportes-operador',
                    'ver-dashboard-administrador', 'gestionar-usuarios', 'gestionar-operadores',
                    'gestionar-permisos', 'gestionar-roles', 'ver-logs-sistema', 'gestionar-configuraciones',
                    'ver-perfil', 'editar-perfil', 'ver-notificaciones', 'gestionar-documentos',
                    'enviar-mensajes', 'crear-observaciones',
                ],
            ],
            [
                'nombre' => 'Super Administrador',
                'slug' => 'super-administrador',
                'descripcion' => 'Super administrador con acceso total al sistema',
                'tipo' => 'Sistema',
                'nivel_acceso' => 4,
                'permisos' => [], // Todos los permisos
            ],
        ];

        foreach ($roles as $rolData) {
            $rol = Rol::create([
                'Nombre' => $rolData['nombre'],
                'Slug' => $rolData['slug'],
                'Descripcion' => $rolData['descripcion'],
                'Tipo' => $rolData['tipo'],
                'EsActivo' => true,
                'EsEditable' => false,
                'NivelAcceso' => $rolData['nivel_acceso'],
            ]);

            // Asignar permisos al rol
            if (! empty($rolData['permisos'])) {
                $permisosIds = Permiso::whereIn('Slug', $rolData['permisos'])->pluck('IdPermiso');
                $rol->permisos()->sync($permisosIds->mapWithKeys(function ($id) {
                    return [$id => ['Permitido' => true]];
                }));
            } else {
                // Super administrador tiene todos los permisos
                $todosPermisos = Permiso::all();
                $rol->permisos()->sync($todosPermisos->mapWithKeys(function ($permiso) {
                    return [$permiso->IdPermiso => ['Permitido' => true]];
                }));
            }
        }

        // Asignar rol de Super Administrador al administrador inicial
        $adminInicial = Operador::where('Correo', 'esteban.41t@gmail.com')->first();
        if ($adminInicial) {
            $rolSuperAdmin = Rol::where('Slug', 'super-administrador')->first();
            if ($rolSuperAdmin) {
                $adminInicial->asignarRol($rolSuperAdmin->IdRol, null, 'Rol asignado automáticamente al crear el sistema');
            }
        }
    }
}
