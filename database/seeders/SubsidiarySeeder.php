<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Subsidiary;
use Illuminate\Database\Seeder;

class SubsidiarySeeder extends Seeder
{
    public function run(): void
    {
        $senegal = Country::query()->where('code', 'SN')->first();

        if (! $senegal) {
            return;
        }

        $subsidiaries = [
            ['name' => 'MESO', 'code' => 'MESO'],
            ['name' => 'CASH DEAL', 'code' => 'CASH_DEAL'],
        ];

        foreach ($subsidiaries as $subsidiary) {
            Subsidiary::query()->updateOrCreate(
                ['code' => $subsidiary['code']],
                [
                    'country_id' => $senegal->id,
                    'name' => $subsidiary['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}
