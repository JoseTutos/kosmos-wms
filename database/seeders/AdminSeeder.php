<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Contraseña fija para el administrador (puedes cambiar 'admin123' por la que prefieras)
        $passwordFija = 'admin123'; 

        $u = User::updateOrCreate(
            ['email' => 'admin@kosmos.local'],
            [
                'nombre'                => 'Administrador del Sistema',
                'password'              => Hash::make($passwordFija),
                'rol'                   => 'ADMIN_SISTEMA',
                'debe_cambiar_password' => false, // El admin ya sabe su contraseña, no necesita cambiarla
                'intentos_fallidos'     => 0,
            ]
        );

        // NOTA: No llamamos a asignarTemporal() aquí, porque eso es para 
        // los usuarios que el admin cree desde el panel, no para el admin mismo.

        $this->command->info('✅ Usuario Admin procesado correctamente.');
        $this->command->info('🔑 Email: admin@kosmos.local');
        $this->command->info('🔑 Contraseña: ' . $passwordFija);
    }
}