<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins = [
            [
                'name' => 'Departamento de Vinculación ITSZN',
                'email' => 'vinculacion@itszn.edu.mx',
                'password' => Hash::make('vinculacion2024'),
                'tipo' => 'admin',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Coordinación de Servicio Social',
                'email' => 'servicio.social@itszn.edu.mx', 
                'password' => Hash::make('servicio2024'),
                'tipo' => 'admin',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Dirección ITSZN',
                'email' => 'direccion@itszn.edu.mx',
                'password' => Hash::make('direccion2024'),
                'tipo' => 'admin',
                'email_verified_at' => now(),
            ]
        ];

        foreach ($admins as $admin) {
            // Verificar si ya existe
            if (!Usuario::where('email', $admin['email'])->exists()) {
                Usuario::create($admin);
                $this->command->info("✅ Creado: {$admin['name']}");
            } else {
                $this->command->warn("⚠️  Ya existe: {$admin['email']}");
            }
        }

        $this->command->info('🎉 ¡Administradores del ITSZN creados exitosamente!');
        $this->command->info('🔑 Credenciales para acceder:');
        $this->command->info('   Email: vinculacion@itszn.edu.mx');
        $this->command->info('   Password: vinculacion2024');
    }
}