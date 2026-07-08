<?php

namespace Database\Seeders;

use App\Enums\OperationalRiskRowStatus;
use App\Models\Entity;
use App\Models\Environment;
use App\Models\OperationalRiskRow;
use Illuminate\Database\Seeder;

class OperationalRiskSeeder extends Seeder
{
    public function run(): void
    {
        $environment = Environment::query()->where('code', 'TG')->first();

        if (! $environment) {
            return;
        }

        $it = Entity::query()
            ->where('environment_id', $environment->id)
            ->where('code', 'IT')
            ->first();

        if (! $it) {
            return;
        }

        $rows = [
            [
                'process_number' => 3,
                'process_name' => 'IT',
                'ratio' => 5,
                'sub_process_name' => 'Gestion du SI FLEXCUBE',
                'major_exceptions' => 'Indisponibilité du SIG (FLEXCUBE)',
                'correlated_risks' => 'Risque de pertes financières',
                'risk_family' => 'Risque opérationnel',
                'gravity' => 4,
                'probability' => 3,
                'status' => OperationalRiskRowStatus::Draft,
            ],
            [
                'process_number' => 3,
                'process_name' => 'IT',
                'ratio' => 5,
                'sub_process_name' => 'Gestion du SI FLEXCUBE',
                'major_exceptions' => 'Défaillances du CBS (dysfonctionnement) identifiées non corrigées',
                'correlated_risks' => 'Risque de pertes financières',
                'risk_family' => 'Risque opérationnel',
                'gravity' => 5,
                'probability' => 2,
                'status' => OperationalRiskRowStatus::Draft,
            ],
            [
                'process_number' => 3,
                'process_name' => 'IT',
                'ratio' => 12,
                'sub_process_name' => 'FLEXCUBE',
                'major_exceptions' => 'Test 1',
                'correlated_risks' => 'test',
                'risk_family' => 'Risque informatique',
                'gravity' => 2,
                'probability' => 1,
                'control_description' => 'Contrôle permanent du serveur Elaboration et mise en place d\'un PCA propre à la filiale',
                'control_exists' => true,
                'control_owner' => 'Mar',
                'control_effectiveness' => 1,
                'status' => OperationalRiskRowStatus::Assigned,
                'assigned_entity_id' => $it->id,
            ],
        ];

        $keptIds = [];

        foreach ($rows as $index => $row) {
            $risk = OperationalRiskRow::query()->updateOrCreate(
                [
                    'entity_id' => $it->id,
                    'sub_process_name' => $row['sub_process_name'],
                    'major_exceptions' => $row['major_exceptions'],
                ],
                array_merge($row, ['sort_order' => $index + 1])
            );
            $keptIds[] = $risk->id;
        }

        OperationalRiskRow::query()
            ->where('entity_id', $it->id)
            ->whereNotIn('id', $keptIds)
            ->delete();
    }
}
