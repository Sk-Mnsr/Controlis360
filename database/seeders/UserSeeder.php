<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'mansour.seck@cofinacorp.com'],
            [
                'name' => 'Mansour SECK',
                'password' => Hash::make('Cofina@123'),
                'profile' => 'super_admin',
                'metier_role' => null,
                'controle_role' => null,
                'audit_role' => null,
                'gouvernance_it_role' => null,
                'job_title' => 'Super administrateur',
                'modules' => null,
                'module_profiles' => null,
                'activated' => true,
                'password_change_required' => true,
            ]
        );
    }
}
