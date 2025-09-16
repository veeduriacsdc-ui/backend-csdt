<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use Illuminate\Database\Seeder;

class ConfiguracionInicialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configuraciones = [
            [
                'Clave' => 'nombre_institucion',
                'Valor' => 'CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL',
                'Descripcion' => 'Nombre oficial de la institución',
                'Categoria' => 'general',
                'Tipo' => 'texto',
                'EsEditable' => true,
                'FechaCreacion' => now(),
            ],
            [
                'Clave' => 'version_sistema',
                'Valor' => '1.0.0',
                'Descripcion' => 'Versión actual del sistema',
                'Categoria' => 'sistema',
                'Tipo' => 'texto',
                'EsEditable' => false,
                'FechaCreacion' => now(),
            ],
            [
                'Clave' => 'tiempo_sesion_horas',
                'Valor' => '8',
                'Descripcion' => 'Tiempo de duración de sesión en horas',
                'Categoria' => 'seguridad',
                'Tipo' => 'numero',
                'EsEditable' => true,
                'FechaCreacion' => now(),
            ],
            [
                'Clave' => 'max_intentos_login',
                'Valor' => '3',
                'Descripcion' => 'Máximo número de intentos de login',
                'Categoria' => 'seguridad',
                'Tipo' => 'numero',
                'EsEditable' => true,
                'FechaCreacion' => now(),
            ],
            [
                'Clave' => 'email_notificaciones',
                'Valor' => 'notificaciones@csdt.org',
                'Descripcion' => 'Email para envío de notificaciones',
                'Categoria' => 'notificaciones',
                'Tipo' => 'email',
                'EsEditable' => true,
                'FechaCreacion' => now(),
            ],
            [
                'Clave' => 'telefono_contacto',
                'Valor' => '+57 300 123 4567',
                'Descripcion' => 'Teléfono de contacto principal',
                'Categoria' => 'contacto',
                'Tipo' => 'texto',
                'EsEditable' => true,
                'FechaCreacion' => now(),
            ],
            [
                'Clave' => 'direccion_oficina',
                'Valor' => 'Calle Principal #123, Bogotá D.C.',
                'Descripcion' => 'Dirección de la oficina principal',
                'Categoria' => 'contacto',
                'Tipo' => 'texto',
                'EsEditable' => true,
                'FechaCreacion' => now(),
            ],
            [
                'Clave' => 'horario_atencion',
                'Valor' => 'Lunes a Viernes: 8:00 AM - 5:00 PM',
                'Descripcion' => 'Horario de atención al público',
                'Categoria' => 'contacto',
                'Tipo' => 'texto',
                'EsEditable' => true,
                'FechaCreacion' => now(),
            ],
        ];

        foreach ($configuraciones as $config) {
            Configuracion::create($config);
        }

        $this->command->info('Configuraciones iniciales creadas exitosamente');
    }
}
