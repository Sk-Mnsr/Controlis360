<?php

namespace App\Services;

use App\Models\ApplicationAnswer;
use App\Models\ApplicationQuestion;
use App\Models\ApplicationType;
use App\Models\ItService;
use Illuminate\Support\Collection;

class ItServiceDashboardService
{
    public function dashboard(): array
    {
        $types = ApplicationType::query()
            ->where('is_active', true)
            ->with('itService')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $typeQuestions = ApplicationQuestion::query()
            ->where('scope', 'type')
            ->where('is_active', true)
            ->get()
            ->groupBy('application_type_id');

        $typeAnswers = ApplicationAnswer::query()
            ->whereNotNull('application_type_id')
            ->whereIn('question_id', $typeQuestions->flatten()->pluck('id'))
            ->get()
            ->groupBy('application_type_id');

        $services = $types->map(function (ApplicationType $type) use ($typeQuestions, $typeAnswers) {
            // groupBy peut indexer en string selon le driver SQL
            $questions = $typeQuestions->get($type->id)
                ?? $typeQuestions->get((string) $type->id)
                ?? collect();
            $answers = $typeAnswers->get($type->id)
                ?? $typeAnswers->get((string) $type->id)
                ?? collect();
            $fill = $this->computeFillRate($questions, $answers);
            $service = $this->formatService($type->itService);

            return array_filter([
                'application_type_id' => $type->id,
                'code' => $type->code,
                'name' => $type->name,
                'accent_color' => $type->accent_color,
                'fill_rate' => $fill['rate'],
                'answered_count' => $fill['answered'],
                'questions_count' => $fill['total'],
                'service' => $service,
            ] + ($service ?? []), static fn ($value) => $value !== null);

        })->values()->all();

        return [
            'generic_fill_rate' => $this->scopeFillRate('generic'),
            'security_fill_rate' => $this->scopeFillRate('security'),
            'services' => $services,
        ];
    }

    public function scopeFillRate(string $scope, ?int $applicationTypeId = null): array
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
                fn ($q) => $q->where('application_type_id', $applicationTypeId),
                fn ($q) => $q->whereNull('application_type_id'),
            )
            ->get();

        return $this->computeFillRate($questions, $answers);
    }

    public function questionsForScope(string $scope, ?int $applicationTypeId = null): Collection
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
            $answersQuery->where('application_type_id', $applicationTypeId);
        } else {
            $answersQuery->whereNull('application_type_id');
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
            ];
        });
    }

    public function saveAnswers(string $scope, ?int $applicationTypeId, array $answers, ?int $userId): array
    {
        foreach ($answers as $item) {
            $questionId = (int) ($item['question_id'] ?? 0);
            $value = array_key_exists('value', $item) ? trim((string) ($item['value'] ?? '')) : '';

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
                    'application_type_id' => $scope === 'type' ? $applicationTypeId : null,
                ],
                [
                    'value' => $value === '' ? null : $value,
                    'answered_by_id' => $userId,
                ],
            );
        }

        if ($scope === 'type' && $applicationTypeId) {
            $questions = ApplicationQuestion::query()
                ->where('scope', 'type')
                ->where('application_type_id', $applicationTypeId)
                ->where('is_active', true)
                ->get();
            $answersModels = ApplicationAnswer::query()
                ->where('application_type_id', $applicationTypeId)
                ->whereIn('question_id', $questions->pluck('id'))
                ->get();

            return $this->computeFillRate($questions, $answersModels);
        }

        return $this->scopeFillRate($scope);
    }

    public function upsertService(int $applicationTypeId, array $data, ?int $userId): ItService
    {
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

        $payload['updated_by_id'] = $userId;

        return ItService::query()->updateOrCreate(
            ['application_type_id' => $applicationTypeId],
            $payload,
        );
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

            return $answer && filled(trim((string) $answer->value));
        })->count();

        return [
            'rate' => (int) round(($answered / $total) * 100),
            'answered' => $answered,
            'total' => $total,
        ];
    }

    private function formatService(?ItService $service): ?array
    {
        if (! $service) {
            return null;
        }

        return [
            'id' => $service->id,
            'exists_flag' => $service->exists_flag,
            'solution_name' => $service->solution_name,
            'editor' => $service->editor,
            'importance' => $service->importance,
            'version' => $service->version,
            'last_version' => $service->last_version,
            'sla_exists' => $service->sla_exists,
            'hosting_mode' => $service->hosting_mode,
            'users_count' => $service->users_count,
            'licenses_count' => $service->licenses_count,
            'license_type' => $service->license_type,
            'customization_level' => $service->customization_level,
            'backups' => $service->backups,
            'etp_support' => $service->etp_support,
            'archi_ho' => $service->archi_ho,
            'environment_id' => $service->environment_id,
        ];
    }
}
