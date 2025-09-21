<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SeederLocalSimplificado::class, // Seeder simplificado para base de datos local
            // AdministradorInicialSeeder::class, // Comentado temporalmente
            // UsuarioSistemaSeeder::class, // Comentado temporalmente
            // PermisosRolesSeeder::class, // Comentado temporalmente
            // ConfiguracionInicialSeeder::class, // Comentado temporalmente
        ]);

        $this->command->info('Base de datos poblada exitosamente.');
        $this->command->info('Sistema listo para uso.');
    }
}
