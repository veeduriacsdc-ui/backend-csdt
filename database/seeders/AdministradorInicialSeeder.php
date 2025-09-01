<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Operador;
use App\Models\Sesion;
use App\Models\LogAuditoria;

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
                'nombre_completo' => 'Esteban Administrador',
                'correo_electronico' => 'esteban.41@gmail.com',
                'contrasena' => Hash::make('ClaveSegura123!'),
                'telefono' => '+57 300 123 4567',
                'direccion' => 'Dirección del Administrador',
                'rol' => 'administrador',
                'nivel_acceso' => 5, // Máximo nivel de acceso
                'permisos' => [
                    'gestionar_usuarios' => true,
                    'gestionar_roles' => true,
                    'gestionar_veedurias' => true,
                    'gestionar_donaciones' => true,
                    'gestionar_archivos' => true,
                    'gestionar_reportes' => true,
                    'gestionar_configuracion' => true,
                    'ver_logs_auditoria' => true,
                    'exportar_datos' => true,
                    'acceso_completo_sistema' => true
                ],
                'estado' => 'activo',
                'notas_internas' => 'Usuario administrador inicial del sistema',
                'fecha_registro' => now(),
                'ultima_actividad' => now()
            ]);

            // Crear sesión inicial para el administrador
            $sesion = Sesion::create([
                'usuario_id' => $administrador->id,
                'tipo_usuario' => 'operador',
                'rol' => 'administrador',
                'nivel_acceso' => 5,
                'permisos' => $administrador->permisos,
                'estado_sesion' => 'activa',
                'fecha_inicio' => now(),
                'fecha_expiracion' => now()->addDays(30),
                'ip_cliente' => '127.0.0.1',
                'user_agent' => 'Seeder - Sistema',
                'actividad_reciente' => [
                    'ultima_accion' => 'Creación de cuenta administrador',
                    'fecha_ultima_accion' => now()->toISOString()
                ]
            ]);

            // Log de auditoría
            LogAuditoria::crear([
                'usuario_id' => $administrador->id,
                'tipo_usuario' => 'operador',
                'accion' => 'crear_administrador_inicial',
                'entidad' => 'administrador',
                'entidad_id' => $administrador->id,
                'datos_anteriores' => [],
                'datos_nuevos' => [
                    'nombre_completo' => $administrador->nombre_completo,
                    'correo_electronico' => $administrador->correo_electronico,
                    'rol' => $administrador->rol,
                    'nivel_acceso' => $administrador->nivel_acceso
                ],
                'estado_accion' => 'exitoso',
                'nivel_severidad' => 3,
                'categoria_accion' => 'usuarios',
                'ip_cliente' => '127.0.0.1',
                'user_agent' => 'Seeder - Sistema'
            ]);

            $this->command->info('Usuario administrador inicial creado exitosamente.');
            $this->command->info('Email: esteban.41@gmail.com');
            $this->command->info('Contraseña: ClaveSegura123!');
            $this->command->info('ID del administrador: ' . $administrador->id);
            $this->command->info('ID de la sesión: ' . $sesion->id);

        } catch (\Exception $e) {
            $this->command->error('Error al crear el administrador inicial: ' . $e->getMessage());
            
            // Log del error
            LogAuditoria::logError(
                1, // Usuario del sistema
                'sistema',
                'crear_administrador_inicial',
                'administrador',
                null,
                $e->getMessage(),
                ['error_trace' => $e->getTraceAsString()]
            );
            
            throw $e;
        }
    }
}
