<?php

namespace App\Http\Controllers\API;

use App\Models\Mission;
use App\Models\MissionResponse;
use App\Models\Recommendation;
use App\Models\RecommendationActionPlan;
use App\Models\User;
use App\Services\ActionPlanStatusService;
use App\Services\MissionRecipientResolver;
use App\Services\RecommendationStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maravel\Http\Controllers\APIController;

/**
 * @group Recommandations
 */
class RecommendationController extends APIController
{
    protected string $modelClass = Recommendation::class;

    public function __construct(
        private MissionRecipientResolver $recipientResolver,
        private RecommendationStatusService $recommendationStatusService,
        private ActionPlanStatusService $actionPlanStatusService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        if (! $this->canViewRecommendations($user)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $entityId = $request->integer('entity_id');
        if (! $entityId) {
            return $this->responseOk([]);
        }

        if ($user->profile === 'metier' && $user->metier_role === 'responsable_entite') {
            if (! in_array($entityId, $user->entity_ids, true)) {
                return $this->responseOk([]);
            }
        }

        $query = Recommendation::query()
            ->with(['mission.entities.environment', 'entities'])
            ->whereHas('entities', fn ($entityQuery) => $entityQuery->where('entities.id', $entityId))
            ->orderByDesc('recommendation_date')
            ->orderByDesc('id');

        $query->whereHas('mission', function ($missionQuery) use ($user) {
            $this->applyMissionVisibilityFilter($missionQuery, $user);
        });

        $items = $query->get()->map(fn (Recommendation $recommendation) => $this->formatHistoryItem($recommendation));

        return $this->responseOk($items);
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $recommendation = $this->findRecommendation($id);

        if (! $this->canViewRecommendation($user, $recommendation)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        return $this->responseOk($this->formatDetail($recommendation, $user));
    }

    public function close(Request $request, int $id)
    {
        $user = $request->user();
        $recommendation = $this->findRecommendation($id);

        if (! $this->canViewRecommendation($user, $recommendation)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        if (! $this->canCloseRecommendation($user, $recommendation)) {
            return $this->responseError(['message' => ['Clôture non autorisée.']], 403);
        }

        if ($recommendation->status === 'cloturee') {
            return $this->responseError(['message' => ['Cette recommandation est déjà clôturée.']], 422);
        }

        if ($recommendation->status !== 'traitee' && $recommendation->status !== 'transmis') {
            return $this->responseError(['message' => ['Seule une recommandation traitée ou transmise peut être clôturée.']], 422);
        }

        DB::transaction(function () use ($recommendation) {
            $this->actionPlanStatusService->closeForRecommendation($recommendation);
            $this->recommendationStatusService->markRecommendationClosed($recommendation);
        });

        return $this->responseOk($this->formatDetail($recommendation->fresh(), $user));
    }

    public function transmitToRegulator(Request $request, int $id)
    {
        $user = $request->user();
        $recommendation = $this->findRecommendation($id);

        if (! $this->canViewRecommendation($user, $recommendation)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        if (! $this->canTransmitToRegulator($user, $recommendation)) {
            return $this->responseError(['message' => ['Transmission non autorisée.']], 403);
        }

        $validator = Validator::make($request->all(), [
            'entries' => 'required|array|min:1',
            'entries.*.commented_at' => 'required|date',
            'entries.*.comment' => 'required|string',
            'entries.*.author_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        $entries = $validator->validated()['entries'];

        DB::transaction(function () use ($recommendation, $user, $entries) {
            foreach ($entries as $entry) {
                $recommendation->regulatorComments()->create([
                    'comment_type' => 'transmission',
                    'user_id' => $user->id,
                    'author_name' => trim((string) ($entry['author_name'] ?? '')) ?: $user->name,
                    'commented_at' => $entry['commented_at'],
                    'comment' => trim($entry['comment']),
                ]);
            }

            $recommendation->update([
                'status' => 'transmis',
                'regulator_transmitted_at' => now(),
                'regulator_transmitted_by' => $user->id,
            ]);
        });

        return $this->responseOk($this->formatDetail($recommendation->fresh(), $user));
    }

    public function regulatorQueue(Request $request)
    {
        $user = $request->user();

        if (! $this->canAccessRegulatorQueue($user)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $query = Recommendation::query()
            ->with(['mission.entities.environment', 'entities', 'regulatorTransmitter', 'regulatorComments.user'])
            ->whereNotNull('regulator_transmitted_at')
            ->orderByDesc('regulator_transmitted_at');

        $this->applyRegulatorVisibilityFilter($query, $user);

        $items = $query->get()->map(fn (Recommendation $recommendation) => $this->formatRegulatorQueueItem($recommendation, $user));

        return $this->responseOk($items);
    }

    public function appendRegulatorComments(Request $request, int $id)
    {
        $user = $request->user();
        $recommendation = $this->findRecommendation($id);

        if (! $this->canCommentAsRegulator($user, $recommendation)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $validator = Validator::make($request->all(), [
            'entries' => 'required|array|min:1',
            'entries.*.commented_at' => 'required|date',
            'entries.*.comment' => 'required|string',
            'entries.*.author_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        $entries = $validator->validated()['entries'];

        DB::transaction(function () use ($recommendation, $user, $entries) {
            foreach ($entries as $entry) {
                $recommendation->regulatorComments()->create([
                    'comment_type' => 'avis',
                    'user_id' => $user->id,
                    'author_name' => trim((string) ($entry['author_name'] ?? '')) ?: $user->name,
                    'commented_at' => $entry['commented_at'],
                    'comment' => trim($entry['comment']),
                ]);
            }
        });

        return $this->responseOk($this->formatDetail($recommendation->fresh(), $user));
    }

    public function update(Request $request, int $id)
    {
        $user = $request->user();
        $recommendation = $this->findRecommendation($id);
        $mission = $recommendation->mission;

        if (! $this->canManageRecommendation($user, $recommendation)) {
            return $this->responseError(['message' => ['Modification non autorisée pour cette recommandation.']], 403);
        }

        $validator = Validator::make($request->all(), $this->validationRules());

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        $data = $validator->validated();
        $entityIds = array_values(array_unique(array_map('intval', $data['entity_ids'])));
        $entities = \App\Models\Entity::query()->whereIn('id', $entityIds)->get();

        if ($entities->count() !== count($entityIds)) {
            return $this->responseError([
                'entity_ids' => ['Un ou plusieurs départements sont introuvables.'],
            ], 422);
        }

        $environmentError = $this->validateEntitiesEnvironment($mission, $entities);
        if ($environmentError) {
            return $environmentError;
        }

        $newAttachmentPaths = $this->storeAttachments($request, $mission->id);
        $responsibleNames = $this->recipientResolver->resolveResponsibleNames($entityIds);

        $recommendation = DB::transaction(function () use ($recommendation, $mission, $data, $entityIds, $responsibleNames, $newAttachmentPaths) {
            $existingPaths = $recommendation->attachment_paths ?? [];
            $attachmentPaths = $newAttachmentPaths
                ? array_values(array_merge($existingPaths, $newAttachmentPaths))
                : $existingPaths;

            $recommendation->update([
                'name' => array_key_exists('name', $data)
                    ? (trim((string) $data['name']) ?: null)
                    : $recommendation->name,
                'theme' => $data['theme'],
                'recommendation_date' => $data['recommendation_date'],
                'risk_level' => $data['risk_level'],
                'risk_type' => $data['risk_type'],
                'priority' => $data['priority'],
                'status' => $recommendation->status,
                'responsible_name' => $responsibleNames ?: null,
                'due_date' => $data['due_date'] ?? null,
                'recommendation_label' => $data['recommendation_label'],
                'comments' => $data['comments'] ?? null,
                'attachment_paths' => $attachmentPaths ?: null,
            ]);

            $recommendation->entities()->sync($entityIds);

            $recipients = $this->recipientResolver->resolveRecipients($entityIds);
            if ($recipients->isNotEmpty()) {
                $syncData = $recipients->mapWithKeys(fn (User $recipient) => [
                    $recipient->id => ['notified_at' => now()],
                ])->all();
                $mission->recipients()->syncWithoutDetaching($syncData);
            }

            $this->recommendationStatusService->syncMissionStatus($mission->fresh('recommendations'));

            return $recommendation->fresh(['mission.entities.environment', 'entities']);
        });

        return $this->responseOk($this->formatDetail($recommendation, $user));
    }

    public function syncFollowUps(Request $request, int $id)
    {
        $user = $request->user();
        $recommendation = $this->findRecommendation($id);

        if (! $this->canFollowUpRecommendation($user, $recommendation)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $validator = Validator::make($request->all(), [
            'entries' => 'required|array|min:1',
            'entries.*.commented_at' => 'required|date',
            'entries.*.comment' => 'required|string',
            'entries.*.author_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        $entries = $validator->validated()['entries'];

        DB::transaction(function () use ($recommendation, $user, $entries) {
            foreach ($entries as $entry) {
                $recommendation->followUps()->create([
                    'user_id' => $user->id,
                    'author_name' => trim((string) ($entry['author_name'] ?? '')) ?: $user->name,
                    'commented_at' => $entry['commented_at'],
                    'comment' => trim($entry['comment']),
                ]);
            }
        });

        $recommendation->load(['mission.entities.environment', 'entities', 'followUps.user']);

        return $this->responseOk($this->formatDetail($recommendation, $user));
    }

    public function syncActionPlans(Request $request, int $id)
    {
        $user = $request->user();
        $recommendation = $this->findRecommendation($id);

        if (! $this->canManageActionPlans($user, $recommendation)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        if (! $this->canSyncActionPlans($user, $recommendation)) {
            return $this->responseError(['message' => ['Modification impossible tant que le membre n\'a pas soumis sa réponse.']], 422);
        }

        $validator = Validator::make($request->all(), [
            'lines' => 'required|array|min:1',
            'lines.*.line_number' => 'required|integer|min:1',
            'lines.*.action_plan' => 'required|string',
            'lines.*.responsible_name' => 'nullable|string|max:255',
            'lines.*.due_date' => 'nullable|date',
            'lines.*.is_waiting' => 'sometimes|boolean',
            'lines.*.comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        $lines = $validator->validated()['lines'];
        $ownerId = $this->resolveActionPlanOwnerId($user, $recommendation);

        if (! $ownerId) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        DB::transaction(function () use ($recommendation, $ownerId, $lines) {
            $existing = $recommendation->actionPlans()
                ->where('user_id', $ownerId)
                ->get()
                ->keyBy('line_number');

            $keptIds = [];

            foreach ($lines as $line) {
                $lineNumber = (int) $line['line_number'];
                $payload = [
                    'action_plan' => trim($line['action_plan']),
                    'responsible_name' => $line['responsible_name'] ?? null,
                    'due_date' => $line['due_date'] ?? null,
                    'is_waiting' => (bool) ($line['is_waiting'] ?? false),
                    'comment' => isset($line['comment']) ? trim($line['comment']) : null,
                    'transmission_date' => Carbon::today()->toDateString(),
                ];

                if ($existing->has($lineNumber)) {
                    $plan = $existing->get($lineNumber);
                    $plan->update($payload);
                } else {
                    $plan = $recommendation->actionPlans()->create([
                        ...$payload,
                        'user_id' => $ownerId,
                        'line_number' => $lineNumber,
                        'responsible_name' => $payload['responsible_name'] ?? User::query()->find($ownerId)?->name,
                    ]);
                }

                $keptIds[] = $plan->id;
                $this->actionPlanStatusService->syncStatus($plan);
            }

            $recommendation->actionPlans()
                ->where('user_id', $ownerId)
                ->whereNotIn('id', $keptIds)
                ->delete();
        });

        $recommendation->load(['mission.entities.environment', 'entities', 'followUps.user', 'actionPlans.user', 'actionPlans.comments.user']);

        return $this->responseOk($this->formatDetail($recommendation, $user));
    }

    public function showActionPlan(Request $request, int $planId)
    {
        $user = $request->user();
        $plan = $this->findActionPlan($planId);

        if (! $this->canViewRecommendation($user, $plan->recommendation)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $plan->load(['user', 'comments.user']);

        return $this->responseOk($this->formatActionPlan($plan, $user));
    }

    public function storeActionPlan(Request $request, int $id)
    {
        $user = $request->user();
        $recommendation = $this->findRecommendation($id);

        if (! $this->canManageActionPlans($user, $recommendation)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        if (! $this->canSyncActionPlans($user, $recommendation)) {
            return $this->responseError(['message' => ['Modification impossible tant que le membre n\'a pas soumis sa réponse.']], 422);
        }

        $ownerId = $this->resolveActionPlanOwnerId($user, $recommendation);

        if (! $ownerId) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $data = $this->validateActionPlanPayload($request);

        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
        }

        $plan = $recommendation->actionPlans()->create([
            ...$data,
            'user_id' => $ownerId,
            'responsible_name' => $data['responsible_name'] ?? User::query()->find($ownerId)?->name,
            'transmission_date' => Carbon::today()->toDateString(),
        ]);

        $this->actionPlanStatusService->syncStatus($plan);
        $plan->load(['user', 'comments.user']);

        return $this->responseOk($this->formatActionPlan($plan->refresh(), $user), 201);
    }

    public function updateActionPlan(Request $request, int $planId)
    {
        $user = $request->user();
        $plan = $this->findActionPlan($planId);
        $recommendation = $plan->recommendation;

        if (! $this->canManageActionPlans($user, $recommendation)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        if (! $this->canSyncActionPlans($user, $recommendation)) {
            return $this->responseError(['message' => ['Modification impossible tant que le membre n\'a pas soumis sa réponse.']], 422);
        }

        $ownerId = $this->resolveActionPlanOwnerId($user, $recommendation);

        if (! $ownerId || (int) $plan->user_id !== (int) $ownerId) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $data = $this->validateActionPlanPayload($request);

        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
        }

        $plan->update([
            ...$data,
            'transmission_date' => Carbon::today()->toDateString(),
        ]);

        $this->actionPlanStatusService->syncStatus($plan);
        $plan->load(['user', 'comments.user']);

        return $this->responseOk($this->formatActionPlan($plan->refresh(), $user));
    }

    public function destroyActionPlan(Request $request, int $planId)
    {
        $user = $request->user();
        $plan = $this->findActionPlan($planId);
        $recommendation = $plan->recommendation;

        if (! $this->canManageActionPlans($user, $recommendation)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        if (! $this->canSyncActionPlans($user, $recommendation)) {
            return $this->responseError(['message' => ['Suppression impossible tant que le membre n\'a pas soumis sa réponse.']], 422);
        }

        $ownerId = $this->resolveActionPlanOwnerId($user, $recommendation);

        if (! $ownerId || (int) $plan->user_id !== (int) $ownerId) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $plan->delete();

        return $this->responseOk(['message' => ['Ligne supprimée.']]);
    }

    public function uploadActionPlanAttachments(Request $request, int $planId)
    {
        $user = $request->user();
        $plan = $this->findActionPlan($planId);
        $recommendation = $plan->recommendation;

        if (! $this->canManageActionPlans($user, $recommendation)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        if (! $this->canEditActionPlanAttachments($user, $plan, $recommendation)) {
            return $this->responseError(['message' => ['Les pièces justificatives ne peuvent plus être modifiées.']], 422);
        }

        $validator = Validator::make($request->all(), [
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
            'remove_attachments' => 'nullable|array',
            'remove_attachments.*' => 'string',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        $data = $validator->validated();
        $paths = $plan->attachment_paths ?? [];

        foreach ($data['remove_attachments'] ?? [] as $path) {
            if (in_array($path, $paths, true)) {
                Storage::disk('local')->delete($path);
                $paths = array_values(array_filter($paths, fn ($p) => $p !== $path));
            }
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $paths[] = $file->store("action-plans/{$plan->id}", 'local');
            }
        }

        $plan->update(['attachment_paths' => $paths]);
        $plan->load(['user', 'comments.user']);

        return $this->responseOk($this->formatActionPlan($plan->refresh(), $user));
    }

    public function appendActionPlanComments(Request $request, int $planId)
    {
        $user = $request->user();
        $plan = RecommendationActionPlan::query()
            ->with(['recommendation.mission', 'recommendation.entities'])
            ->findOrFail($planId);

        if (! $this->canManageActionPlans($user, $plan->recommendation)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $validator = Validator::make($request->all(), [
            'entries' => 'required|array|min:1',
            'entries.*.commented_at' => 'required|date',
            'entries.*.comment' => 'required|string',
            'entries.*.author_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        $entries = $validator->validated()['entries'];

        DB::transaction(function () use ($plan, $user, $entries) {
            foreach ($entries as $entry) {
                $plan->comments()->create([
                    'user_id' => $user->id,
                    'author_name' => trim((string) ($entry['author_name'] ?? '')) ?: $user->name,
                    'commented_at' => $entry['commented_at'],
                    'comment' => trim($entry['comment']),
                ]);
            }
        });

        $recommendation = $plan->recommendation;
        $recommendation->load(['mission.entities.environment', 'entities', 'followUps.user', 'actionPlans.user', 'actionPlans.comments.user']);

        return $this->responseOk($this->formatDetail($recommendation, $user));
    }

    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $recommendation = $this->findRecommendation($id);
        $mission = $recommendation->mission;

        if (! $this->canManageRecommendation($user, $recommendation)) {
            return $this->responseError(['message' => ['Suppression non autorisée pour cette recommandation.']], 403);
        }

        DB::transaction(function () use ($recommendation, $mission) {
            $recommendation->entities()->detach();
            $recommendation->delete();
            $this->recommendationStatusService->syncMissionStatus($mission->fresh('recommendations'));
        });

        return $this->responseOk([
            'mission_id' => $mission->id,
            'deleted_id' => $id,
        ]);
    }

    private function findRecommendation(int $id): Recommendation
    {
        return Recommendation::query()
            ->with([
                'mission.entities.environment',
                'mission.responses',
                'mission.recipients',
                'entities',
                'followUps.user',
                'actionPlans.user',
                'regulatorComments.user',
                'regulatorTransmitter',
            ])
            ->findOrFail($id);
    }

    private function findActionPlan(int $planId): RecommendationActionPlan
    {
        return RecommendationActionPlan::query()
            ->with(['recommendation.mission.recipients', 'recommendation.entities'])
            ->findOrFail($planId);
    }

    private function validateActionPlanPayload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'line_number' => 'required|integer|min:1',
            'action_plan' => 'required|string',
            'responsible_name' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'is_waiting' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        $data = $validator->validated();

        return [
            'line_number' => (int) $data['line_number'],
            'action_plan' => trim($data['action_plan']),
            'responsible_name' => $data['responsible_name'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'is_waiting' => (bool) ($data['is_waiting'] ?? false),
        ];
    }

    private function validationRules(): array
    {
        return [
            'entity_ids' => 'required|array|min:1',
            'entity_ids.*' => 'integer|exists:entities,id',
            'name' => 'nullable|string|max:255',
            'theme' => 'required|string|max:5000',
            'recommendation_label' => 'required|string|max:5000',
            'risk_type' => 'required|string|max:5000',
            'risk_level' => 'required|in:faible,moyen,eleve,critique',
            'priority' => 'required|in:basse,moyenne,haute',
            'status' => 'nullable|in:emise,en_cours,traitee,transmis,cloturee',
            'recommendation_date' => 'required|date',
            'due_date' => 'nullable|date',
            'comments' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
        ];
    }

    private function validateEntitiesEnvironment(Mission $mission, $entities)
    {
        $missionEnvironmentIds = $mission->entities
            ->pluck('environment_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->filter()
            ->values();

        foreach ($entities as $entity) {
            if ($missionEnvironmentIds->isNotEmpty() && ! $missionEnvironmentIds->contains((int) $entity->environment_id)) {
                return $this->responseError([
                    'entity_ids' => ['Les départements doivent appartenir au même environnement que la mission.'],
                ], 422);
            }
        }

        return null;
    }

    private function storeAttachments(Request $request, int $missionId): array
    {
        $paths = [];

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $paths[] = $file->store("mission-recommendations/{$missionId}", 'local');
            }
        }

        return $paths;
    }

    private function canFollowUpRecommendation(User $user, Recommendation $recommendation): bool
    {
        return $this->canManageActionPlans($user, $recommendation);
    }

    private function canManageActionPlans(User $user, Recommendation $recommendation): bool
    {
        if ($user->isSuperAdmin() || in_array($user->profile, ['controle', 'audit'], true)) {
            return $this->canViewRecommendation($user, $recommendation);
        }

        if ($this->resolveActionPlanOwnerId($user, $recommendation) !== null) {
            return true;
        }

        return false;
    }

    private function resolveActionPlanOwnerId(User $user, Recommendation $recommendation): ?int
    {
        if ($user->profile === 'metier' && $user->metier_role === 'responsable_entite') {
            $entityIds = $user->entity_ids;

            if ($entityIds === []) {
                return null;
            }

            if (! $recommendation->entities()->whereIn('entities.id', $entityIds)->exists()) {
                return null;
            }

            if (! $recommendation->mission
                ->recipients()
                ->where('users.id', $user->id)
                ->exists()) {
                return null;
            }

            return (int) $user->id;
        }

        if ($user->profile === 'metier' && $user->metier_role === 'agent') {
            $response = MissionResponse::query()
                ->where('mission_id', $recommendation->mission_id)
                ->where('assigned_agent_id', $user->id)
                ->where('response_type', 'action')
                ->where('workflow_status', 'en_saisie')
                ->first();

            if (! $response) {
                return null;
            }

            $entityIds = $user->entity_ids;

            if ($entityIds === [] || ! $recommendation->entities()->whereIn('entities.id', $entityIds)->exists()) {
                return null;
            }

            return (int) $response->responsable_id;
        }

        return null;
    }

    private function canSyncActionPlans(User $user, Recommendation $recommendation): bool
    {
        if ($this->resolveActionPlanOwnerId($user, $recommendation) === null) {
            return false;
        }

        if ($user->profile === 'metier' && $user->metier_role === 'responsable_entite') {
            return ! MissionResponse::query()
                ->where('mission_id', $recommendation->mission_id)
                ->where('responsable_id', $user->id)
                ->where('response_type', 'action')
                ->where('handling_mode', 'agent')
                ->where('workflow_status', 'en_saisie')
                ->exists();
        }

        return true;
    }

    private function canEditActionPlanAttachments(User $user, RecommendationActionPlan $plan, Recommendation $recommendation): bool
    {
        if (! $this->canSyncActionPlans($user, $recommendation)) {
            return false;
        }

        $ownerId = $this->resolveActionPlanOwnerId($user, $recommendation);

        if (! $ownerId || (int) $plan->user_id !== (int) $ownerId) {
            return false;
        }

        return true;
    }

    private function canManageRecommendation(User $user, Recommendation $recommendation): bool
    {
        $mission = $recommendation->mission;

        if ($mission->responses->isNotEmpty()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return in_array($user->profile, ['controle', 'audit'], true)
            && (int) $mission->created_by === (int) $user->id;
    }

    private function canCloseRecommendation(User $user, Recommendation $recommendation): bool
    {
        if (! in_array($recommendation->status, ['traitee', 'transmis'], true)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! in_array($user->profile, ['controle', 'audit'], true)) {
            return false;
        }

        $missionQuery = Mission::query()->where('id', $recommendation->mission_id);
        $this->applyMissionVisibilityFilter($missionQuery, $user);

        return $missionQuery->exists();
    }

    private function canTransmitToRegulator(User $user, Recommendation $recommendation): bool
    {
        if ($recommendation->status === 'cloturee') {
            return false;
        }

        if (! in_array($recommendation->status, ['traitee', 'transmis'], true)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! in_array($user->profile, ['controle', 'audit'], true)) {
            return false;
        }

        return $this->canViewRecommendation($user, $recommendation);
    }

    private function canAccessRegulatorQueue(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isRegulateur();
    }

    private function canViewAsRegulator(User $user, Recommendation $recommendation): bool
    {
        if (! $this->canAccessRegulatorQueue($user)) {
            return false;
        }

        if (! $recommendation->regulator_transmitted_at) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $this->regulatorRecommendationVisible($user, $recommendation);
    }

    private function canCommentAsRegulator(User $user, Recommendation $recommendation): bool
    {
        return $this->canViewAsRegulator($user, $recommendation);
    }

    private function canViewRegulatorComments(User $user, Recommendation $recommendation): bool
    {
        if (! $recommendation->regulator_transmitted_at) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isRegulateur()) {
            return $this->canViewAsRegulator($user, $recommendation);
        }

        if (in_array($user->profile, ['controle', 'audit'], true)) {
            $missionQuery = Mission::query()->where('id', $recommendation->mission_id);
            $this->applyMissionVisibilityFilter($missionQuery, $user);

            return $missionQuery->exists();
        }

        return false;
    }

    private function regulatorRecommendationVisible(User $user, Recommendation $recommendation): bool
    {
        $environmentIds = $user->environment_ids ?? [];
        $entityIds = $user->entity_ids ?? [];

        if ($environmentIds !== []) {
            return $recommendation->mission
                ->entities
                ->contains(fn ($entity) => in_array((int) $entity->environment_id, $environmentIds, true));
        }

        if ($entityIds !== []) {
            return $recommendation->entities()->whereIn('entities.id', $entityIds)->exists();
        }

        return true;
    }

    private function applyRegulatorVisibilityFilter($query, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $environmentIds = $user->environment_ids ?? [];
        $entityIds = $user->entity_ids ?? [];

        if ($environmentIds !== []) {
            $query->whereHas('mission.entities', fn ($entityQuery) => $entityQuery->whereIn('environment_id', $environmentIds));

            return;
        }

        if ($entityIds !== []) {
            $query->whereHas('entities', fn ($entityQuery) => $entityQuery->whereIn('entities.id', $entityIds));
        }
    }

    private function formatRegulatorQueueItem(Recommendation $recommendation, ?User $viewer = null): array
    {
        $mission = $recommendation->mission;
        $entityNames = $recommendation->entities->pluck('name')->filter()->values();

        return [
            'id' => $recommendation->id,
            'mission_id' => $mission?->id,
            'mission_reference' => $mission?->reference,
            'mission_type_fr' => $mission?->mission_type_fr,
            'auditor' => $mission?->auditor,
            'reference' => $recommendation->reference,
            'theme' => $recommendation->theme,
            'status_fr' => $recommendation->status_fr,
            'entity_name' => $entityNames->isNotEmpty() ? $entityNames->implode(', ') : null,
            'regulator_transmitted_at' => $recommendation->regulator_transmitted_at?->toIso8601String(),
            'regulator_transmitted_by_name' => $recommendation->regulatorTransmitter?->name,
            'comments_count' => $recommendation->regulatorComments->count(),
        ];
    }

    private function formatRegulatorComments(Recommendation $recommendation): array
    {
        $comments = $recommendation->relationLoaded('regulatorComments')
            ? $recommendation->regulatorComments
            : $recommendation->regulatorComments()->with('user')->get();

        return $comments
            ->sortByDesc(fn ($item) => sprintf(
                '%s-%010d',
                $item->commented_at?->format('Y-m-d') ?? '',
                $item->id,
            ))
            ->map(fn ($item) => [
                'id' => $item->id,
                'comment_type' => $item->comment_type ?? 'avis',
                'comment_type_fr' => ($item->comment_type ?? 'avis') === 'transmission'
                    ? 'Transmission'
                    : 'Avis régulateur',
                'commented_at' => $item->commented_at?->format('Y-m-d'),
                'comment' => $item->comment,
                'author_name' => $item->author_name ?: $item->user?->name,
            ])->values()->all();
    }

    private function formatRegulatorCommentsByType(Recommendation $recommendation, string $type): array
    {
        return collect($this->formatRegulatorComments($recommendation))
            ->filter(fn (array $item) => ($item['comment_type'] ?? 'avis') === $type)
            ->values()
            ->all();
    }

    private function canViewRecommendation(User $user, Recommendation $recommendation): bool
    {
        if ($this->canViewAsRegulator($user, $recommendation)) {
            return true;
        }

        if (! $this->canViewRecommendations($user)) {
            return false;
        }

        $missionQuery = Mission::query()->where('id', $recommendation->mission_id);
        $this->applyMissionVisibilityFilter($missionQuery, $user);

        return $missionQuery->exists();
    }

    private function canViewRecommendations(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (in_array($user->profile, ['controle', 'audit'], true)) {
            return true;
        }

        if ($user->profile === 'metier' && $user->metier_role === 'responsable_entite') {
            return true;
        }

        if ($user->profile === 'metier' && $user->metier_role === 'agent') {
            return MissionResponse::query()
                ->where('assigned_agent_id', $user->id)
                ->exists();
        }

        return false;
    }

    private function applyMissionVisibilityFilter($query, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        if (in_array($user->profile, ['controle', 'audit'], true)) {
            $environmentIds = $user->environment_ids;

            if (! empty($environmentIds)) {
                $query->whereHas('entities', function ($entityQuery) use ($environmentIds) {
                    $entityQuery->whereIn('environment_id', $environmentIds);
                });
            }

            return;
        }

        if ($user->profile === 'metier' && $user->metier_role === 'responsable_entite') {
            $query->whereHas('recipients', function ($recipientQuery) use ($user) {
                $recipientQuery->where('users.id', $user->id);
            });

            return;
        }

        if ($user->profile === 'metier' && $user->metier_role === 'agent') {
            $query->whereHas('responses', function ($responseQuery) use ($user) {
                $responseQuery->where('assigned_agent_id', $user->id);
            });
        }
    }

    private function formatDetail(Recommendation $recommendation, ?User $viewer = null): array
    {
        $mission = $recommendation->mission;
        $entityNames = $recommendation->entities->pluck('name')->filter()->values();
        $canManage = $viewer && $this->canManageRecommendation($viewer, $recommendation);

        return [
            'id' => $recommendation->id,
            'mission_id' => $mission?->id,
            'mission_reference' => $mission?->reference,
            'mission_type_fr' => $mission?->mission_type_fr,
            'mission_auditor' => $mission?->auditor,
            'mission_start_date' => $mission?->start_date?->format('Y-m-d'),
            'mission_end_date' => $mission?->end_date?->format('Y-m-d'),
            'reference' => $recommendation->reference,
            'name' => $recommendation->name,
            'theme' => $recommendation->theme,
            'recommendation_date' => $recommendation->recommendation_date?->format('Y-m-d'),
            'recommendation_label' => $recommendation->recommendation_label,
            'recommendation_details' => $recommendation->recommendation_details,
            'responsible_name' => $recommendation->responsible_name,
            'due_date' => $recommendation->due_date?->format('Y-m-d'),
            'risk_level' => $recommendation->risk_level,
            'risk_level_fr' => $recommendation->risk_level_fr,
            'risk_type' => $recommendation->risk_type,
            'priority' => $recommendation->priority,
            'priority_fr' => $recommendation->priority_fr,
            'status' => $recommendation->status,
            'status_fr' => $recommendation->status_fr,
            'comments' => $recommendation->comments,
            'attachment_paths' => $recommendation->attachment_paths ?? [],
            'entity_ids' => $recommendation->entities->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'entity_id' => $recommendation->entities->first()?->id,
            'entity_name' => $entityNames->isNotEmpty() ? $entityNames->implode(', ') : null,
            'entities' => $recommendation->entities->map(fn ($entity) => [
                'id' => $entity->id,
                'name' => $entity->name,
            ])->values()->all(),
            'environment_id' => $mission?->entities->first()?->environment_id,
            'can_edit' => $canManage,
            'can_delete' => $canManage,
            'can_close' => $viewer ? $this->canCloseRecommendation($viewer, $recommendation) : false,
            'can_transmit_regulator' => $viewer ? $this->canTransmitToRegulator($viewer, $recommendation) : false,
            'regulator_transmitted_at' => $recommendation->regulator_transmitted_at?->toIso8601String(),
            'regulator_transmitted_by_name' => $recommendation->regulatorTransmitter?->name,
            'can_comment_regulator' => $viewer ? $this->canCommentAsRegulator($viewer, $recommendation) : false,
            'can_view_regulator_comments' => $viewer ? $this->canViewRegulatorComments($viewer, $recommendation) : false,
            'regulator_comments' => $viewer && $this->canViewRegulatorComments($viewer, $recommendation)
                ? $this->formatRegulatorComments($recommendation)
                : [],
            'regulator_transmission_comments' => $viewer && $this->canViewRegulatorComments($viewer, $recommendation)
                ? $this->formatRegulatorCommentsByType($recommendation, 'transmission')
                : [],
            'regulator_avis_comments' => $viewer && $this->canViewRegulatorComments($viewer, $recommendation)
                ? $this->formatRegulatorCommentsByType($recommendation, 'avis')
                : [],
            'follow_ups' => $this->formatFollowUps($recommendation, $viewer),
            'action_plans' => $this->formatActionPlans($recommendation, $viewer),
        ];
    }

    private function formatActionPlans(Recommendation $recommendation, ?User $viewer = null): array
    {
        $plans = $recommendation->relationLoaded('actionPlans')
            ? $recommendation->actionPlans
            : $recommendation->actionPlans()->with(['user', 'comments.user'])->get();

        return $plans->map(fn ($plan) => $this->formatActionPlan($plan, $viewer))->values()->all();
    }

    private function formatActionPlan($plan, ?User $viewer = null): array
    {
        $status = $this->actionPlanStatusService->resolveStatus($plan);
        $recommendation = $plan->relationLoaded('recommendation')
            ? $plan->recommendation
            : $plan->recommendation()->first();
        $ownerId = $viewer && $recommendation ? $this->resolveActionPlanOwnerId($viewer, $recommendation) : null;
        $isOwnerPlan = $ownerId && (int) $plan->user_id === (int) $ownerId;
        $canSync = $viewer && $recommendation && $this->canSyncActionPlans($viewer, $recommendation);

        return [
            'id' => $plan->id,
            'line_number' => $plan->line_number,
            'action_plan' => $plan->action_plan,
            'responsible_name' => $plan->responsible_name,
            'due_date' => $plan->due_date?->format('Y-m-d'),
            'status' => $status,
            'status_fr' => collect(config('mission-parametrage.action_plan_statuses', []))
                ->firstWhere('value', $status)['label'] ?? $status,
            'status_color' => $this->actionPlanStatusService->statusColor($status),
            'is_waiting' => $plan->is_waiting,
            'transmission_date' => $plan->transmission_date?->format('Y-m-d'),
            'delay_days' => $this->actionPlanStatusService->delayDays($plan),
            'author_name' => $plan->user?->name,
            'user_id' => $plan->user_id,
            'comments' => $this->formatActionPlanComments($plan),
            'attachment_paths' => $plan->attachment_paths ?? [],
            'can_view' => true,
            'can_edit' => (bool) ($canSync && $isOwnerPlan),
            'can_edit_attachments' => $viewer && $recommendation
                ? $this->canEditActionPlanAttachments($viewer, $plan, $recommendation)
                : false,
            'can_delete' => (bool) ($canSync && $isOwnerPlan),
        ];
    }

    private function formatActionPlanComments($plan): array
    {
        $comments = $plan->relationLoaded('comments')
            ? $plan->comments
            : $plan->comments()->with('user')->get();

        return $comments
            ->sortByDesc(fn ($item) => sprintf(
                '%s-%010d',
                $item->commented_at?->format('Y-m-d') ?? '',
                $item->id,
            ))
            ->map(fn ($item) => [
                'id' => $item->id,
                'commented_at' => $item->commented_at?->format('Y-m-d'),
                'comment' => $item->comment,
                'author_name' => $item->author_name ?: $item->user?->name,
            ])->values()->all();
    }

    private function formatFollowUps(Recommendation $recommendation, ?User $viewer = null): array
    {
        $followUps = $recommendation->relationLoaded('followUps')
            ? $recommendation->followUps
            : $recommendation->followUps()->with('user')->get();

        return $followUps
            ->sortByDesc(fn ($item) => sprintf(
                '%s-%010d',
                $item->commented_at?->format('Y-m-d') ?? '',
                $item->id,
            ))
            ->map(fn ($item) => [
                'id' => $item->id,
                'commented_at' => $item->commented_at?->format('Y-m-d'),
                'comment' => $item->comment,
                'author_name' => $item->author_name ?: $item->user?->name,
            ])->values()->all();
    }

    private function formatHistoryItem(Recommendation $recommendation): array
    {
        $mission = $recommendation->mission;
        $entityNames = $recommendation->entities->pluck('name')->filter()->values();

        return [
            'id' => $recommendation->id,
            'reference' => $recommendation->reference,
            'name' => $recommendation->name,
            'mission_id' => $mission?->id,
            'mission_reference' => $mission?->reference,
            'mission_type_fr' => $mission?->mission_type_fr,
            'theme' => $recommendation->theme,
            'recommendation_label' => $recommendation->recommendation_label,
            'recommendation_date' => $recommendation->recommendation_date?->format('Y-m-d'),
            'due_date' => $recommendation->due_date?->format('Y-m-d'),
            'risk_level_fr' => $recommendation->risk_level_fr,
            'priority_fr' => $recommendation->priority_fr,
            'status' => $recommendation->status,
            'status_fr' => $recommendation->status_fr,
            'responsible_name' => $recommendation->responsible_name,
            'entity_name' => $entityNames->implode(', ') ?: null,
            'environment_label' => $mission?->entities
                ->map(fn ($entity) => $entity->environment?->name)
                ->filter()
                ->unique()
                ->implode(', ') ?: '—',
        ];
    }
}
