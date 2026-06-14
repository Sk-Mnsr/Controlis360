<?php

namespace Database\Seeders;

use App\Models\RiskCategory;
use App\Models\RiskFamily;
use Illuminate\Database\Seeder;

class RiskFamilySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'number' => 1,
                'name' => 'Risques opérationnels',
                'description' => 'Le risque opérationnel est la vulnérabilité liée aux processus, personnes, systèmes ou événements externes.',
                'families' => [
                    ['Risque de non-conformité', 'Risque auquel l\'institution est exposée en cas de non-respect des règles.'],
                    ['Risque juridique', 'Risque lié au droit ou à l\'interprétation des textes législatifs et réglementaires.'],
                    ['Risque informatique', 'Défaillance du SI pouvant résulter en une perte de données ou d\'exploitation.'],
                    ['Risque de pertes opérationnelles', 'Erreur ou non-respect des procédures pouvant entraîner des pertes.'],
                    ['Risque de pertes de ressources humaines', 'Turnover excessif, démission non maîtrisée.'],
                    ['Risque de redressement fiscal', 'Redressement fiscal suite à un contrôle administratif.'],
                ],
            ],
            [
                'number' => 2,
                'name' => 'Risques de fraude',
                'description' => 'Risques liés aux divers types de fraude interne ou externe.',
                'families' => [
                    ['Risque de vol', 'Vol de matériel ou d\'espèces.'],
                    ['Fraude opérationnelle', 'Manipulation des résultats, non comptabilisation d\'opérations.'],
                    ['Pertes financières', 'Fraude pouvant résulter en une perte financière directe.'],
                    ['Risque de sécurité', 'Fraude informatique, intrusion SI, cambriolage.'],
                    ['Blanchiment de capitaux et Financement du terrorisme', 'Non-respect des obligations LBC/FT.'],
                ],
            ],
            [
                'number' => 3,
                'name' => 'Risques de crédit',
                'description' => 'Le risque de crédit est le risque que le débiteur ne puisse pas honorer ses engagements.',
                'families' => [
                    ['Risque de contrepartie', 'Risque que le débiteur ne puisse pas payer sa dette.'],
                    ['Risque de transformation', 'Risque que le débiteur ait des difficultés à rembourser à échéance.'],
                    ['Risque de concentration', 'Financement d\'un client dans un secteur ou zone concentrée.'],
                    ['Risque de concurrence', 'Exposition à la concurrence affectant la solvabilité du client.'],
                    ['Risque de marché', 'Financement d\'un client exposé à un risque de marché.'],
                    ['Risque de taux', 'Impact négatif des variations de taux sur le client.'],
                    ['Diversion de fonds', 'Détournement de l\'objet du crédit.'],
                ],
            ],
            [
                'number' => 4,
                'name' => 'Risques stratégiques',
                'description' => 'Les risques stratégiques affectent la mission et la pérennité de l\'institution.',
                'families' => [
                    ['Risques liés à la mission sociale', 'Risque de ne pas atteindre les objectifs sociaux.'],
                    ['Risques liés à la mission commerciale', 'Risque de ne pas atteindre les objectifs commerciaux.'],
                    ['Risque de dépendance financière', 'Concentration des ressources sur un ou plusieurs bailleurs.'],
                    ['Risque d\'image ou réputation', 'Impact négatif sur la réputation de l\'institution.'],
                    ['Mauvaise qualité de service', 'Action influençant négativement la satisfaction client.'],
                    ['Conflits d\'intérêts', 'Conflit entre intérêts personnels et institutionnels.'],
                    ['Risque humain', 'Démotivation du personnel, dégradation du climat social.'],
                ],
            ],
            [
                'number' => 5,
                'name' => 'Risques de liquidité',
                'description' => 'Le risque de liquidité est l\'incapacité à honorer les engagements à court terme.',
                'families' => [
                    ['Risque de liquidité investisseur', 'Difficulté à faire face aux demandes de retrait des investisseurs.'],
                ],
            ],
        ];

        foreach ($categories as $index => $categoryData) {
            $families = $categoryData['families'];
            unset($categoryData['families']);

            $category = RiskCategory::query()->updateOrCreate(
                ['number' => $categoryData['number']],
                [
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                    'sort_order' => $index + 1,
                ]
            );

            foreach ($families as $familyIndex => [$name, $definition]) {
                RiskFamily::query()->updateOrCreate(
                    ['risk_category_id' => $category->id, 'name' => $name],
                    [
                        'definition' => $definition,
                        'sort_order' => $familyIndex + 1,
                    ]
                );
            }
        }
    }
}
