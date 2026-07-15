<?php

namespace Database\Seeders;

use App\Models\ScaleLevel;
use Illuminate\Database\Seeder;

class ScaleLevelSeeder extends Seeder
{
    public function run(): void
    {
        $gravity = [
            [1, 'Inoffensif (aucun impact)', 'Ne présente aucune forme d\'impact sur l\'activité ou les objectifs'],
            [2, 'Faible impact', 'Un niveau d\'impact sur les activités ou les objectifs stratégiques ou opérationnels est perceptible mais ne gêne pas le déroulement des opérations'],
            [3, 'Impact sérieux sur process secondaires', 'Des niveaux d\'impact circonscrits à des processus (importants mais non critiques ont été notés. Il s\'agit de processus non liés directement à la qualité de service'],
            [4, 'Impact sérieux sur process métiers', 'Il s\'agit soit de processus critiques fortement impactées et de manière durable. soit de désagréments perceptibles en interne ou par le membre sur des processus liés directement à la qualité de service'],
            [5, 'Fort impact (généralisé)', 'Niveau de risque impactant de manière significative et durable l\'ensemble des activités de l\'entité ainsi que ses objectifs stratégiques et opérationnels'],
            [6, 'Crise majeure / Arrêt de l\'exploitation', 'Niveau de risque impactant de manière absolue et durable la continuité de l\'activité et autres processus critiques de l\'entité'],
        ];

        foreach ($gravity as [$level, $qualification, $description]) {
            ScaleLevel::query()->updateOrCreate(
                ['type' => 'gravity', 'level' => $level],
                [
                    'label' => $qualification,
                    'qualification' => $qualification,
                    'description' => $description,
                ]
            );
        }

        $probability = [
            [1, 'Quasi impossible', 'Ce niveau de l\'échelle correspond au risque pour lesquels aucun facteur indiquant une possible survenance n\'a été identifiée ni au niveau interne, ni externe'],
            [2, 'Eventuelle / possible', 'Le risque est plausible et, un ou des facteurs internes ou externes le rendent tout à fait réalisable'],
            [3, 'Vraisemblable', 'Plusieurs facteurs précis ont été identifiés au niveau interne ou externe favorisant la survenance du risque. Par exemple, une vague de démissions au niveau du support informatique doublée ou non d\'une augmentation exponentielle du nombre de sollicitations par les membres rendra assez probable le risque de dégradation de la qualité du support (qualité des traitements, délai de résolution des problèmes, etc.)'],
            [4, 'Probable', 'Un nombre important de facteurs potentiels ont été identifiés au niveau interne ou externe favorisant la survenance du risque. Par exemple, une vague de démissions au niveau du support informatique + une augmentation exponentielle du nombre de sollicitations par les services + dégradation des outils de traitement + démotivation + etc. rendra extrêmement probable le risque de dégradation de la qualité du support (qualité des interventions, délai de résolution...)'],
        ];

        foreach ($probability as [$level, $qualification, $description]) {
            ScaleLevel::query()->updateOrCreate(
                ['type' => 'probability', 'level' => $level],
                [
                    'label' => $qualification,
                    'qualification' => $qualification,
                    'description' => $description,
                ]
            );
        }

        ScaleLevel::query()
            ->where('type', 'probability')
            ->where('level', '>', 4)
            ->delete();

        $control = [
            [1, 'Initial', 'Dispositif de contrôle jugé "embryonnaire"', 'Inexistant'],
            [2, 'Aléatoire', 'Pratique de contrôle identifié mais présentant un caractère aléatoire et fortement conditionné par la disponibilité et la volonté de certaines individualités (prépondérance du facteur humain)', 'Informel'],
            [3, 'Opérationnel', 'Contrôle opérationnel et mis en œuvre manuellement ou de manière semi-automatique, conceptuellement satisfaisant mais ne présentant aucune garantie de mise en œuvre', 'Opérationnel'],
            [4, 'Standardisé', 'Dispositif de contrôle indépendant du facteur humain, testé périodiquement avec des résultats globalement concluants et un système d\'archivage des preuves assurant une parfaite traçabilité des tests', 'Standardisé'],
            [5, 'Optimisé', 'Contrôle paramétré de manière automatique ne nécessitant pas d\'intervention humaine. Un contrôle des paramètres est néanmoins effectué suivant une périodicité définie (contrôles de type applicatif ou "embarqué" par un SI)', 'Optimisé'],
        ];

        foreach ($control as [$level, $qualification, $description, $maturityLabel]) {
            ScaleLevel::query()->updateOrCreate(
                ['type' => 'control', 'level' => $level],
                [
                    'label' => $qualification,
                    'qualification' => $qualification,
                    'description' => $description,
                    'maturity_label' => $maturityLabel,
                ]
            );
        }
    }
}
