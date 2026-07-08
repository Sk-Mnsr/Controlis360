<?php

namespace Database\Seeders;

use App\Models\Entity;
use App\Models\Environment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $togo = Environment::query()->where('code', 'TG')->first();
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
                'audit_role' => null,
                'environment_ids' => [],
                'entity_ids' => [],
                'job_title' => 'Super administrateur',
            ],
            [
                'name' => 'Aminata DIALLO',
                'email' => 'aminata.diallo@cofinacorp.com',
                'profile' => 'controle',
                'metier_role' => null,
                'controle_role' => 'responsable_controle_permanent',
                'audit_role' => null,
                'environment_ids' => [$togo?->id],
                'entity_ids' => [],
                'job_title' => 'Responsable Contrôle permanent & risques opérationnels',
            ],
            [
                'name' => 'Ibrahim KOFFI',
                'email' => 'ibrahim.koffi@cofinacorp.com',
                'profile' => 'controle',
                'metier_role' => null,
                'controle_role' => 'agent_controle_interne',
                'audit_role' => null,
                'environment_ids' => [$togo?->id],
                'entity_ids' => [],
                'job_title' => 'Agent du contrôle interne',
            ],
            [
                'name' => 'Fatou TRAORE',
                'email' => 'fatou.traore@cofinacorp.com',
                'profile' => 'audit',
                'metier_role' => null,
                'controle_role' => null,
                'audit_role' => 'responsable_audit',
                'environment_ids' => [$togo?->id],
                'entity_ids' => [],
                'job_title' => 'Responsable audit',
            ],
            [
                'name' => 'Kofi ADJEI',
                'email' => 'kofi.adjei@cofinacorp.com',
                'profile' => 'audit',
                'metier_role' => null,
                'controle_role' => null,
                'audit_role' => 'agent_audit',
                'environment_ids' => [$togo?->id],
                'entity_ids' => [],
                'job_title' => 'Agent audit',
            ],
            [
                'name' => 'Aïcha N\'GUESSAN',
                'email' => 'aicha.nguessan@cofinacorp.com',
                'profile' => 'superviseur',
                'metier_role' => null,
                'controle_role' => null,
                'audit_role' => null,
                'environment_ids' => [$togo?->id],
                'entity_ids' => array_filter([$credit?->id]),
                'job_title' => 'Superviseur',
            ],
            [
                'name' => 'Samuel KOUASSI',
                'email' => 'samuel.kouassi@cofinacorp.com',
                'profile' => 'regulateur',
                'metier_role' => null,
                'controle_role' => null,
                'audit_role' => null,
                'environment_ids' => [$togo?->id],
                'entity_ids' => [],
                'job_title' => 'Régulateur',
            ],
        ];

        foreach ($users as $userData) {
            $this->upsertUser($userData);
        }

        $this->seedMetierUsersForAllEntities();
        $this->seedAgencesTestAgent();
        $this->seedItAndProfileUsers();
    }

    private function seedItAndProfileUsers(): void
    {
        $togo = Environment::query()->where('code', 'TG')->first();
        $it = Entity::query()
            ->where('environment_id', $togo?->id)
            ->where('code', 'IT')
            ->first();

        if (! $togo || ! $it) {
            return;
        }

        $users = [
            [
                'name' => 'Amadou Mar DIOP',
                'email' => 'amadou.mardiop@cofinacorp.com',
                'profile' => 'metier',
                'metier_role' => 'responsable_entite',
                'controle_role' => null,
                'audit_role' => null,
                'environment_ids' => [$togo->id],
                'entity_ids' => [$it->id],
                'job_title' => 'Responsable IT',
            ],
            [
                'name' => 'Mansour',
                'email' => 'mansour.it.tg@cofinacorp.com',
                'profile' => 'metier',
                'metier_role' => 'agent',
                'controle_role' => null,
                'audit_role' => null,
                'environment_ids' => [$togo->id],
                'entity_ids' => [$it->id],
                'job_title' => 'Agent IT',
            ],
            [
                'name' => 'Modou',
                'email' => 'modou.it.tg@cofinacorp.com',
                'profile' => 'metier',
                'metier_role' => 'agent',
                'controle_role' => null,
                'audit_role' => null,
                'environment_ids' => [$togo->id],
                'entity_ids' => [$it->id],
                'job_title' => 'Agent IT',
            ],
            [
                'name' => 'Abdoulaye LOUM',
                'email' => 'abdoulaye.loum@cofinacorp.com',
                'profile' => 'controle',
                'metier_role' => null,
                'controle_role' => 'agent_controle_interne',
                'audit_role' => null,
                'environment_ids' => [$togo->id],
                'entity_ids' => [],
                'job_title' => 'Agent du contrôle interne',
            ],
            [
                'name' => 'Mambaye DIAGNE',
                'email' => 'mambaye.diagne@cofinacorp.com',
                'profile' => 'audit',
                'metier_role' => null,
                'controle_role' => null,
                'audit_role' => 'agent_audit',
                'environment_ids' => [$togo->id],
                'entity_ids' => [],
                'job_title' => 'Agent audit',
            ],
        ];

        foreach ($users as $userData) {
            $this->upsertUser($userData);
        }
    }

    private function seedAgencesTestAgent(): void
    {
        $togo = Environment::query()->where('code', 'TG')->first();
        $agences = Entity::query()
            ->where('environment_id', $togo?->id)
            ->where('code', 'AGENCES')
            ->first();

        if (! $agences) {
            return;
        }

        $this->upsertUser([
            'name' => 'Mansour',
            'email' => 'mansour.agences.tg@cofinacorp.com',
            'profile' => 'metier',
            'metier_role' => 'agent',
            'controle_role' => null,
            'audit_role' => null,
            'environment_ids' => [$togo->id],
            'entity_ids' => [$agences->id],
            'job_title' => 'Agent AGENCES',
        ]);
    }

    private function seedMetierUsersForAllEntities(): void
    {
        $entities = Entity::query()
            ->with('environment')
            ->where('is_active', true)
            ->orderBy('environment_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        foreach ($entities as $entity) {
            $envCode = Str::lower($entity->environment?->code ?? 'xx');
            $entitySlug = Str::lower($entity->code ?? ('entity-'.$entity->id));

            $this->upsertUser([
                'name' => 'Responsable '.$entity->name,
                'email' => "resp.{$entitySlug}.{$envCode}@cofinacorp.com",
                'profile' => 'metier',
                'metier_role' => 'responsable_entite',
                'controle_role' => null,
                'audit_role' => null,
                'environment_ids' => [$entity->environment_id],
                'entity_ids' => [$entity->id],
                'job_title' => 'Responsable entité '.$entity->name,
            ]);

            $this->upsertUser([
                'name' => 'Agent '.$entity->name,
                'email' => "agent.{$entitySlug}.{$envCode}@cofinacorp.com",
                'profile' => 'metier',
                'metier_role' => 'agent',
                'controle_role' => null,
                'audit_role' => null,
                'environment_ids' => [$entity->environment_id],
                'entity_ids' => [$entity->id],
                'job_title' => 'Agent métier '.$entity->name,
            ]);
        }
    }

    private function upsertUser(array $userData): void
    {
        $environmentIds = array_values(array_filter($userData['environment_ids'] ?? []));
        $entityIds = array_values(array_filter($userData['entity_ids'] ?? []));

        $user = User::query()->updateOrCreate(
            ['email' => $userData['email']],
            [
                'name' => $userData['name'],
                'password' => Hash::make('Cofina@123'),
                'profile' => $userData['profile'],
                'metier_role' => $userData['metier_role'],
                'controle_role' => $userData['controle_role'],
                'audit_role' => $userData['audit_role'],
                'job_title' => $userData['job_title'],
                'activated' => true,
                'password_change_required' => false,
            ]
        );

        $user->environments()->sync($environmentIds);
        $user->entities()->sync($entityIds);
    }
}
