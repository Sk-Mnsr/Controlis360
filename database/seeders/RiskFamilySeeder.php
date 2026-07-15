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
                'description' => "La vulnérabilité à laquelle l'institution est exposée dans sa gestion quotidienne et au regard de la qualité de son portefeuille. Il correspond aux pertes potentielles résultant de lacunes ou de défaillances des ressources humaines et matérielles.",
                'families' => [
                    ['Risque de non-conformité', "Risque de non-conformité aux procédures, à la législation et aux normes professionnelles."],
                    ['Risque juridique', 'Risque lié au droit ou aux règles juridiques.'],
                    ['Risque informatique', "Défaillance du système d'information (SI) pouvant entraîner des arrêts d'exploitation."],
                    ['Risque de pertes opérationnelles', 'Erreur ou non-conformité aux procédures entraînant une perte opérationnelle ou financière.'],
                    ['Risque de pertes de ressources humaines', 'Turnover excessif, démissions.'],
                    ['Risque de redressement fiscal', 'Redressement fiscal.'],
                ],
            ],
            [
                'number' => 2,
                'name' => 'Risques de fraude',
                'description' => "Risques liés aux divers types de fraude interne ou externe ou d'actes répréhensibles, compte tenu des activités de l'organisation.",
                'families' => [
                    ['Risque de vol', "Vol de matériel ou d'espèces."],
                    ['Fraude opérationnelle', "Manipulation des résultats, non comptabilisation de charges, ou inflation fictive ou unilatérale du PNB. Restructuration de crédit sans accord du client."],
                    ['Pertes financières', 'Fraude pouvant résulter en une perte financière ou un détournement de fonds.'],
                    ['Risque de sécurité', "Fraude informatique, intrusion SI, cambriolage d'agence, failles de sécurité."],
                    ['Blanchiment de capitaux et Financement de terrorisme', null],
                ],
            ],
            [
                'number' => 3,
                'name' => 'Risques de crédit',
                'description' => "Le risque que le débiteur ne respecte pas son obligation de rembourser un crédit. Ce risque commence dès qu'un compte client devient débiteur.",
                'families' => [
                    ['Risque de contrepartie', 'Risque que le débiteur ne puisse pas honorer ses engagements.'],
                    ['Risque de transformation', 'Risque que le débiteur ait des difficultés à vendre ses biens après avoir reçu le financement.'],
                    ['Risque de concentration', 'Financement d\'un client dans un secteur très concentré.'],
                    ['Risque de concurrence', 'Financement d\'un client dans un secteur très concurrentiel.'],
                    ['Risque de marché', 'Risque de pertes liées aux variations de valeur de marché des actions, obligations, devises ou matières premières dans le portefeuille de négociation.'],
                    ['Risque de taux', "Impact négatif sur le client en raison des variations des taux d'intérêt."],
                    ['Diversion de fonds', "Détournement de l'objet du crédit."],
                ],
            ],
            [
                'number' => 4,
                'name' => 'Risques stratégiques',
                'description' => "Risques affectant la stratégie ou les objectifs de la direction. Ils peuvent être des incertitudes ou des opportunités et correspondent généralement aux préoccupations clés du management.",
                'families' => [
                    ['Risques liés à la mission sociale', "Fournir des services financiers non adaptés au marché et donc non utilisés."],
                    ['Risques liés à la mission commerciale', "Fournir des services de manière à permettre à l'organisation de persister et de devenir autosuffisante."],
                    ['Risque de dépendance financière', 'Concentration des ressources sur un ou quelques déposants.'],
                    ["Risque d'image ou réputation", "Risque d'influencer négativement la réputation de l'institution."],
                    ['Mauvaise qualité de service', 'Toute action influençant négativement la qualité du service.'],
                    ["Conflits d'intérêts", "Lorsqu'un individu ou une organisation gère des intérêts opposés où l'un pourrait corrompre la motivation d'agir sur les autres."],
                    ['Risque humain', 'Démotivation du personnel, dégradation du climat social ou de la santé physique.'],
                ],
            ],
            [
                'number' => 5,
                'name' => 'Risques de liquidité',
                'description' => "Désigne généralement le risque de rupture de trésorerie, c'est-à-dire l'incapacité à honorer les engagements à court terme.",
                'families' => [
                    ['Risque de liquidité investisseur', 'Retrait massif par les clients épargnants.'],
                    ['Risque de liquidité entreprise', 'Absence de « matelas de sécurité » pour couvrir les dépenses (notamment imprévues) ; difficulté à trouver un financement sur le crédit ou les marchés financiers pour répondre aux besoins de trésorerie des clients.'],
                ],
            ],
            [
                'number' => 6,
                'name' => 'Risques réglementaires',
                'description' => "Risques liés à une mauvaise compréhension des exigences réglementaires ou à une exposition à des sanctions légales ou pécuniaires.",
                'families' => [
                    ['Sanctions légales et pécuniaires', null],
                    ['Risques de non-conformité réglementaire', null],
                ],
            ],
            [
                'number' => 7,
                'name' => 'Risques exogènes ou externes',
                'description' => "Risques sur lesquels l'institution n'a aucun contrôle et qui peuvent survenir dans toute situation.",
                'families' => [
                    ['Risques sociodémographiques', "Taux de criminalité, taux de mortalité, niveau d'éducation des clients."],
                    ['Risques macroéconomiques et politiques', 'Mauvaise situation économique, dévaluation, guerre civile.'],
                    ["Risques liés à l'environnement physique", 'Intempéries, manque de routes, de téléphone ou d\'électricité.'],
                    ['Dévaluation de la monnaie', null],
                    ['Dépréciation du taux de change', null],
                ],
            ],
        ];

        $keptCategoryIds = [];

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

            $keptCategoryIds[] = $category->id;
            $keptFamilyIds = [];

            foreach ($families as $familyIndex => [$name, $definition]) {
                $family = RiskFamily::query()->updateOrCreate(
                    ['risk_category_id' => $category->id, 'name' => $name],
                    [
                        'definition' => $definition,
                        'sort_order' => $familyIndex + 1,
                    ]
                );
                $keptFamilyIds[] = $family->id;
            }

            RiskFamily::query()
                ->where('risk_category_id', $category->id)
                ->whereNotIn('id', $keptFamilyIds)
                ->delete();
        }

        RiskCategory::query()
            ->whereNotIn('id', $keptCategoryIds)
            ->each(function (RiskCategory $category) {
                $category->families()->delete();
                $category->delete();
            });
    }
}
