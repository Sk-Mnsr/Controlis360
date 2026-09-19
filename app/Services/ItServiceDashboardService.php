<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationAnswer;
use App\Models\ApplicationQuestion;
use App\Models\ApplicationType;
use Illuminate\Support\Collection;

class ItServiceDashboardService
{
    public function dashboard(): array
    {
        $types = ApplicationType::query()
            ->where('is_active', true)
            ->with(['applications' => function ($query) {
                $query->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                    ->orderBy('code')
                    ->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $typeQuestions = ApplicationQuestion::query()
            ->where('scope', 'type')
            ->where('is_active', true)
            ->get()
            ->groupBy('application_type_id');

        $applicationIds = $types->flatMap(fn (ApplicationType $type) => $type->applications->pluck('id'))->unique()->values();

        $answersByApplication = ApplicationAnswer::query()
            ->whereIn('application_id', $applicationIds)
            ->whereIn('question_id', $typeQuestions->flatten()->pluck('id'))
            ->get()
            ->groupBy('application_id');

        $services = $types->flatMap(function (ApplicationType $type) use ($typeQuestions, $answersByApplication) {
            $questions = $typeQuestions->get($type->id)
                ?? $typeQuestions->get((string) $type->id)
                ?? collect();

            $inventoryApps = $type->applications;
            $count = $inventoryApps->count();
            $apps = $count > 0 ? $inventoryApps : collect([null]);

            return $apps->map(function (?Application $application) use ($type, $questions, $answersByApplication, $count) {
                $answers = $application
                    ? ($answersByApplication->get($application->id)
                        ?? $answersByApplication->get((string) $application->id)
                        ?? collect())
                    : collect();
                $fill = $this->computeFillRate($questions, $answers);
                $service = $this->formatFromInventory($application);

                return array_filter([
                    'application_type_id' => $type->id,
                    'code' => $type->code,
                    'name' => $type->name,
                    'accent_color' => $type->accent_color,
                    'fill_rate' => $fill['rate'],
                    'answered_count' => $fill['answered'],
                    'questions_count' => $fill['total'],
                    'inventory_application_id' => $application?->id,
                    'inventory_applications_count' => $count,
                    'inventory_application_code' => $application?->code,
                    'source' => $application ? 'inventory' : 'empty',
                    'service' => $service,
                ] + ($service ?? []), static fn ($value) => $value !== null);
            });
        })->values()->all();

        return [
            'generic_fill_rate' => $this->scopeFillRate('generic'),
            'security_fill_rate' => $this->scopeFillRate('security'),
            'services' => $services,
        ];
    }

    public function scopeFillRate(string $scope, ?int $applicationTypeId = null, ?int $applicationId = null): array
    {
        $questions = ApplicationQuestion::query()
            ->where('scope', $scope)
            ->where('is_active', true)
            ->when(
                $scope === 'type',
                fn ($q) => $q->where('application_type_id', $applicationTypeId),
                fn ($q) => $q->whereNull('application_type_id'),
            )
            ->orderBy('sort_order')
            ->get();

        $answers = ApplicationAnswer::query()
            ->whereIn('question_id', $questions->pluck('id'))
            ->when(
                $scope === 'type',
                fn ($q) => $q->where('application_id', $applicationId),
                fn ($q) => $q->whereNull('application_id'),
            )
            ->get();

        return $this->computeFillRate($questions, $answers);
    }

    public function questionsForScope(string $scope, ?int $applicationTypeId = null, ?int $applicationId = null): Collection
    {
        $query = ApplicationQuestion::query()
            ->where('scope', $scope)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($scope === 'type') {
            $query->where('application_type_id', $applicationTypeId);
        } else {
            $query->whereNull('application_type_id');
        }

        $questions = $query->get();

        $answersQuery = ApplicationAnswer::query()->whereIn('question_id', $questions->pluck('id'));

        if ($scope === 'type') {
            $answersQuery->where('application_id', $applicationId);
        } else {
            $answersQuery->whereNull('application_id');
        }

        $answersByQuestion = $answersQuery->get()->keyBy('question_id');

        return $questions->map(function (ApplicationQuestion $question) use ($answersByQuestion) {
            $answer = $answersByQuestion->get($question->id);

            return [
                'id' => $question->id,
                'label' => $question->label,
                'help' => $question->help,
                'input_type' => $question->input_type,
                'options' => $question->options ?? [],
                'is_required' => $question->is_required,
                'value' => $answer?->value,
                'details' => $answer?->details,
            ];
        });
    }

    public function saveAnswers(
        string $scope,
        ?int $applicationTypeId,
        array $answers,
        ?int $userId,
        ?int $applicationId = null,
    ): array {
        foreach ($answers as $item) {
            $questionId = (int) ($item['question_id'] ?? 0);
            $value = array_key_exists('value', $item) ? trim((string) ($item['value'] ?? '')) : '';
            $details = array_key_exists('details', $item) ? trim((string) ($item['details'] ?? '')) : '';

            $question = ApplicationQuestion::query()
                ->where('id', $questionId)
                ->where('scope', $scope)
                ->where('is_active', true)
                ->when(
                    $scope === 'type',
                    fn ($q) => $q->where('application_type_id', $applicationTypeId),
                    fn ($q) => $q->whereNull('application_type_id'),
                )
                ->first();

            if (! $question) {
                continue;
            }

            ApplicationAnswer::query()->updateOrCreate(
                [
                    'question_id' => $question->id,
                    'application_id' => $scope === 'type' ? $applicationId : null,
                ],
                [
                    'application_type_id' => $scope === 'type' ? $applicationTypeId : null,
                    'value' => $value === '' ? null : $value,
                    'details' => $details === '' ? null : $details,
                    'answered_by_id' => $userId,
                ],
            );
        }

        if ($scope === 'type' && $applicationTypeId && $applicationId) {
            return $this->scopeFillRate('type', $applicationTypeId, $applicationId);
        }

        return $this->scopeFillRate($scope);
    }

    /**
     * Met à jour (ou crée) l'application d'inventaire rattachée au type Accueil.
     * Si inventory_application_id est fourni, cible cette fiche ; sinon la première du type (ou création).
     */
    public function upsertService(int $applicationTypeId, array $data, ?int $userId): Application
    {
        $type = ApplicationType::query()->findOrFail($applicationTypeId);

        $payload = collect($data)->only([
            'exists_flag',
            'solution_name',
            'editor',
            'importance',
            'version',
            'last_version',
            'sla_exists',
            'hosting_mode',
            'users_count',
            'licenses_count',
            'license_type',
            'customization_level',
            'backups',
            'etp_support',
            'etp_changes',
            'archi_ho',
            'environment_id',
        ])->map(function ($value) {
            if ($value === null) {
                return null;
            }
            if (is_string($value)) {
                $trimmed = trim($value);

                return $trimmed === '' ? null : $trimmed;
            }

            return $value;
        })->all();

        $inventoryApplicationId = ! empty($data['inventory_application_id'])
            ? (int) $data['inventory_application_id']
            : null;

        if ($inventoryApplicationId) {
            $application = Application::query()
                ->where('id', $inventoryApplicationId)
                ->where('application_type_id', $type->id)
                ->firstOrFail();
        } else {
            $application = Application::query()
                ->where('application_type_id', $type->id)
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->first();
        }

        $mapped = [
            'application_type_id' => $type->id,
            'name' => $payload['solution_name'] ?? ($application?->name ?: $type->name),
            'editor' => $payload['editor'] ?? null,
            'importance' => $payload['importance'] ?? null,
            'version' => $payload['version'] ?? null,
            'last_version' => $payload['last_version'] ?? null,
            'sla' => $payload['sla_exists'] ?? null,
            'hosting_type' => $payload['hosting_mode'] ?? null,
            'users' => $payload['users_count'] ?? null,
            'licenses_count' => $payload['licenses_count'] ?? null,
            'license_type' => $payload['license_type'] ?? null,
            'customization_level' => $payload['customization_level'] ?? null,
            'backup' => $payload['backups'] ?? null,
            'etp_support' => $payload['etp_support'] ?? null,
            'etp_changes' => $payload['etp_changes'] ?? null,
            'archi_ho' => $payload['archi_ho'] ?? null,
            'environment_id' => $payload['environment_id'] ?? null,
            'business_domain' => $application?->business_domain ?: $type->name,
            'status' => $this->statusFromExistsFlag($payload['exists_flag'] ?? null, $application?->status),
        ];

        if ($application) {
            $application->fill($mapped);
            $application->save();

            return $application->fresh();
        }

        $mapped['code'] = $this->nextInventoryCode($type->code);
        $mapped['created_by_id'] = $userId;

        return Application::query()->create($mapped);
    }

    /**
     * Garantit une fiche inventaire pour ouvrir le questionnaire d'un type encore vide.
     */
    public function ensureInventoryApplication(int $applicationTypeId, ?int $applicationId, ?int $userId): Application
    {
        $type = ApplicationType::query()->findOrFail($applicationTypeId);

        if ($applicationId) {
            return Application::query()
                ->where('id', $applicationId)
                ->where('application_type_id', $type->id)
                ->firstOrFail();
        }

        $existing = Application::query()
            ->where('application_type_id', $type->id)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return Application::query()->create([
            'application_type_id' => $type->id,
            'code' => $this->nextInventoryCode($type->code),
            'name' => $type->name,
            'business_domain' => $type->name,
            'status' => 'active',
            'created_by_id' => $userId,
        ]);
    }

    private function statusFromExistsFlag(?string $existsFlag, ?string $fallback = null): string
    {
        $flag = strtolower(trim((string) $existsFlag));

        if ($flag === 'oui' || $flag === 'yes') {
            return 'active';
        }

        if ($flag === 'non' || $flag === 'no') {
            return 'planned';
        }

        return $fallback ?: 'active';
    }

    private function nextInventoryCode(string $typeCode): string
    {
        $prefix = 'APP-'.strtoupper(preg_replace('/[^A-Z0-9]/i', '', $typeCode) ?: 'X');
        $count = Application::query()->where('code', 'like', $prefix.'%')->count() + 1;

        return $prefix.'-'.str_pad((string) $count, 2, '0', STR_PAD_LEFT);
    }

    private function computeFillRate(Collection $questions, Collection $answers): array
    {
        $total = $questions->count();
        if ($total === 0) {
            return ['rate' => 0, 'answered' => 0, 'total' => 0];
        }

        $answerMap = $answers->keyBy('question_id');
        $answered = $questions->filter(function (ApplicationQuestion $question) use ($answerMap) {
            $answer = $answerMap->get($question->id);

            return $answer && $answer->isFilled();
        })->count();

        return [
            'rate' => (int) round(($answered / $total) * 100),
            'answered' => $answered,
            'total' => $total,
        ];
    }

    private function formatFromInventory(?Application $application): ?array
    {
        if (! $application) {
            return [
                'exists_flag' => null,
                'solution_name' => null,
                'editor' => null,
                'importance' => null,
                'version' => null,
                'last_version' => null,
                'sla_exists' => null,
                'hosting_mode' => null,
                'users_count' => null,
                'licenses_count' => null,
                'license_type' => null,
                'customization_level' => null,
                'backups' => null,
                'etp_support' => null,
                'etp_changes' => null,
                'archi_ho' => null,
                'environment_id' => null,
            ];
        }

        $exists = match ($application->status) {
            'active' => 'oui',
            'planned', 'inactive', 'retired' => 'non',
            default => null,
        };

        return [
            'id' => $application->id,
            'exists_flag' => $exists,
            'solution_name' => $application->name,
            'editor' => $application->editor,
            'importance' => $application->importance,
            'version' => $application->version,
            'last_version' => $application->last_version,
            'sla_exists' => $application->sla,
            'hosting_mode' => $application->hosting_type,
            'users_count' => $application->users,
            'licenses_count' => $application->licenses_count,
            'license_type' => $application->license_type,
            'customization_level' => $application->customization_level,
            'backups' => $application->backup,
            'etp_support' => $application->etp_support,
            'etp_changes' => $application->etp_changes,
            'archi_ho' => $application->archi_ho,
            'environment_id' => $application->environment_id,
        ];
    }
}
