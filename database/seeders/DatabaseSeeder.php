<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = env('SEED_ADMIN_EMAIL', 'admin@cuentascyp.test');
        $adminPassword = env('SEED_ADMIN_PASSWORD', 'Admin123!');

        User::query()->firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Administrador',
                'password' => $adminPassword,
                'role' => UserRole::Admin,
                'is_active' => true,
            ],
        );

        $consultaEmail = env('SEED_CONSULTA_EMAIL', 'consulta@cuentascyp.test');

        User::query()->firstOrCreate(
            ['email' => $consultaEmail],
            [
                'name' => 'Consulta',
                'password' => env('SEED_CONSULTA_PASSWORD', 'Consulta123!'),
                'role' => UserRole::Consulta,
                'is_active' => true,
            ],
        );
    }
}
