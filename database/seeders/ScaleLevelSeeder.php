<?php

namespace Database\Seeders;

use App\Models\ScaleLevel;
use Illuminate\Database\Seeder;

class ScaleLevelSeeder extends Seeder
{
    public function run(): void
    {
        $gravity = [
            [1, 'Inoffensif (aucun impact)', 'Inoffensif', 'Ne présente aucune forme d\'impact sur les activités ou les résultats.'],
            [2, 'Faible impact', 'Faible', 'Un niveau d\'impact sur les activités ou les résultats limité et circonscrit.'],
            [3, 'Impact sérieux sur process secondaires', 'Sérieux', 'Des niveaux d\'impact circonscris à des processus secondaires.'],
            [4, 'Impact sérieux sur process métiers', 'Sérieux', 'Processus critiques formant le cœur de métier de l\'institution.'],
            [5, 'Fort impact (généralisé)', 'Fort', 'Niveau de risque impactant de manière significative l\'institution.'],
            [6, 'Crise majeure / Arrêt de l\'exploitation', 'Critique', 'Niveau de risque impactant de manière absolue l\'institution.'],
        ];

        foreach ($gravity as [$level, $label, $qualification, $description]) {
            ScaleLevel::query()->updateOrCreate(
                ['type' => 'gravity', 'level' => $level],
                compact('label', 'qualification', 'description')
            );
        }

        $probability = [
            [1, 'Quasi impossible', 'Quasi impossible', 'Ce niveau correspond au risque le moins probable de survenir.'],
            [2, 'Eventuelle / possible', 'Possible', 'Le risque est plausible et un ou des facteurs peuvent le déclencher.'],
            [3, 'Vraisemblable', 'Vraisemblable', 'Plusieurs facteurs précis ont été identifiés comme déclencheurs potentiels.'],
            [4, 'Probable', 'Probable', 'Un nombre important de facteurs potentiels de survenance a été identifié.'],
            [5, 'Hautement probable', 'Hautement probable', 'Ce niveau correspond à l\'identification de nombreux facteurs de survenance.'],
            [6, 'Evènement certain', 'Certain', 'Aucun doute sur la survenance du risque.'],
        ];

        foreach ($probability as [$level, $label, $qualification, $description]) {
            ScaleLevel::query()->updateOrCreate(
                ['type' => 'probability', 'level' => $level],
                compact('label', 'qualification', 'description')
            );
        }

        $control = [
            [1, 'Initial', 'Initial', 'Dispositif de contrôle jugé embryonnaire.', 'Inexistant'],
            [2, 'Aléatoire', 'Aléatoire', 'Pratique de contrôle identifiée mais présentant des failles.', 'Informel'],
            [3, 'Opérationnel', 'Opérationnel', 'Contrôle opérationnel et mis en œuvre de manière satisfaisante.', 'Opérationnel'],
            [4, 'Standardisé', 'Standardisé', 'Dispositif de contrôle indépendant du facteur humain.', 'Standardisé'],
            [5, 'Optimisé', 'Optimisé', 'Contrôle paramétré de manière automatique et optimisé.', 'Optimisé'],
        ];

        foreach ($control as [$level, $label, $qualification, $description, $maturityLabel]) {
            ScaleLevel::query()->updateOrCreate(
                ['type' => 'control', 'level' => $level],
                [
                    'label' => $label,
                    'qualification' => $qualification,
                    'description' => $description,
                    'maturity_label' => $maturityLabel,
                ]
            );
        }
    }
}
