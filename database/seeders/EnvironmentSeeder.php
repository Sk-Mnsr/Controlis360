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
            ['name' => 'Togo', 'code' => 'TG'],
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
            ['type' => 'department', 'name' => 'MARKETING PRODUIT', 'code' => 'MARKETING_PRODUIT', 'sort_order' => 1],
            ['type' => 'department', 'name' => 'AGENCES', 'code' => 'AGENCES', 'sort_order' => 2],
            ['type' => 'department', 'name' => 'RH', 'code' => 'RH', 'sort_order' => 3],
            ['type' => 'department', 'name' => 'OPERATIONS', 'code' => 'OPERATIONS', 'sort_order' => 4],
            ['type' => 'department', 'name' => 'CREDIT', 'code' => 'CREDIT', 'sort_order' => 5],
            ['type' => 'department', 'name' => 'FINANCES & REPORT', 'code' => 'FINANCES_REPORT', 'sort_order' => 6],
            ['type' => 'department', 'name' => 'IT', 'code' => 'IT', 'sort_order' => 7],
            ['type' => 'department', 'name' => 'RECOUVREMENT', 'code' => 'RECOUVREMENT', 'sort_order' => 8],
            ['type' => 'department', 'name' => 'PRODUITS DIGITAUX', 'code' => 'PRODUITS_DIGITAUX', 'sort_order' => 9],
            ['type' => 'department', 'name' => 'GOUVERNANCE & CI', 'code' => 'GOUVERNANCE_CI', 'sort_order' => 10],
            ['type' => 'department', 'name' => 'JURIDIQUE & CTX', 'code' => 'JURIDIQUE_CTX', 'sort_order' => 11],
            ['type' => 'department', 'name' => 'ACHATS & LOGISTIQUE', 'code' => 'ACHATS_LOGISTIQUE', 'sort_order' => 12],
            ['type' => 'department', 'name' => 'CONFORMITE', 'code' => 'CONFORMITE', 'sort_order' => 13],
            ['type' => 'agency', 'name' => 'Agence principale', 'code' => 'AGENCE_PRINCIPALE', 'sort_order' => 14],
        ];

        foreach ($entities as $entityData) {
            Entity::query()->updateOrCreate(
                ['environment_id' => $environment->id, 'code' => $entityData['code']],
                array_merge($entityData, ['is_active' => true])
            );
        }
    }
}
