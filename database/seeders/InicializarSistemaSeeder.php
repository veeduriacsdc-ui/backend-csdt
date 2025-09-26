<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdministradorGeneral;
use App\Models\Rol;
use App\Models\PermisoMejorado;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class InicializarSistemaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear permisos del sistema
        $this->command->info('Creando permisos del sistema...');
        PermisoMejorado::crearPermisosSistema();
        $this->command->info('Permisos creados exitosamente.');

        // Crear roles del sistema
        $this->command->info('Creando roles del sistema...');
        $this->crearRoles();
        $this->command->info('Roles creados exitosamente.');

        // Crear administrador general
        $this->command->info('Creando administrador general...');
        $this->crearAdministradorGeneral();
        $this->command->info('Administrador general creado exitosamente.');

        // Asignar permisos a roles
        $this->command->info('Asignando permisos a roles...');
        $this->asignarPermisosARoles();
        $this->command->info('Permisos asignados exitosamente.');

        $this->command->info('Sistema inicializado completamente.');
    }

    private function crearRoles()
    {
        $roles = [
            [
                'nom' => 'Administrador General',
                'des' => 'Administrador con permisos totales del sistema',
                'est' => 'act',
                'perm' => ['*'] // Todos los permisos
            ],
            [
                'nom' => 'Administrador',
                'des' => 'Administrador con permisos de gestión',
                'est' => 'act',
                'perm' => [
                    'usuarios_crear', 'usuarios_leer', 'usuarios_actualizar',
                    'veedurias_crear', 'veedurias_leer', 'veedurias_actualizar', 'veedurias_eliminar',
                    'donaciones_crear', 'donaciones_leer', 'donaciones_actualizar',
                    'tareas_crear', 'tareas_leer', 'tareas_actualizar', 'tareas_eliminar',
                    'archivos_crear', 'archivos_leer', 'archivos_actualizar', 'archivos_eliminar',
                    'estadisticas_ver'
                ]
            ],
            [
                'nom' => 'Operador',
                'des' => 'Operador con permisos de gestión de veedurías y tareas',
                'est' => 'act',
                'perm' => [
                    'veedurias_leer', 'veedurias_actualizar', 'veedurias_asignar_operador',
                    'veedurias_radicar', 'veedurias_cerrar', 'veedurias_cancelar',
                    'tareas_crear', 'tareas_leer', 'tareas_actualizar', 'tareas_asignar',
                    'tareas_completar', 'tareas_cancelar',
                    'archivos_crear', 'archivos_leer', 'archivos_descargar',
                    'estadisticas_ver'
                ]
            ],
            [
                'nom' => 'Cliente',
                'des' => 'Cliente con permisos básicos',
                'est' => 'act',
                'perm' => [
                    'veedurias_crear', 'veedurias_leer',
                    'donaciones_crear', 'donaciones_leer',
                    'archivos_crear', 'archivos_leer', 'archivos_descargar'
                ]
            ]
        ];

        foreach ($roles as $rolData) {
            Rol::create($rolData);
        }
    }

    private function crearAdministradorGeneral()
    {
        $adminGeneral = AdministradorGeneral::create([
            'nom' => 'Administrador',
            'ape' => 'General',
            'cor' => 'admin@csdt.gov.co',
            'con' => Hash::make('AdminCSDT2024!'),
            'tel' => '3001234567',
            'doc' => '12345678',
            'tip_doc' => 'cc',
            'fec_nac' => '1980-01-01',
            'dir' => 'Calle 123 #45-67',
            'ciu' => 'Bogotá',
            'dep' => 'Cundinamarca',
            'gen' => 'm',
            'rol' => AdministradorGeneral::ROL_ADMINISTRADOR_GENERAL,
            'est' => AdministradorGeneral::ESTADO_ACTIVO,
            'cor_ver' => true,
            'cor_ver_en' => now(),
        ]);

        // Asignar rol de administrador general
        $rolAdminGeneral = Rol::where('nom', 'Administrador General')->first();
        if ($rolAdminGeneral) {
            $adminGeneral->roles()->attach($rolAdminGeneral->id, [
                'asig_por' => $adminGeneral->id,
                'asig_en' => now(),
                'act' => true
            ]);
        }
    }

    private function asignarPermisosARoles()
    {
        $roles = Rol::all();
        $permisos = PermisoMejorado::all();

        foreach ($roles as $rol) {
            if ($rol->nom === 'Administrador General') {
                // El administrador general tiene todos los permisos
                $rol->permisos()->sync($permisos->pluck('id'));
            } else {
                // Asignar permisos específicos según el rol
                $permisosRol = [];
                foreach ($rol->perm as $permisoNombre) {
                    if ($permisoNombre === '*') {
                        $permisosRol = $permisos->pluck('id')->toArray();
                        break;
                    }
                    
                    $permiso = $permisos->where('nom', $permisoNombre)->first();
                    if ($permiso) {
                        $permisosRol[] = $permiso->id;
                    }
                }
                
                $rol->permisos()->sync($permisosRol);
            }
        }
    }
}
