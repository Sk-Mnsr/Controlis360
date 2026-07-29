<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'mansour.seck@cofinacorp.com'],
            [
                'name' => 'Mansour SECK',
                'password' => Hash::make('Cofina@123'),
                'profile' => 'super_admin',
                'metier_role' => null,
                'controle_role' => null,
                'audit_role' => null,
                'job_title' => 'Super administrateur',
                'activated' => true,
                'password_change_required' => true,
            ]
        );

        $user->environments()->sync([]);
        $user->entities()->sync([]);
    }
}
