<?php

namespace Database\Seeders;

use App\Models\ApplicationQuestion;
use App\Models\ApplicationType;
use App\Models\ItService;
use Illuminate\Database\Seeder;

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

            $demo = $demoServices[$type['code']] ?? ['exists_flag' => null];

            ItService::query()->updateOrCreate(
                ['application_type_id' => $model->id],
                $demo,
            );

            $this->seedTypeQuestions($model);
        }

        $this->seedGlobalQuestions('generic', [
            'Quelle est la gouvernance IT globale en place ?',
            'Existe-t-il une cartographie des processus IT ?',
            'Les incidents majeurs sont-ils tracés et suivis ?',
            'Y a-t-il un plan de continuité d’activité IT ?',
            'Les accès privilégiés sont-ils revus périodiquement ?',
        ]);

        $this->seedGlobalQuestions('security', [
            'Une politique de sécurité SI est-elle formalisée ?',
            'Les vulnérabilités critiques sont-elles corrigées sous SLA ?',
            'L’authentification forte est-elle déployée sur les apps sensibles ?',
            'Les journaux de sécurité sont-ils centralisés et analysés ?',
            'Des tests d’intrusion sont-ils réalisés périodiquement ?',
        ]);
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
                'users_count' => 'environ 50',
                'licenses_count' => 'Illimité',
                'license_type' => 'Partagées',
                'customization_level' => 'Très haut',
                'backups' => 'oui',
                'etp_support' => 'Niveau 2 : Filiale : ETP dédiés : 2',
                'archi_ho' => 'non',
            ],
            'MB' => [
                'exists_flag' => 'oui',
                'solution_name' => 'COFINA MOBILE +',
                'editor' => 'COFINA',
                'importance' => 'Primordial',
                'version' => '2.1',
                'last_version' => '2.3',
                'sla_exists' => 'oui',
                'hosting_mode' => 'Cloud',
                'users_count' => 'Illimité',
                'licenses_count' => 'Illimité',
                'license_type' => 'Partagées',
                'customization_level' => 'Haut',
                'backups' => 'oui',
                'etp_support' => 'Support groupe + filiales',
                'archi_ho' => 'oui',
            ],
            'IB' => [
                'exists_flag' => 'oui',
                'solution_name' => 'COFINA NET',
                'editor' => 'COFINA',
                'importance' => 'Moyen',
                'sla_exists' => 'oui',
                'hosting_mode' => 'On-site',
                'backups' => 'oui',
                'archi_ho' => 'oui',
            ],
            'AML' => [
                'exists_flag' => 'oui',
                'solution_name' => 'Vneuron AML',
                'editor' => 'Vneuron',
                'importance' => 'Critique',
                'sla_exists' => 'oui',
                'hosting_mode' => 'On-site',
                'backups' => 'oui',
                'archi_ho' => 'oui',
            ],
            'Collect' => [
                'exists_flag' => 'oui',
                'solution_name' => 'Collecte terrain',
                'editor' => 'COFINA',
                'importance' => 'Moyen',
                'hosting_mode' => 'On-site',
                'backups' => 'oui',
            ],
            'CRM' => [
                'exists_flag' => 'oui',
                'solution_name' => 'CRM COFINA',
                'editor' => 'COFINA',
                'importance' => 'Moyen',
                'hosting_mode' => 'Cloud',
                'backups' => 'oui',
            ],
            'Call Center' => [
                'exists_flag' => 'non',
                'solution_name' => 'Non prévu',
            ],
            'DocM' => [
                'exists_flag' => 'non',
                'solution_name' => 'Non prévu',
            ],
            'BI' => [
                'exists_flag' => 'non',
                'solution_name' => 'Non prévu',
            ],
            'SOA' => [
                'exists_flag' => 'non',
                'solution_name' => 'Non prévu',
            ],
            'ERP Finance' => [
                'exists_flag' => 'oui',
                'solution_name' => 'ERP Finance',
                'importance' => 'Critique',
                'hosting_mode' => 'On-site',
                'backups' => 'oui',
            ],
            'ERP HR' => [
                'exists_flag' => 'oui',
                'solution_name' => 'ERP RH',
                'importance' => 'Moyen',
                'hosting_mode' => 'On-site',
                'backups' => 'oui',
            ],
            'Gestion des Achats' => [
                'exists_flag' => 'oui',
                'solution_name' => 'Gestion des Achats',
                'importance' => 'Faible',
                'hosting_mode' => 'On-site',
            ],
            'Contract' => [
                'exists_flag' => 'oui',
                'solution_name' => 'Contract Management',
                'importance' => 'Moyen',
            ],
            'Messagerie' => [
                'exists_flag' => 'oui',
                'solution_name' => 'OFFICE 365',
                'editor' => 'Microsoft',
                'importance' => 'Primordial',
                'sla_exists' => 'oui',
                'hosting_mode' => 'Cloud',
                'users_count' => 'Illimité',
                'licenses_count' => 'Nominatives',
                'license_type' => 'Nominatives',
                'backups' => 'oui',
                'archi_ho' => 'oui',
            ],
            'Autres' => [
                'exists_flag' => 'oui',
                'solution_name' => 'Divers outils métier',
                'importance' => 'Faible',
            ],
            'Outils IT' => [
                'exists_flag' => 'oui',
                'solution_name' => 'Outils d’administration IT',
                'importance' => 'Moyen',
                'hosting_mode' => 'On-site',
                'backups' => 'oui',
            ],
        ];
    }

    private function seedTypeQuestions(ApplicationType $type): void
    {
        $labels = [
            'La solution est-elle en production ?',
            'Qui est le responsable métier de cette application ?',
            'Qui est le responsable technique / support ?',
            'Un contrat de maintenance est-il en vigueur ?',
            'Les sauvegardes sont-elles testées régulièrement ?',
            'Un plan de reprise est-il documenté pour ce service ?',
            'Des dépendances critiques existent-elles (intégrations) ?',
            'Le niveau de criticité métier est-il validé ?',
        ];

        foreach ($labels as $index => $label) {
            ApplicationQuestion::query()->updateOrCreate(
                [
                    'scope' => 'type',
                    'application_type_id' => $type->id,
                    'label' => $label,
                ],
                [
                    'input_type' => $index === 1 || $index === 2 ? 'text' : 'yes_no',
                    'is_required' => true,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }

    private function seedGlobalQuestions(string $scope, array $labels): void
    {
        foreach ($labels as $index => $label) {
            ApplicationQuestion::query()->updateOrCreate(
                [
                    'scope' => $scope,
                    'application_type_id' => null,
                    'label' => $label,
                ],
                [
                    'input_type' => 'yes_no',
                    'is_required' => true,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }
}
