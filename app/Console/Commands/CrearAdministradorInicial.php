<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Operador;
use App\Models\Sesion;
use App\Models\LogAuditoria;

class CrearAdministradorInicial extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:crear 
                            {--email=esteban.41@gmail.com : Correo electrónico del administrador}
                            {--password=ClaveSegura123! : Contraseña del administrador}
                            {--nombre=Esteban Administrador : Nombre completo del administrador}
                            {--telefono=+57 300 123 4567 : Teléfono del administrador}
                            {--direccion=Dirección del Administrador : Dirección del administrador}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear el usuario administrador inicial del sistema';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->info('🔐 Creando usuario administrador inicial...');
            $this->newLine();

            // Verificar si ya existe un administrador
            $administradorExistente = Operador::where('rol', 'administrador')->first();
            
            if ($administradorExistente) {
                $this->warn('⚠️  Ya existe un administrador en el sistema:');
                $this->line("   • ID: {$administradorExistente->id}");
                $this->line("   • Nombre: {$administradorExistente->nombre_completo}");
                $this->line("   • Email: {$administradorExistente->correo_electronico}");
                $this->line("   • Rol: {$administradorExistente->rol}");
                $this->newLine();
                
                if (!$this->confirm('¿Desea crear otro administrador?')) {
                    $this->info('Operación cancelada.');
                    return 0;
                }
            }

            // Obtener datos del comando
            $email = $this->option('email');
            $password = $this->option('password');
            $nombre = $this->option('nombre');
            $telefono = $this->option('telefono');
            $direccion = $this->option('direccion');

            // Validar datos
            $validator = Validator::make([
                'email' => $email,
                'password' => $password,
                'nombre' => $nombre,
                'telefono' => $telefono
            ], [
                'email' => 'required|email|unique:operadores,correo_electronico',
                'password' => 'required|min:8',
                'nombre' => 'required|string|min:3|max:255',
                'telefono' => 'required|string|max:20'
            ]);

            if ($validator->fails()) {
                $this->error('❌ Errores de validación:');
                foreach ($validator->errors()->all() as $error) {
                    $this->line("   • {$error}");
                }
                return 1;
            }

            // Confirmar creación
            $this->info('📋 Datos del administrador a crear:');
            $this->line("   • Nombre: {$nombre}");
            $this->line("   • Email: {$email}");
            $this->line("   • Teléfono: {$telefono}");
            $this->line("   • Dirección: {$direccion}");
            $this->line("   • Rol: Administrador");
            $this->line("   • Nivel de Acceso: Máximo (5)");
            $this->newLine();

            if (!$this->confirm('¿Confirma la creación del administrador con estos datos?')) {
                $this->info('Operación cancelada.');
                return 0;
            }

            // Crear el administrador
            $this->info('🔄 Creando administrador...');
            
            $administrador = Operador::create([
                'nombre_completo' => $nombre,
                'correo_electronico' => $email,
                'contrasena' => Hash::make($password),
                'telefono' => $telefono,
                'direccion' => $direccion,
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
                    'acceso_completo_sistema' => true,
                    'crear_otros_administradores' => true,
                    'gestionar_sistema' => true
                ],
                'estado' => 'activo',
                'notas_internas' => 'Usuario administrador creado vía comando Artisan',
                'fecha_registro' => now(),
                'ultima_actividad' => now()
            ]);

            // Crear sesión inicial
            $this->info('🔄 Creando sesión inicial...');
            
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
                'user_agent' => 'Comando Artisan - CrearAdministradorInicial',
                'actividad_reciente' => [
                    'ultima_accion' => 'Creación de cuenta administrador vía comando',
                    'fecha_ultima_accion' => now()->toISOString()
                ]
            ]);

            // Log de auditoría
            $this->info('🔄 Registrando en log de auditoría...');
            
            LogAuditoria::crear([
                'usuario_id' => $administrador->id,
                'tipo_usuario' => 'operador',
                'accion' => 'crear_administrador_via_comando',
                'entidad' => 'administrador',
                'entidad_id' => $administrador->id,
                'datos_anteriores' => [],
                'datos_nuevos' => [
                    'nombre_completo' => $administrador->nombre_completo,
                    'correo_electronico' => $administrador->correo_electronico,
                    'rol' => $administrador->rol,
                    'nivel_acceso' => $administrador->nivel_acceso,
                    'metodo_creacion' => 'comando_artisan'
                ],
                'estado_accion' => 'exitoso',
                'nivel_severidad' => 3,
                'categoria_accion' => 'usuarios',
                'ip_cliente' => '127.0.0.1',
                'user_agent' => 'Comando Artisan - CrearAdministradorInicial'
            ]);

            // Mostrar información de éxito
            $this->newLine();
            $this->info('✅ Usuario administrador creado exitosamente!');
            $this->newLine();
            
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['ID del Administrador', $administrador->id],
                    ['Nombre Completo', $administrador->nombre_completo],
                    ['Correo Electrónico', $administrador->correo_electronico],
                    ['Rol', $administrador->rol],
                    ['Nivel de Acceso', $administrador->nivel_acceso],
                    ['Estado', $administrador->estado],
                    ['ID de la Sesión', $sesion->id],
                    ['Fecha de Creación', $administrador->fecha_registro->format('d/m/Y H:i:s')]
                ]
            );

            $this->newLine();
            $this->warn('🔑 Credenciales de acceso:');
            $this->line("   • Email: {$email}");
            $this->line("   • Contraseña: {$password}");
            $this->newLine();
            
            $this->warn('⚠️  IMPORTANTE: Guarde estas credenciales en un lugar seguro.');
            $this->warn('   Se recomienda cambiar la contraseña después del primer inicio de sesión.');
            $this->newLine();

            $this->info('🚀 El sistema está listo para uso administrativo.');
            
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error al crear el administrador: ' . $e->getMessage());
            $this->newLine();
            
            // Log del error
            try {
                LogAuditoria::logError(
                    1, // Usuario del sistema
                    'sistema',
                    'crear_administrador_via_comando',
                    'administrador',
                    null,
                    $e->getMessage(),
                    [
                        'error_trace' => $e->getTraceAsString(),
                        'comando' => $this->getName(),
                        'opciones' => $this->options()
                    ]
                );
            } catch (\Exception $logError) {
                $this->error('❌ Error adicional al registrar en log: ' . $logError->getMessage());
            }
            
            return 1;
        }
    }
}
