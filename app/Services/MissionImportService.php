<?php

namespace App\Services;

use App\Mail\MissionAssignedMail;
use App\Models\Entity;
use App\Models\Mission;
use App\Models\User;
use App\Services\MissionRecipientResolver;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MissionImportService
{
    public const HEADERS = [
        'Type de mission',
        'Codes entités',
        'Auditeur',
        'Date début',
        'Date fin',
        'Date recommandation',
        'Niveau de risque',
        'Priorité',
        'Date échéance',
        'Statut',
        'Rapport associé',
        'Libellé recommandation',
        'Détails recommandation',
        'Commentaires',
    ];

    private const MISSION_TYPE_MAP = [
        'audit interne' => 'audit_interne',
        'audit_interne' => 'audit_interne',
        'audit externe' => 'audit_externe',
        'audit_externe' => 'audit_externe',
        'contrôle permanent' => 'controle_permanent',
        'controle permanent' => 'controle_permanent',
        'controle_permanent' => 'controle_permanent',
        'inspection' => 'inspection',
        'cac' => 'cac',
        'régulateur' => 'regulateur',
        'regulateur' => 'regulateur',
    ];

    private const RISK_MAP = [
        'faible' => 'faible',
        'moyen' => 'moyen',
        'élevé' => 'eleve',
        'eleve' => 'eleve',
        'critique' => 'critique',
    ];

    private const PRIORITY_MAP = [
        'basse' => 'basse',
        'moyenne' => 'moyenne',
        'haute' => 'haute',
    ];

    private const STATUS_MAP = [
        'ouvert' => 'ouvert',
        'ouverte' => 'ouvert',
        'open' => 'ouvert',
        'ferme' => 'ferme',
        'fermé' => 'ferme',
        'fermée' => 'ferme',
        'closed' => 'ferme',
        'émise' => 'ouvert',
        'emise' => 'ouvert',
        'en cours' => 'ouvert',
        'en_cours' => 'ouvert',
        'partiellement traitée' => 'ouvert',
        'partiellement_traitee' => 'ouvert',
        'traitée' => 'ferme',
        'traitee' => 'ferme',
        'clôturée' => 'ferme',
        'cloturee' => 'ferme',
    ];

    private const TEMPLATE_DATA_ROWS = 500;

    public function downloadTemplate(): StreamedResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $path = storage_path('app/templates/modele-import-missions.xlsx');
        $this->buildTemplateFile($path);

        return response()->download($path, 'modele-import-missions.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function buildTemplateFile(string $path): void
    {
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $parametrage = config('mission-parametrage');
        $missionTypeLabels = array_column($parametrage['mission_types'], 'label');
        $riskLabels = array_column($parametrage['risk_levels'], 'label');
        $priorityLabels = array_column($parametrage['priorities'], 'label');
        $statusLabels = array_column($parametrage['statuses'], 'label');

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Missions');

        $paramSheet = $spreadsheet->createSheet();
        $paramSheet->setTitle('Parametrage');
        $this->fillParametrageSheet($paramSheet, [
            'Types de mission' => $missionTypeLabels,
            'Niveaux de risque' => $riskLabels,
            'Priorités' => $priorityLabels,
            'Statuts' => $statusLabels,
        ]);
        $paramSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

        foreach (self::HEADERS as $col => $header) {
            $sheet->setCellValue([$col + 1, 1], $header);
            $sheet->getStyle([$col + 1, 1])->getFont()->setBold(true);
        }

        $sheet->fromArray([
            [
                $missionTypeLabels[0],
                'CREDIT',
                'Nom auditeur',
                '2026-06-01',
                '2026-06-30',
                '2026-06-15',
                $riskLabels[1],
                $priorityLabels[2],
                '2026-09-30',
                $statusLabels[0],
                'Référence rapport',
                'Libellé de la recommandation',
                'Détails optionnels',
                'Commentaires optionnels',
            ],
        ], null, 'A2');

        $lastDataRow = self::TEMPLATE_DATA_ROWS + 1;
        $this->applyListValidation($sheet, 'A', 2, $lastDataRow, $this->parametrageRange('Parametrage', 'B', count($missionTypeLabels)));
        $this->applyListValidation($sheet, 'G', 2, $lastDataRow, $this->parametrageRange('Parametrage', 'D', count($riskLabels)));
        $this->applyListValidation($sheet, 'H', 2, $lastDataRow, $this->parametrageRange('Parametrage', 'F', count($priorityLabels)));
        $this->applyListValidation($sheet, 'J', 2, $lastDataRow, $this->parametrageRange('Parametrage', 'H', count($statusLabels)));

        foreach (range('A', 'N') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        (new Xlsx($spreadsheet))->save($path);
    }

    private function fillParametrageSheet(Worksheet $sheet, array $lists): void
    {
        $columnIndex = 1;

        foreach ($lists as $title => $values) {
            $column = Coordinate::stringFromColumnIndex($columnIndex);
            $labelColumn = Coordinate::stringFromColumnIndex($columnIndex + 1);

            $sheet->setCellValue("{$column}1", $title);
            $sheet->getStyle("{$column}1")->getFont()->setBold(true);

            foreach ($values as $index => $value) {
                $sheet->setCellValue("{$labelColumn}".($index + 1), $value);
            }

            $sheet->getColumnDimension($column)->setAutoSize(true);
            $sheet->getColumnDimension($labelColumn)->setAutoSize(true);

            $columnIndex += 2;
        }
    }

    private function parametrageRange(string $sheetName, string $column, int $count): string
    {
        return sprintf("'%s'!\$%s\$1:\$%s\$%d", $sheetName, $column, $column, $count);
    }

    private function applyListValidation(
        Worksheet $sheet,
        string $column,
        int $firstRow,
        int $lastRow,
        string $formula,
    ): void {
        for ($row = $firstRow; $row <= $lastRow; $row++) {
            $validation = $sheet->getCell("{$column}{$row}")->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setErrorTitle('Valeur non autorisée');
            $validation->setError('Sélectionnez une valeur dans la liste paramétrée.');
            $validation->setPromptTitle('Liste paramétrée');
            $validation->setPrompt('Choisissez une valeur dans la liste déroulante.');
            $validation->setFormula1($formula);
        }
    }

    public function import(UploadedFile $file, User $user): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if (count($rows) < 2) {
            return [
                'imported' => 0,
                'failed' => 0,
                'missions' => [],
                'errors' => [['row' => 1, 'message' => 'Le fichier est vide ou ne contient pas de données.']],
            ];
        }

        $headerRow = array_map(fn ($cell) => $this->normalizeKey((string) $cell), $rows[0]);
        $columnIndex = $this->mapColumns($headerRow);

        $imported = [];
        $errors = [];

        for ($i = 1; $i < count($rows); $i++) {
            $rowNumber = $i + 1;
            $row = $rows[$i];

            if ($this->isEmptyRow($row)) {
                continue;
            }

            try {
                $data = $this->parseRow($row, $columnIndex, $user);
                $mission = $this->createMission($data, $user);
                $imported[] = [
                    'row' => $rowNumber,
                    'reference' => $mission->reference,
                    'title' => $mission->title,
                    'entities' => $mission->entities->pluck('name')->values(),
                    'notified' => $mission->recipients->pluck('name')->values(),
                ];
            } catch (\Throwable $e) {
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'imported' => count($imported),
            'failed' => count($errors),
            'missions' => $imported,
            'errors' => $errors,
        ];
    }

    public function createMission(array $data, User $user): Mission
    {
        $entityIds = $data['entity_ids'];
        $responsibleNames = $data['responsible_name'] ?? null;
        if ($responsibleNames === null || $responsibleNames === '') {
            $responsibleNames = $this->resolveResponsibleNames($entityIds) ?: null;
        }

        $mission = DB::transaction(function () use ($data, $entityIds, $responsibleNames, $user) {
            $mission = Mission::query()->create([
                'reference' => $data['reference'] ?? $this->generateReference(),
                'mission_type' => $data['mission_type'],
                'created_by' => $user->id,
                'auditor' => $data['auditor'],
                'issue_date' => $data['issue_date'] ?? $data['start_date'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'report_reference' => $data['report_reference'] ?? null,
                'report_attachment_paths' => $data['report_attachment_paths'] ?? null,
                'status' => $data['status'] ?? 'ouvert',
                'comments' => $data['comments'] ?? null,
            ]);

            $mission->entities()->sync($entityIds);

            if ($this->shouldCreateRecommendation($data)) {
                $recommendation = $mission->recommendations()->create([
                    'reference' => $mission->reference.'-R01',
                    'recommendation_date' => $data['recommendation_date'] ?? $data['start_date'],
                    'risk_level' => $data['risk_level'] ?? 'moyen',
                    'priority' => $data['priority'] ?? 'moyenne',
                    'status' => 'emise',
                    'responsible_name' => $responsibleNames ?: null,
                    'due_date' => $data['due_date'] ?? null,
                    'recommendation_label' => $data['recommendation_label'],
                    'recommendation_details' => $data['recommendation_details'] ?? null,
                ]);

                $recommendation->entities()->sync($entityIds);
            }

            $recipients = app(MissionRecipientResolver::class)->resolveRecipients($entityIds);

            if ($recipients->isNotEmpty()) {
                $syncData = $recipients->mapWithKeys(fn (User $recipient) => [
                    $recipient->id => ['notified_at' => now()],
                ])->all();

                $mission->recipients()->sync($syncData);
            }

            return $mission->load(['recommendations.entities', 'entities', 'creator', 'recipients']);
        });

        if ($mission->recommendations->isNotEmpty() && $mission->recipients->isNotEmpty()) {
            foreach ($mission->recipients as $recipient) {
                Mail::to($recipient->email)->send(new MissionAssignedMail($mission, $recipient, $user));
            }
        }

        return $mission;
    }

    private function shouldCreateRecommendation(array $data): bool
    {
        return ! empty($data['recommendation_label']);
    }

    private function mapColumns(array $headerRow): array
    {
        $aliases = [
            'type de mission' => 'mission_type',
            'type mission' => 'mission_type',
            'codes entites' => 'entity_codes',
            'codes entités' => 'entity_codes',
            'entites' => 'entity_codes',
            'entités' => 'entity_codes',
            'auditeur' => 'auditor',
            'date debut' => 'start_date',
            'date début' => 'start_date',
            'date fin' => 'end_date',
            'date recommandation' => 'recommendation_date',
            'niveau de risque' => 'risk_level',
            'priorite' => 'priority',
            'priorité' => 'priority',
            'date echeance' => 'due_date',
            'date échéance' => 'due_date',
            'statut' => 'status',
            'rapport associe' => 'report_reference',
            'rapport associé' => 'report_reference',
            'libelle recommandation' => 'recommendation_label',
            'libellé recommandation' => 'recommendation_label',
            'details recommandation' => 'recommendation_details',
            'détails recommandation' => 'recommendation_details',
            'commentaires' => 'comments',
        ];

        $map = [];
        foreach ($headerRow as $index => $header) {
            if (isset($aliases[$header])) {
                $map[$aliases[$header]] = $index;
            }
        }

        $required = ['mission_type', 'entity_codes', 'auditor', 'start_date', 'recommendation_date', 'risk_level', 'priority', 'recommendation_label'];
        foreach ($required as $field) {
            if (! isset($map[$field])) {
                throw new \InvalidArgumentException("Colonne manquante dans le fichier : {$field}");
            }
        }

        return $map;
    }

    private function parseRow(array $row, array $columnIndex, User $user): array
    {
        $get = fn (string $key) => trim((string) ($row[$columnIndex[$key]] ?? ''));

        $missionType = $this->mapValue($get('mission_type'), self::MISSION_TYPE_MAP, 'type de mission');
        $riskLevel = $this->mapValue($get('risk_level'), self::RISK_MAP, 'niveau de risque');
        $priority = $this->mapValue($get('priority'), self::PRIORITY_MAP, 'priorité');
        $statusRaw = $get('status');
        $status = $statusRaw !== ''
            ? $this->mapValue($statusRaw, self::STATUS_MAP, 'statut')
            : 'ouvert';

        $entityIds = $this->resolveEntityIds($get('entity_codes'), $user);

        if (empty($entityIds)) {
            throw new \InvalidArgumentException('Aucune entité valide trouvée pour cette ligne.');
        }

        return [
            'mission_type' => $missionType,
            'entity_ids' => $entityIds,
            'auditor' => $get('auditor') ?: $user->name,
            'start_date' => $this->parseDate($get('start_date'), 'date début'),
            'end_date' => $get('end_date') ? $this->parseDate($get('end_date'), 'date fin') : null,
            'recommendation_date' => $this->parseDate($get('recommendation_date'), 'date recommandation'),
            'risk_level' => $riskLevel,
            'priority' => $priority,
            'due_date' => $get('due_date') ? $this->parseDate($get('due_date'), 'date échéance') : null,
            'status' => $status,
            'report_reference' => $get('report_reference') ?: null,
            'recommendation_label' => $get('recommendation_label'),
            'recommendation_details' => $get('recommendation_details') ?: null,
            'comments' => $get('comments') ?: null,
        ];
    }

    private function resolveEntityIds(string $raw, User $user): array
    {
        $codes = preg_split('/[;,|]+/', $raw) ?: [];
        $codes = array_values(array_filter(array_map(fn ($c) => strtoupper(trim($c)), $codes)));

        if (empty($codes)) {
            return [];
        }

        $query = Entity::query()
            ->where('is_active', true)
            ->whereIn('code', $codes);

        $environmentIds = $user->environment_ids;
        if (! empty($environmentIds) && ! $user->isSuperAdmin()) {
            $query->whereIn('environment_id', $environmentIds);
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    private function resolveResponsibleRecipients(array $entityIds)
    {
        return app(MissionRecipientResolver::class)->resolveRecipients($entityIds);
    }

    private function resolveResponsibleNames(array $entityIds): string
    {
        return app(MissionRecipientResolver::class)->resolveResponsibleNames($entityIds);
    }

    private function generateReference(): string
    {
        $year = now()->format('Y');
        $count = Mission::query()->whereYear('created_at', $year)->count() + 1;

        return sprintf('MIS-%s-%04d', $year, $count);
    }

    private function mapValue(string $value, array $map, string $label): string
    {
        $key = $this->normalizeKey($value);

        if (! isset($map[$key])) {
            throw new \InvalidArgumentException("Valeur invalide pour {$label} : {$value}");
        }

        return $map[$key];
    }

    private function parseDate(string $value, string $label): string
    {
        if ($value === '') {
            throw new \InvalidArgumentException("{$label} est obligatoire.");
        }

        if (is_numeric($value)) {
            $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);

            return $date->format('Y-m-d');
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            throw new \InvalidArgumentException("Date invalide pour {$label} : {$value}");
        }
    }

    private function normalizeKey(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['é', 'è', 'ê', 'ë'], 'e', $value);
        $value = str_replace(['à', 'â'], 'a', $value);
        $value = str_replace('ù', 'u', $value);
        $value = str_replace('ô', 'o', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return $value ?? '';
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
