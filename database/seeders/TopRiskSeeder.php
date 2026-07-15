<?php

namespace Database\Seeders;

use App\Models\TopRisk;
use Illuminate\Database\Seeder;

class TopRiskSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'process_name' => null,
                'sub_process_name' => 'Mauvaise gestion des prêts',
                'major_exceptions' => null,
                'risk_family' => null,
                'gravity' => null,
                'probability' => null,
                'sort_order' => 1,
            ],
        ];

        $keptIds = [];

        foreach ($rows as $row) {
            $risk = TopRisk::query()->updateOrCreate(
                ['sub_process_name' => $row['sub_process_name']],
                $row
            );
            $keptIds[] = $risk->id;
        }

        TopRisk::query()->whereNotIn('id', $keptIds)->delete();
    }
}
