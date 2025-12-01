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
                'rol_especifico' => 'vinculacion', // ✅ NUEVO CAMPO
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Coordinación de Servicio Social',
                'email' => 'servicio.social@itszn.edu.mx', 
                'password' => Hash::make('servicio2024'),
                'tipo' => 'admin',
                'rol_especifico' => 'servicio_social', // ✅ NUEVO CAMPO
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Dirección ITSZN',
                'email' => 'direccion@itszn.edu.mx',
                'password' => Hash::make('direccion2024'),
                'tipo' => 'admin',
                'rol_especifico' => 'direccion', // ✅ NUEVO CAMPO
                'email_verified_at' => now(),
            ]
        ];

        foreach ($admins as $admin) {
            // Verificar si ya existe
            if (!Usuario::where('email', $admin['email'])->exists()) {
                Usuario::create($admin);
                $this->command->info("✅ Creado: {$admin['name']} - Rol: {$admin['rol_especifico']}");
            } else {
                // ✅ ACTUALIZAR el rol si ya existe
                $usuario = Usuario::where('email', $admin['email'])->first();
                $usuario->update(['rol_especifico' => $admin['rol_especifico']]);
                $this->command->warn("⚠️  Actualizado: {$admin['email']} - Nuevo rol: {$admin['rol_especifico']}");
            }
        }

        $this->command->info('🎉 ¡Administradores del ITSZN configurados con roles específicos!');
        $this->command->info('🔑 Credenciales para Servicio Social:');
        $this->command->info('   Email: servicio.social@itszn.edu.mx');
        $this->command->info('   Password: servicio2024');
    }
}