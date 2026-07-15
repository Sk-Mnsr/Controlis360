<?php

namespace Database\Seeders;

use App\Models\RiskClassification;
use Illuminate\Database\Seeder;

class RiskClassificationSeeder extends Seeder
{
    public function run(): void
    {
        $classifications = [
            [
                'name' => 'Non significatif',
                'code' => 'non_significatif',
                'min_score' => 1,
                'max_score' => 6,
                'color' => '#2e7d32',
                'sort_order' => 1,
                'description' => 'Risque jugé insignifiant par rapport aux process métier',
            ],
            [
                'name' => 'Faible',
                'code' => 'faible',
                'min_score' => 2,
                'max_score' => 8,
                'color' => '#81c784',
                'sort_order' => 2,
                'description' => 'Risque jugé faible (Des possibilités d\'améliorations existent, mais les faiblesses et défaillances constatées sont insignifiants)',
            ],
            [
                'name' => 'Modéré',
                'code' => 'modere',
                'min_score' => 5,
                'max_score' => 12,
                'color' => '#fff176',
                'sort_order' => 3,
                'description' => 'Risque jugé modéré (un certain nombre de constatations révèlent des faiblesses dans les contrôles. Ces constatations requièrent l\'attention du management)',
            ],
            [
                'name' => 'Élevé',
                'code' => 'eleve',
                'min_score' => 10,
                'max_score' => 18,
                'color' => '#ffb74d',
                'sort_order' => 4,
                'description' => 'Le risque est jugé élevé (des manquements et/ou défaillances sérieux existent qui pourraient mettre en danger le fait d\'atteindre les objectifs)',
            ],
            [
                'name' => 'Très élevé',
                'code' => 'tres_eleve',
                'min_score' => 17,
                'max_score' => 30,
                'color' => '#e65100',
                'sort_order' => 5,
                'description' => 'Le risque est jugé très élevé (des fraudes sérieuses existent telles que les objectifs initiaux ne pourront vraisemblablement pas être atteints)',
            ],
            [
                'name' => 'Critique',
                'code' => 'critique',
                'min_score' => 25,
                'max_score' => 36,
                'color' => '#c62828',
                'sort_order' => 6,
                'description' => 'Le risque est jugé critique (la situation est tellement grave que l\'organisation est face à la faillite ou à une dégradation accrue de sa réputation ou image)',
            ],
        ];

        foreach ($classifications as $classification) {
            RiskClassification::query()->updateOrCreate(
                ['code' => $classification['code']],
                $classification
            );
        }
    }
}
