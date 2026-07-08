<?php

namespace Database\Seeders;

use App\Models\Entity;
use App\Models\Environment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Comptes dédiés au test du parcours Suivi des reco :
 * création mission (audit/contrôle) → envoi → réception responsable → Action / Passivité.
 *
 * Mot de passe pour tous : Cofina@123
 */
class MissionFlowTestSeeder extends Seeder
{
    public const TEST_PASSWORD = 'Cofina@123';

    public function run(): void
    {
        $togo = Environment::query()->where('code', 'TG')->first();
        $senegal = Environment::query()->where('code', 'SN')->first();

        $creditTg = $this->entity($togo, 'CREDIT');
        $itTg = $this->entity($togo, 'IT');
        $rhTg = $this->entity($togo, 'RH');
        $creditSn = $this->entity($senegal, 'CREDIT');

        $users = [
            [
                'name' => '[TEST] Auditeur Togo',
                'email' => 'test.audit.tg@cofinacorp.com',
                'profile' => 'audit',
                'audit_role' => 'responsable_audit',
                'environment_ids' => [$togo?->id],
                'entity_ids' => [],
                'job_title' => 'Test — création mission (profil Audit, Togo)',
            ],
            [
                'name' => '[TEST] Contrôle Togo',
                'email' => 'test.controle.tg@cofinacorp.com',
                'profile' => 'controle',
                'controle_role' => 'agent_controle_interne',
                'environment_ids' => [$togo?->id],
                'entity_ids' => [],
                'job_title' => 'Test — création mission (profil Contrôle, Togo)',
            ],
            [
                'name' => '[TEST] Responsable CREDIT Togo',
                'email' => 'test.resp.credit.tg@cofinacorp.com',
                'profile' => 'metier',
                'metier_role' => 'responsable_entite',
                'environment_ids' => [$togo?->id],
                'entity_ids' => array_filter([$creditTg?->id]),
                'job_title' => 'Test — reçoit mission CREDIT (Togo), boutons Action / Passivité',
            ],
            [
                'name' => '[TEST] Responsable IT Togo',
                'email' => 'test.resp.it.tg@cofinacorp.com',
                'profile' => 'metier',
                'metier_role' => 'responsable_entite',
                'environment_ids' => [$togo?->id],
                'entity_ids' => array_filter([$itTg?->id]),
                'job_title' => 'Test — reçoit mission IT (Togo), boutons Action / Passivité',
            ],
            [
                'name' => '[TEST] Agent CREDIT Togo',
                'email' => 'test.agent.credit.tg@cofinacorp.com',
                'profile' => 'metier',
                'metier_role' => 'agent',
                'environment_ids' => [$togo?->id],
                'entity_ids' => array_filter([$creditTg?->id]),
                'job_title' => 'Test — remplit le formulaire si affecté par le responsable',
            ],
            [
                'name' => '[TEST] Auditeur Sénégal',
                'email' => 'test.audit.sn@cofinacorp.com',
                'profile' => 'audit',
                'audit_role' => 'agent_audit',
                'environment_ids' => [$senegal?->id],
                'entity_ids' => [],
                'job_title' => 'Test — création mission (profil Audit, Sénégal)',
            ],
            [
                'name' => '[TEST] Responsable CREDIT Sénégal',
                'email' => 'test.resp.credit.sn@cofinacorp.com',
                'profile' => 'metier',
                'metier_role' => 'responsable_entite',
                'environment_ids' => [$senegal?->id],
                'entity_ids' => array_filter([$creditSn?->id]),
                'job_title' => 'Test — reçoit mission CREDIT (Sénégal)',
            ],
            [
                'name' => '[TEST] Responsable RH Togo (sans mission)',
                'email' => 'test.resp.rh.tg@cofinacorp.com',
                'profile' => 'metier',
                'metier_role' => 'responsable_entite',
                'environment_ids' => [$togo?->id],
                'entity_ids' => array_filter([$rhTg?->id]),
                'job_title' => 'Test — liste vide si mission adressée à une autre entité',
            ],
        ];

        foreach ($users as $userData) {
            $this->upsertUser($userData);
        }
    }

    private function entity(?Environment $environment, string $code): ?Entity
    {
        if (! $environment) {
            return null;
        }

        return Entity::query()
            ->where('environment_id', $environment->id)
            ->where('code', $code)
            ->first();
    }

    private function upsertUser(array $userData): void
    {
        $environmentIds = array_values(array_filter($userData['environment_ids'] ?? []));
        $entityIds = array_values(array_filter($userData['entity_ids'] ?? []));

        $user = User::query()->updateOrCreate(
            ['email' => $userData['email']],
            [
                'name' => $userData['name'],
                'password' => Hash::make(self::TEST_PASSWORD),
                'profile' => $userData['profile'],
                'metier_role' => $userData['metier_role'] ?? null,
                'controle_role' => $userData['controle_role'] ?? null,
                'audit_role' => $userData['audit_role'] ?? null,
                'job_title' => $userData['job_title'],
                'activated' => true,
                'password_change_required' => false,
            ]
        );

        $user->environments()->sync($environmentIds);
        $user->entities()->sync($entityIds);
    }
}
