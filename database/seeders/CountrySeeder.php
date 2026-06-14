<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['name' => 'Sénégal', 'code' => 'SN'],
            ['name' => 'Côte d\'Ivoire', 'code' => 'CI'],
            ['name' => 'Mali', 'code' => 'ML'],
            ['name' => 'Guinée', 'code' => 'GN'],
            ['name' => 'Burkina Faso', 'code' => 'BF'],
            ['name' => 'Gabon', 'code' => 'GA'],
            ['name' => 'Cameroun', 'code' => 'CM'],
            ['name' => 'Togo', 'code' => 'TG'],
            ['name' => 'Bénin', 'code' => 'BJ'],
            ['name' => 'RD Congo', 'code' => 'CD'],
        ];

        foreach ($countries as $country) {
            Country::query()->updateOrCreate(
                ['code' => $country['code']],
                ['name' => $country['name'], 'is_active' => true]
            );
        }
    }
}
