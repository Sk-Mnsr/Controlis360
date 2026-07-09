<?php

namespace Database\Seeders;

use App\Services\MissionParametrageService;
use Illuminate\Database\Seeder;

class MissionParametrageSeeder extends Seeder
{
    public function run(): void
    {
        app(MissionParametrageService::class)->updateSettings([]);
    }
}
