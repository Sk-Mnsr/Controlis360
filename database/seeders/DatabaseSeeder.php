<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            SubsidiarySeeder::class,
            DepartmentSeeder::class,
            ScaleLevelSeeder::class,
            RiskClassificationSeeder::class,
            RiskFamilySeeder::class,
            EnvironmentSeeder::class,
            MethodologyPageSeeder::class,
            TopRiskSeeder::class,
            OperationalRiskSeeder::class,
            UserSeeder::class,
            MissionFlowTestSeeder::class,
            MissionTypeSeeder::class,
            MissionParametrageSeeder::class,
        ]);
    }
}
