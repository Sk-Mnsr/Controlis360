<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\ApplicationQuestion;
use App\Models\ApplicationType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ApplicationTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'CBS', 'name' => 'Core Banking System', 'accent_color' => '#166534', 'sort_order' => 1],
            ['code' => 'LOS', 'name' => 'Loan Origination System', 'accent_color' => '#7f1d1d', 'sort_order' => 2],
            ['code' => 'AB', 'name' => 'Agency Banking', 'accent_color' => '#115e59', 'sort_order' => 3],
            ['code' => 'MB', 'name' => 'Mobile Banking', 'accent_color' => '#6b21a8', 'sort_order' => 4],
            ['code' => 'IB', 'name' => 'Internet Banking', 'accent_color' => '#be185d', 'sort_order' => 5],
            ['code' => 'AML', 'name' => 'Anti Money Laundering', 'accent_color' => '#0369a1', 'sort_order' => 6],
            ['code' => 'Collect', 'name' => 'Collecte', 'accent_color' => '#ca8a04', 'sort_order' => 7],
            ['code' => 'CRM', 'name' => 'Customer Relationship Management', 'accent_color' => '#b91c1c', 'sort_order' => 8],
            ['code' => 'Call Center', 'name' => 'Call Center', 'accent_color' => '#db2777', 'sort_order' => 9],
            ['code' => 'DocM', 'name' => 'Document Management', 'accent_color' => '#4d7c0f', 'sort_order' => 10],
            ['code' => 'BI', 'name' => 'Business Intelligence', 'accent_color' => '#65a30d', 'sort_order' => 11],
            ['code' => 'SOA', 'name' => 'Service Oriented Architecture', 'accent_color' => '#e11d48', 'sort_order' => 12],
            ['code' => 'ERP Finance', 'name' => 'ERP Finance', 'accent_color' => '#3730a3', 'sort_order' => 13],
            ['code' => 'ERP HR', 'name' => 'ERP RH', 'accent_color' => '#475569', 'sort_order' => 14],
            ['code' => 'Gestion des Achats', 'name' => 'Gestion des Achats', 'accent_color' => '#15803d', 'sort_order' => 15],
            ['code' => 'Contract', 'name' => 'Contract Management', 'accent_color' => '#14532d', 'sort_order' => 16],
            ['code' => 'Messagerie', 'name' => 'Messagerie', 'accent_color' => '#dc2626', 'sort_order' => 17],
            ['code' => 'Autres', 'name' => 'Autres applications', 'accent_color' => '#0891b2', 'sort_order' => 18],
            ['code' => 'Outils IT', 'name' => 'Outils IT', 'accent_color' => '#64748b', 'sort_order' => 19],
        ];

        $catalog = $this->loadQuestionCatalog();
        $demoServices = $this->demoServices();

        foreach ($types as $type) {
            $model = ApplicationType::query()->updateOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'accent_color' => $type['accent_color'],
                    'sort_order' => $type['sort_order'],
                    'is_active' => true,
                ],
            );

            $demo = $demoServices[$type['code']] ?? ['exists_flag' => 'non', 'solution_name' => 'Non prévu'];
            $this->seedInventoryApplication($model, is_array($demo) ? $demo : []);

            $this->seedTypeQuestions($model, $catalog['types'][$type['code']] ?? []);
        }

        $this->seedScopedQuestions('generic', $catalog['generic'] ?? []);
        $this->seedScopedQuestions('security', $catalog['security'] ?? []);
    }

    private function loadQuestionCatalog(): array
    {
        $path = database_path('data/it_audit_questions.json');

        if (! File::exists($path)) {
            return ['types' => [], 'generic' => [], 'security' => []];
        }

        $decoded = json_decode(File::get($path), true);

        return is_array($decoded) ? $decoded : ['types' => [], 'generic' => [], 'security' => []];
    }

    private function seedInventoryApplication(ApplicationType $type, array $demo): void
    {
        $exists = strtolower((string) ($demo['exists_flag'] ?? ''));
        $status = in_array($exists, ['oui', 'yes'], true) ? 'active' : 'planned';
        $name = trim((string) ($demo['solution_name'] ?? ''));
        if ($name === '') {
            $name = $type->name;
        }

        $code = 'APP-'.strtoupper(preg_replace('/[^A-Z0-9]/i', '', $type->code) ?: 'X').'-01';

        $payload = [
            'name' => $name,
            'business_domain' => $type->name,
            'status' => $status,
            'editor' => $demo['editor'] ?? null,
            'importance' => $demo['importance'] ?? null,
            'version' => $demo['version'] ?? null,
            'last_version' => $demo['last_version'] ?? null,
            'sla' => $demo['sla_exists'] ?? null,
            'hosting_type' => $demo['hosting_mode'] ?? null,
            'users' => $demo['users_count'] ?? null,
            'licenses_count' => $demo['licenses_count'] ?? null,
            'license_type' => $demo['license_type'] ?? null,
            'customization_level' => $demo['customization_level'] ?? null,
            'backup' => $demo['backups'] ?? null,
            'etp_support' => $demo['etp_support'] ?? null,
            'etp_changes' => $demo['etp_changes'] ?? null,
            'archi_ho' => $demo['archi_ho'] ?? null,
            'application_type_id' => $type->id,
        ];

        $existing = Application::query()
            ->where('application_type_id', $type->id)
            ->orderBy('id')
            ->first();

        if ($existing) {
            $existing->fill($payload)->save();

            return;
        }

        if (Application::query()->where('code', $code)->exists()) {
            $code = 'APP-'.strtoupper(preg_replace('/[^A-Z0-9]/i', '', $type->code) ?: 'X').'-'.str_pad((string) ($type->id), 2, '0', STR_PAD_LEFT);
        }

        Application::query()->create(array_merge($payload, ['code' => $code]));
    }

    private function seedTypeQuestions(ApplicationType $type, array $questions): void
    {
        if ($questions === []) {
            return;
        }

        $keepLabels = [];

        foreach ($questions as $index => $question) {
            $label = trim((string) ($question['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $keepLabels[] = $label;

            ApplicationQuestion::query()->updateOrCreate(
                [
                    'scope' => 'type',
                    'application_type_id' => $type->id,
                    'label' => $label,
                ],
                [
                    'help' => $question['help'] ?? null,
                    'input_type' => $question['input_type'] ?? 'text',
                    'is_required' => false,
                    'is_active' => true,
                    'sort_order' => (int) ($question['sort_order'] ?? ($index + 1)),
                ],
            );
        }

        ApplicationQuestion::query()
            ->where('scope', 'type')
            ->where('application_type_id', $type->id)
            ->whereNotIn('label', $keepLabels)
            ->update(['is_active' => false]);
    }

    private function seedScopedQuestions(string $scope, array $questions): void
    {
        if ($questions === []) {
            return;
        }

        $keepLabels = [];

        foreach ($questions as $index => $question) {
            $label = trim((string) ($question['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $keepLabels[] = $label;

            ApplicationQuestion::query()->updateOrCreate(
                [
                    'scope' => $scope,
                    'application_type_id' => null,
                    'label' => $label,
                ],
                [
                    'help' => $question['help'] ?? null,
                    'input_type' => $question['input_type'] ?? 'text',
                    'is_required' => false,
                    'is_active' => true,
                    'sort_order' => (int) ($question['sort_order'] ?? ($index + 1)),
                ],
            );
        }

        ApplicationQuestion::query()
            ->where('scope', $scope)
            ->whereNull('application_type_id')
            ->whereNotIn('label', $keepLabels)
            ->update(['is_active' => false]);
    }

    private function demoServices(): array
    {
        return [
            'CBS' => [
                'exists_flag' => 'oui',
                'solution_name' => 'Oracle FLEXCUBE Universal Banking',
                'editor' => 'Oracle',
                'importance' => 'Primordial',
                'version' => '14.7',
                'last_version' => '14.8',
                'sla_exists' => 'oui',
                'hosting_mode' => 'On-site',
                'users_count' => '1000 pour tout le groupe',
                'licenses_count' => 'Illimité',
                'license_type' => 'Nominatives',
                'customization_level' => 'Moyen',
                'backups' => 'oui',
                'etp_support' => 'Expertise technique, capacité à résoudre des problèmes complexes',
                'archi_ho' => 'oui',
            ],
            'LOS' => [
                'exists_flag' => 'oui',
                'solution_name' => 'CREDITFLOW',
                'editor' => 'EDAN',
                'importance' => 'Moyen',
                'version' => '1.0',
                'last_version' => '1.0',
                'sla_exists' => 'oui',
                'hosting_mode' => 'On-site',
                'users_count' => 'environ 50',
                'licenses_count' => 'une licence par filiale',
                'license_type' => 'Nominatives',
                'customization_level' => 'Haut',
                'backups' => 'oui',
                'etp_support' => 'Niveau 2 : Filiale : ETP dédiés : 3',
                'archi_ho' => 'oui',
            ],
            'AB' => [
                'exists_flag' => 'oui',
                'solution_name' => 'KIWI',
                'editor' => 'MARIS SOLUTION',
                'importance' => 'Critique',
                'version' => '1.7',
                'last_version' => '4.1',
                'sla_exists' => 'oui',
                'hosting_mode' => 'Cloud',
                'users_count' => 'Illimité',
                'licenses_count' => 'Illimité',
                'license_type' => 'Nominatives',
                'customization_level' => 'Très haut',
                'backups' => 'oui',
                'etp_support' => 'Niveau 1 : Holding ETP 5 / Filiale ETP 1',
                'archi_ho' => 'non',
            ],
            'MB' => [
                'exists_flag' => 'oui',
                'solution_name' => 'COFINA MOBILE +',
                'editor' => 'SMARTS SOLUTIONS',
                'importance' => 'Primordial',
                'version' => '1.7',
                'last_version' => '1.7.5',
                'sla_exists' => 'non',
                'hosting_mode' => 'Cloud',
                'users_count' => '8600',
                'licenses_count' => 'VOIR IT GROUPE',
                'license_type' => 'Nominatives',
                'customization_level' => 'Très haut',
                'backups' => 'oui',
                'archi_ho' => 'oui',
            ],
            'IB' => [
                'exists_flag' => 'non',
                'solution_name' => 'Non prévu',
                'importance' => 'Moyen',
            ],
            'AML' => [
                'exists_flag' => 'oui',
                'solution_name' => 'REIS',
                'editor' => 'Vneuron',
                'importance' => 'Critique',
                'version' => 'Vneuron © Reis™ RCS 4.1',
                'last_version' => '4.1',
                'sla_exists' => 'oui',
                'hosting_mode' => 'Cloud',
                'users_count' => 'une vingtaine',
                'licenses_count' => 'une licence par filiale',
                'license_type' => 'Partagées',
                'customization_level' => 'Très haut',
                'backups' => 'oui',
                'archi_ho' => 'non',
            ],
            'Collect' => [
                'exists_flag' => 'non',
                'solution_name' => 'Module : Collection',
                'importance' => 'Critique',
                'version' => 'Version 14.7',
                'last_version' => 'Version 14.9',
                'hosting_mode' => 'On-site',
            ],
            'CRM' => ['exists_flag' => 'non', 'solution_name' => 'Non prévu', 'importance' => 'Moyen'],
            'Call Center' => ['exists_flag' => 'non', 'solution_name' => 'Non prévu', 'importance' => 'Moyen'],
            'DocM' => ['exists_flag' => 'non', 'solution_name' => 'Non prévu', 'importance' => 'Faible'],
            'BI' => ['exists_flag' => 'non', 'solution_name' => 'Non prévu', 'importance' => 'Moyen'],
            'SOA' => ['exists_flag' => 'non', 'solution_name' => 'Non prévu', 'importance' => 'Moyen'],
            'ERP Finance' => [
                'exists_flag' => 'oui',
                'solution_name' => 'Module CBS',
                'importance' => 'Moyen',
                'hosting_mode' => 'On-site',
            ],
            'ERP HR' => [
                'exists_flag' => 'oui',
                'solution_name' => 'digitalisée d\'évaluation des performances de COFINA',
                'editor' => 'COFINA',
                'importance' => 'Moyen',
                'hosting_mode' => 'On-site',
            ],
            'Gestion des Achats' => [
                'exists_flag' => 'oui',
                'solution_name' => 'COFIFED',
                'editor' => 'COFINA',
                'importance' => 'Moyen',
                'version' => '1.1',
                'last_version' => '1.2',
                'hosting_mode' => 'On-site',
            ],
            'Contract' => [
                'exists_flag' => 'oui',
                'solution_name' => 'application de recrutement RH & gestion des contrats',
                'editor' => 'COFINA',
                'importance' => 'Moyen',
                'hosting_mode' => 'On-site',
            ],
            'Messagerie' => [
                'exists_flag' => 'oui',
                'solution_name' => 'OFFICE 365',
                'editor' => 'Microsoft',
                'importance' => 'Primordial',
                'sla_exists' => 'non',
                'hosting_mode' => 'Cloud',
                'archi_ho' => 'oui',
            ],
            'Autres' => ['exists_flag' => 'oui'],
            'Outils IT' => ['exists_flag' => 'oui'],
        ];
    }
}
