<?php

namespace Database\Seeders;

use App\Models\Entity;
use App\Models\Environment;
use Illuminate\Database\Seeder;

class EnvironmentSeeder extends Seeder
{
    public function run(): void
    {
        $environments = [
            ['name' => 'Sénégal', 'code' => 'SN'],
            ['name' => 'Togo', 'code' => 'TG']
        ];

        foreach ($environments as $envData) {
            $environment = Environment::query()->updateOrCreate(
                ['code' => $envData['code']],
                ['name' => $envData['name'], 'is_active' => true]
            );

            $this->seedEntities($environment);
        }
    }

    private function seedEntities(Environment $environment): void
    {
        $entities = [
            ['type' => 'department', 'name' => 'CREDIT', 'code' => 'CREDIT', 'sort_order' => 1],
            ['type' => 'department', 'name' => 'OPERATIONS', 'code' => 'OPERATIONS', 'sort_order' => 2],
            ['type' => 'department', 'name' => 'CONFORMITE', 'code' => 'CONFORMITE', 'sort_order' => 3],
            ['type' => 'agency', 'name' => 'Agence principale', 'code' => 'AGENCE_PRINCIPALE', 'sort_order' => 4],
        ];

        foreach ($entities as $entityData) {
            Entity::query()->updateOrCreate(
                ['environment_id' => $environment->id, 'code' => $entityData['code']],
                array_merge($entityData, ['is_active' => true])
            );
        }
    }
}
