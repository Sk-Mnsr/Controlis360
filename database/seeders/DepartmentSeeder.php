<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Subsidiary;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $meso = Subsidiary::query()->where('code', 'MESO')->first();

        if (! $meso) {
            return;
        }

        $departments = [
            ['name' => 'DMCC', 'code' => 'DMCC', 'ratio' => null, 'sort_order' => 1],
            ['name' => 'AGENCES', 'code' => 'AGENCES', 'ratio' => null, 'sort_order' => 2],
            ['name' => 'RESSOURCES HUMAINES', 'code' => 'RH', 'ratio' => null, 'sort_order' => 3],
            ['name' => 'OPERATIONS', 'code' => 'OPERATIONS', 'ratio' => 0.10, 'sort_order' => 4],
            ['name' => 'CREDIT', 'code' => 'CREDIT', 'ratio' => 0.40, 'sort_order' => 5],
            ['name' => 'FINANCES & REPORTING', 'code' => 'FINANCES', 'ratio' => null, 'sort_order' => 6],
            ['name' => 'IT', 'code' => 'IT', 'ratio' => null, 'sort_order' => 7],
            ['name' => 'RECOUVREMENT', 'code' => 'RECOUVREMENT', 'ratio' => null, 'sort_order' => 8],
            ['name' => 'PRODUITS DIGITAUX', 'code' => 'PRODUITS_DIGITAUX', 'ratio' => null, 'sort_order' => 9],
            ['name' => 'GOUV & CONTRÔLE INTERNE', 'code' => 'GOUV_CI', 'ratio' => null, 'sort_order' => 10],
            ['name' => 'JURIDIQUE & CONTENTIEUX', 'code' => 'JURIDIQUE', 'ratio' => null, 'sort_order' => 11],
            ['name' => 'ACHATS & LOGISTIQUE', 'code' => 'ACHATS', 'ratio' => null, 'sort_order' => 12],
            ['name' => 'CONFORMITE', 'code' => 'CONFORMITE', 'ratio' => null, 'sort_order' => 13],
            ['name' => 'INNOVATION ET PARTENARIAT', 'code' => 'INNOVATION', 'ratio' => null, 'sort_order' => 14],
            ['name' => 'GRAND COMPTE', 'code' => 'GRAND_COMPTE', 'ratio' => null, 'sort_order' => 15],
        ];

        foreach ($departments as $department) {
            Department::query()->updateOrCreate(
                ['subsidiary_id' => $meso->id, 'code' => $department['code']],
                [
                    'name' => $department['name'],
                    'ratio' => $department['ratio'],
                    'sort_order' => $department['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
