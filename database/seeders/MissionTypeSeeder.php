<?php

namespace Database\Seeders;

use App\Models\MissionType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MissionTypeSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'code' => 'audit_interne',
                'name' => 'Audit Interne',
                'profiles' => ['audit'],
                'description' => 'Missions d\'audit interne et suivi des recommandations émises.',
                'accent_color' => '#7c3aed',
                'sort_order' => 1,
            ],
            [
                'code' => 'audit_externe',
                'name' => 'Audit Externe',
                'profiles' => ['audit'],
                'description' => 'Missions réalisées par des auditeurs externes.',
                'accent_color' => '#475569',
                'sort_order' => 2,
            ],
            [
                'code' => 'controle_permanent',
                'name' => 'Contrôle Permanent',
                'profiles' => ['controle'],
                'description' => 'Contrôles permanents et suivi des points de contrôle.',
                'accent_color' => '#047857',
                'sort_order' => 3,
            ],
            [
                'code' => 'inspection',
                'name' => 'Inspection',
                'profiles' => ['controle'],
                'description' => 'Inspections ponctuelles et constats de conformité.',
                'accent_color' => '#2563eb',
                'sort_order' => 4,
            ],
            [
                'code' => 'cac',
                'name' => 'CAC',
                'profiles' => ['audit'],
                'description' => 'Missions du commissaire aux comptes et recommandations associées.',
                'accent_color' => '#d97706',
                'sort_order' => 5,
            ],
            [
                'code' => 'regulateur',
                'name' => 'Régulateur',
                'profiles' => ['audit', 'controle'],
                'description' => 'Missions liées aux exigences et retours du régulateur.',
                'accent_color' => '#dc2626',
                'sort_order' => 6,
            ],
        ];

        foreach ($defaults as $type) {
            MissionType::query()->updateOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'profiles' => $type['profiles'],
                    'description' => $type['description'],
                    'accent_color' => $type['accent_color'],
                    'sort_order' => $type['sort_order'],
                    'is_active' => true,
                ],
            );
        }

        $configTypes = config('mission-parametrage.mission_types', []);

        foreach ($configTypes as $index => $type) {
            $code = $type['value'] ?? null;

            if (! $code || MissionType::query()->where('code', $code)->exists()) {
                continue;
            }

            MissionType::query()->create([
                'code' => $code,
                'name' => $type['label'] ?? Str::title(str_replace('_', ' ', $code)),
                'profiles' => $type['profiles'] ?? ['audit', 'controle'],
                'description' => null,
                'accent_color' => '#047857',
                'sort_order' => 100 + $index,
                'is_active' => true,
            ]);
        }
    }
}
