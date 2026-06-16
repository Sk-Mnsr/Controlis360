<?php

namespace Database\Seeders;

use App\Models\Entity;
use App\Models\Environment;
use App\Models\OperationalRiskRow;
use Illuminate\Database\Seeder;

class OperationalRiskSeeder extends Seeder
{
    public function run(): void
    {
        $environment = Environment::query()->where('code', 'SN')->first();

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
                'control_description' => 'Contrôle permanent du serveur',
                'control_exists' => true,
                'control_owner' => 'HEAD IT',
                'control_effectiveness' => 3,
                'residual_gravity' => 3,
                'residual_probability' => 2,
            ],
            [
                'process_number' => 3,
                'process_name' => 'IT',
                'ratio' => 5,
                'sub_process_name' => 'Gestion des comptes et habilitations',
                'major_exceptions' => 'Accès non autorisé',
                'correlated_risks' => 'Risque de fraude',
                'risk_family' => 'Risque de Fraude',
                'gravity' => 5,
                'probability' => 3,
                'control_description' => 'Veiller au strict respect de la procédure des habilitations',
                'control_exists' => true,
                'control_owner' => 'CONTROLE INTERNE',
                'control_effectiveness' => 4,
                'residual_gravity' => 3,
                'residual_probability' => 2,
            ],
            [
                'process_number' => 3,
                'process_name' => 'IT',
                'ratio' => 5,
                'sub_process_name' => 'Administration des bases de données',
                'major_exceptions' => 'Absence de sauvegarde des données',
                'correlated_risks' => 'Risque de non continuité des activités',
                'risk_family' => 'Risque opérationnel',
                'gravity' => 5,
                'probability' => 4,
                'control_description' => 'Plan de sauvegarde et restauration périodique',
                'control_exists' => true,
                'control_owner' => 'HEAD IT',
                'control_effectiveness' => 3,
                'residual_gravity' => 4,
                'residual_probability' => 3,
            ],
            [
                'process_number' => 3,
                'process_name' => 'IT',
                'ratio' => 5,
                'sub_process_name' => 'Gestion des incidents',
                'major_exceptions' => 'Délai de résolution trop long',
                'correlated_risks' => 'Risque stratégique',
                'risk_family' => 'Risque stratégique',
                'gravity' => 3,
                'probability' => 4,
                'control_description' => 'Suivi des tickets et reporting hebdomadaire',
                'control_exists' => true,
                'control_owner' => 'RSSI',
                'control_effectiveness' => 4,
                'residual_gravity' => 2,
                'residual_probability' => 3,
            ],
        ];

        $keptIds = [];

        foreach ($rows as $index => $row) {
            $risk = OperationalRiskRow::query()->updateOrCreate(
                [
                    'entity_id' => $it->id,
                    'sub_process_name' => $row['sub_process_name'],
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
