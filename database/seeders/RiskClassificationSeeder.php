<?php

namespace Database\Seeders;

use App\Models\RiskClassification;
use Illuminate\Database\Seeder;

class RiskClassificationSeeder extends Seeder
{
    public function run(): void
    {
        $classifications = [
            ['name' => 'Non significatif', 'code' => 'non_significatif', 'min_score' => 1, 'max_score' => 1, 'color' => '#22c55e', 'sort_order' => 1],
            ['name' => 'Faible', 'code' => 'faible', 'min_score' => 2, 'max_score' => 4, 'color' => '#84cc16', 'sort_order' => 2],
            ['name' => 'Modéré', 'code' => 'modere', 'min_score' => 5, 'max_score' => 9, 'color' => '#eab308', 'sort_order' => 3],
            ['name' => 'Élevé', 'code' => 'eleve', 'min_score' => 10, 'max_score' => 16, 'color' => '#f97316', 'sort_order' => 4],
            ['name' => 'Très élevé', 'code' => 'tres_eleve', 'min_score' => 17, 'max_score' => 24, 'color' => '#ef4444', 'sort_order' => 5],
            ['name' => 'Critique', 'code' => 'critique', 'min_score' => 25, 'max_score' => 36, 'color' => '#991b1b', 'sort_order' => 6],
        ];

        foreach ($classifications as $classification) {
            RiskClassification::query()->updateOrCreate(
                ['code' => $classification['code']],
                $classification
            );
        }
    }
}
