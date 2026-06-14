<?php

namespace Database\Seeders;

use App\Models\Entity;
use App\Models\Environment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $togo = Environment::query()->where('code', 'TOGO')->first();
        $credit = Entity::query()
            ->where('environment_id', $togo?->id)
            ->where('code', 'CREDIT')
            ->first();

        $users = [
            [
                'name' => 'Mansour SECK',
                'email' => 'mansour.seck@cofinacorp.com',
                'profile' => 'super_admin',
                'metier_role' => null,
                'controle_role' => null,
                'environment_id' => null,
                'entity_id' => null,
                'job_title' => 'Super administrateur',
            ],
            [
                'name' => 'Aminata DIALLO',
                'email' => 'aminata.diallo@cofinacorp.com',
                'profile' => 'controle',
                'metier_role' => null,
                'controle_role' => 'responsable_controle_permanent',
                'environment_id' => $togo?->id,
                'entity_id' => null,
                'job_title' => 'Responsable Contrôle permanent & risques opérationnels',
            ],
            [
                'name' => 'Ibrahim KOFFI',
                'email' => 'ibrahim.koffi@cofinacorp.com',
                'profile' => 'controle',
                'metier_role' => null,
                'controle_role' => 'agent_controle_interne',
                'environment_id' => $togo?->id,
                'entity_id' => null,
                'job_title' => 'Agent du contrôle interne',
            ],
        ];

        foreach ($users as $userData) {
            User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('Cofina@123'),
                    'profile' => $userData['profile'],
                    'metier_role' => $userData['metier_role'],
                    'controle_role' => $userData['controle_role'],
                    'environment_id' => $userData['environment_id'],
                    'entity_id' => $userData['entity_id'],
                    'job_title' => $userData['job_title'],
                    'activated' => true,
                    'password_change_required' => false,
                ]
            );
        }
    }
}
