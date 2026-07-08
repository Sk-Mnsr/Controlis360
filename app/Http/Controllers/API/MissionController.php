<?php

namespace App\Http\Controllers\API;

use App\Mail\MissionAssignedMail;
use App\Models\Mission;
use App\Models\MissionResponse;
use App\Models\Recommendation;
use App\Models\User;
use App\Services\ActionPlanStatusService;
use App\Services\MissionImportService;
use App\Services\MissionRecipientResolver;
use App\Services\RecommendationStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Maravel\Http\Controllers\APIController;

/**
 * @group Missions
 */
class MissionController extends APIController
{
    protected string $modelClass = Mission::class;

    public function __construct(
        private MissionResponseController $responseController,
        private MissionImportService $importService,
        private MissionRecipientResolver $recipientResolver,
        private RecommendationStatusService $recommendationStatusService,
        private ActionPlanStatusService $actionPlanStatusService,
    ) {}

    private const MISSION_TYPES = [
        'audit_interne',
        'audit_externe',
        'controle_permanent',
        'inspection',
        'cac',
        'regulateur',
    ];

    private const STATUSES = [
        'ouvert',
        'ferme',
    ];

    private const RECOMMENDATION_STATUSES = [
        'emise',
        'en_cours',
        'traitee',
        'transmis',
        'cloturee',
    ];

    public function index(Request $request)
    {
        $user = $request->user();

        if (! $this->canViewMissions($user)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $query = Mission::query()
            ->with(['recommendations.entities', 'entities.environment', 'creator', 'recipients', 'responses.assignedAgent'])
            ->orderByDesc('created_at');

        $this->applyVisibilityFilter($query, $user);

        $missions = $query->get()->map(fn (Mission $mission) => $this->formatMissionListItem($mission, $user));

        return $this->responseOk($missions);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (! $this->canCreateMission($user)) {
            return $this->responseError(['message' => ['Seuls les profils Contrôle et Audit peuvent créer une mission.']], 403);
        }

        $validator = Validator::make($request->all(), $this->missionStoreValidationRules($user));

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        $data = $validator->validated();
        $entityIds = array_values(array_unique(array_map('intval', $data['entity_ids'])));

        $invalid = $this->entitiesOutsideEnvironment($entityIds, (int) $data['environment_id']);
        if ($invalid) {
            return $this->responseError([
                'entity_ids' => ['Les entités sélectionnées doivent appartenir à l\'environnement choisi.'],
            ], 422);
        }

        $reportPaths = $this->storeReportAttachments($request);

        $mission = $this->importService->createMission([
            'reference' => $data['reference'],
            'mission_type' => $data['mission_type'],
            'entity_ids' => $entityIds,
            'auditor' => $data['auditor'],
            'issue_date' => $data['issue_date'] ?? now()->toDateString(),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'] ?? 'ouvert',
            'report_reference' => $data['report_reference'] ?? $this->reportLabelFromFiles($request, $reportPaths),
            'report_attachment_paths' => $reportPaths,
        ], $user);

        $payload = $this->formatMissionListItem($mission, $user);
        $payload['notified_recipients'] = $mission->recipients->map(fn (User $recipient) => [
            'id' => $recipient->id,
            'name' => $recipient->name,
            'email' => $recipient->email,
        ])->values();

        if ($this->recipientResolver->resolveRecipients($entityIds)->isEmpty()) {
            $payload['warning'] = 'Aucun responsable d\'entité rattaché aux entités sélectionnées. Vérifiez le profil Métier / Responsable entité et le rattachement à l\'entité.';
        }

        return $this->responseOk($payload, 201);
    }

    public function importTemplate(Request $request)
    {
        $user = $request->user();

        if (! $this->canCreateMission($user)) {
            return $this->responseError(['message' => ['Seuls les profils Contrôle et Audit peuvent importer des missions.']], 403);
        }

        return $this->importService->downloadTemplate();
    }

    public function import(Request $request)
    {
        $user = $request->user();

        if (! $this->canCreateMission($user)) {
            return $this->responseError(['message' => ['Seuls les profils Contrôle et Audit peuvent importer des missions.']], 403);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        try {
            $result = $this->importService->import($request->file('file'), $user);

            return $this->responseOk($result);
        } catch (\InvalidArgumentException $e) {
            return $this->responseError(['message' => [$e->getMessage()]], 422);
        }
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();

        if (! $this->canViewMissions($user)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $mission = Mission::query()
            ->with(['recommendations.entities', 'recommendations.followUps.user', 'recommendations.actionPlans.user', 'recommendations.actionPlans.comments.user', 'entities.environment', 'creator', 'recipients', 'responses.assignedAgent'])
            ->findOrFail($id);

        if (! $this->canViewMission($user, $mission)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        return $this->responseOk($this->formatMissionDetail($mission, $user));
    }

    public function takeCharge(Request $request, int $id)
    {
        $user = $request->user();

        if ($user->profile !== 'metier' || $user->metier_role !== 'responsable_entite') {
            return $this->responseError(['message' => ['Seul un responsable d\'entité peut prendre en charge.']], 403);
        }

        $mission = Mission::query()
            ->with(['recommendations.entities', 'recipients'])
            ->whereHas('recipients', fn ($query) => $query->where('users.id', $user->id))
            ->find($id);

        if (! $mission) {
            return $this->responseError(['message' => ['Mission introuvable ou accès refusé.']], 403);
        }

        $updated = $this->recommendationStatusService->markOwnerRecommendationsInProgress($mission, $user);

        return $this->responseOk([
            'mission_id' => $mission->id,
            'updated_count' => $updated,
            'message' => [$updated > 0
                ? 'Recommandations prises en charge.'
                : 'Mission déjà prise en charge.'],
        ]);
    }

    public function storeRecommendation(Request $request, int $id)
    {
        $user = $request->user();
        $mission = Mission::query()
            ->with(['recommendations', 'entities', 'recipients'])
            ->findOrFail($id);

        if (! $this->canAddRecommendationToMission($user, $mission)) {
            return $this->responseError(['message' => ['Ajout de recommandation non autorisé.']], 403);
        }

        $validator = Validator::make($request->all(), [
            'entity_ids' => 'required|array|min:1',
            'entity_ids.*' => 'integer|exists:entities,id',
            'reference' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'theme' => 'required|string|max:5000',
            'recommendation_label' => 'required|string|max:5000',
            'risk_type' => 'required|string|max:5000',
            'risk_level' => 'required|in:faible,moyen,eleve,critique',
            'priority' => 'required|in:basse,moyenne,haute',
            'status' => 'nullable|in:'.implode(',', self::RECOMMENDATION_STATUSES),
            'recommendation_date' => 'required|date',
            'due_date' => 'nullable|date',
            'comments' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
        ]);

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

        $attachmentPaths = $this->storeRecommendationAttachments($request, $mission->id);
        $responsibleNames = $this->recipientResolver->resolveResponsibleNames($entityIds);

        $mission = DB::transaction(function () use ($mission, $data, $responsibleNames, $attachmentPaths, $entityIds) {
            $recommendation = $mission->recommendations()->create([
                'reference' => isset($data['reference']) && trim((string) $data['reference']) !== ''
                    ? trim((string) $data['reference'])
                    : $this->generateRecommendationReference($mission),
                'name' => isset($data['name']) ? trim((string) $data['name']) ?: null : null,
                'theme' => $data['theme'],
                'recommendation_date' => $data['recommendation_date'],
                'risk_level' => $data['risk_level'],
                'risk_type' => $data['risk_type'],
                'priority' => $data['priority'],
                'status' => $data['status'] ?? 'emise',
                'responsible_name' => $responsibleNames ?: null,
                'due_date' => $data['due_date'] ?? null,
                'recommendation_label' => $data['recommendation_label'],
                'comments' => $data['comments'] ?? null,
                'attachment_paths' => $attachmentPaths ?: null,
            ]);

            $recommendation->entities()->sync($entityIds);

            $this->recommendationStatusService->syncMissionStatus($mission->fresh('recommendations'));

            $recipients = $this->recipientResolver->resolveRecipients($entityIds);
            if ($recipients->isNotEmpty()) {
                $syncData = $recipients->mapWithKeys(fn (User $recipient) => [
                    $recipient->id => ['notified_at' => now()],
                ])->all();
                $mission->recipients()->syncWithoutDetaching($syncData);
            }

            return $mission->load(['recommendations.entities', 'entities', 'creator', 'recipients']);
        });

        foreach ($mission->recipients as $recipient) {
            Mail::to($recipient->email)->send(new MissionAssignedMail($mission, $recipient, $user));
        }

        $payload = $this->formatMissionDetail($mission, $user);
        if ($mission->recipients->isEmpty()) {
            $payload['warning'] = 'Recommandation créée mais aucun responsable d\'entité trouvé pour notification.';
        }

        return $this->responseOk($payload, 201);
    }

    public function update(Request $request, int $id)
    {
        $user = $request->user();
        $mission = Mission::query()
            ->with(['recommendations', 'entities', 'responses', 'recipients'])
            ->findOrFail($id);

        if (! $this->canManageMission($user, $mission)) {
            return $this->responseError(['message' => ['Modification non autorisée pour cette mission.']], 403);
        }

        $validator = Validator::make($request->all(), $this->missionValidationRules($user));

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        $data = $validator->validated();
        $entityIds = array_values(array_unique(array_map('intval', $data['entity_ids'])));

        DB::transaction(function () use ($mission, $data, $entityIds) {
            $mission->update([
                'mission_type' => $data['mission_type'],
                'auditor' => $data['auditor'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'report_reference' => $data['report_reference'] ?? null,
                'comments' => $data['comments'] ?? null,
            ]);

            $mission->entities()->sync($entityIds);

            $mission->recommendations()->oldest()->first()?->update([
                'recommendation_date' => $data['recommendation_date'],
                'risk_level' => $data['risk_level'],
                'priority' => $data['priority'],
                'responsible_name' => $data['responsible'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'recommendation_label' => $data['recommendation_label'],
                'recommendation_details' => $data['recommendation_details'] ?? null,
            ]);

            $this->recommendationStatusService->syncMissionStatus($mission);

            $recipients = $this->recipientResolver->resolveRecipients($entityIds);
            $syncData = $recipients->mapWithKeys(fn (User $recipient) => [
                $recipient->id => ['notified_at' => now()],
            ])->all();
            $mission->recipients()->sync($syncData);
        });

        $mission->load(['recommendations', 'entities.environment', 'creator', 'recipients', 'responses.assignedAgent']);

        return $this->responseOk($this->formatMissionDetail($mission, $user));
    }

    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $mission = Mission::query()->with(['responses'])->findOrFail($id);

        if (! $this->canManageMission($user, $mission)) {
            return $this->responseError(['message' => ['Suppression non autorisée pour cette mission.']], 403);
        }

        $mission->delete();

        return $this->responseOk(['message' => 'Mission supprimée.']);
    }

    private function missionStoreValidationRules(?User $user = null): array
    {
        return [
            'reference' => 'required|string|max:100|unique:missions,reference',
            'mission_type' => 'required|in:'.implode(',', $this->allowedMissionTypesForUser($user)),
            'environment_id' => 'required|integer|exists:environments,id',
            'entity_ids' => 'required|array|min:1',
            'entity_ids.*' => 'integer|exists:entities,id',
            'auditor' => 'required|string|max:255',
            'issue_date' => 'nullable|date',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'report_reference' => 'nullable|string',
            'report_attachments' => 'nullable|array',
            'report_attachments.*' => 'file|max:10240',
        ];
    }

    private function missionValidationRules(?User $user = null): array
    {
        return [
            'mission_type' => 'required|in:'.implode(',', $this->allowedMissionTypesForUser($user)),
            'environment_id' => 'nullable|integer|exists:environments,id',
            'entity_ids' => 'required|array|min:1',
            'entity_ids.*' => 'integer|exists:entities,id',
            'auditor' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'recommendation_date' => 'required|date',
            'risk_level' => 'required|in:faible,moyen,eleve,critique',
            'priority' => 'required|in:basse,moyenne,haute',
            'responsible' => 'nullable|string|max:500',
            'due_date' => 'nullable|date',
            'report_reference' => 'nullable|string',
            'report_attachments' => 'nullable|array',
            'report_attachments.*' => 'file|max:10240',
            'recommendation_label' => 'required|string|max:500',
            'recommendation_details' => 'nullable|string',
            'comments' => 'nullable|string',
        ];
    }

    private function entitiesOutsideEnvironment(array $entityIds, int $environmentId): bool
    {
        $count = \App\Models\Entity::query()
            ->whereIn('id', $entityIds)
            ->where('environment_id', $environmentId)
            ->count();

        return $count !== count($entityIds);
    }

    private function storeReportAttachments(Request $request): array
    {
        $paths = [];

        if ($request->hasFile('report_attachments')) {
            foreach ($request->file('report_attachments') as $file) {
                $paths[] = $file->store('mission-reports', 'local');
            }
        }

        return $paths;
    }

    private function reportLabelFromFiles(Request $request, array $paths): ?string
    {
        if (! $request->hasFile('report_attachments')) {
            return null;
        }

        $names = collect($request->file('report_attachments'))
            ->map(fn ($file) => $file->getClientOriginalName())
            ->filter()
            ->values();

        return $names->isNotEmpty() ? $names->implode(', ') : null;
    }

    private function canManageMission(User $user, Mission $mission): bool
    {
        if ($mission->responses->isNotEmpty()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return in_array($user->profile, ['controle', 'audit'], true)
            && (int) $mission->created_by === (int) $user->id;
    }

    private function canCloseRecommendation(User $user, Recommendation $recommendation, Mission $mission): bool
    {
        if ($recommendation->status !== 'traitee' && $recommendation->status !== 'transmis') {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! in_array($user->profile, ['controle', 'audit'], true)) {
            return false;
        }

        return $this->canViewMission($user, $mission);
    }

    private function canAddRecommendationToMission(User $user, Mission $mission): bool
    {
        if (! $this->canViewMission($user, $mission)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return in_array($user->profile, ['controle', 'audit'], true);
    }

    private function canCreateMission(User $user): bool
    {
        return $user->isSuperAdmin()
            || in_array($user->profile, ['controle', 'audit'], true);
    }

    private function canViewMissions(User $user): bool
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

    private function canViewMission(User $user, Mission $mission): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (in_array($user->profile, ['controle', 'audit'], true)) {
            $environmentIds = $user->environment_ids;

            if (empty($environmentIds)) {
                return true;
            }

            return $mission->entities->contains(
                fn ($entity) => in_array((int) $entity->environment_id, $environmentIds, true)
            );
        }

        if ($user->profile === 'metier' && $user->metier_role === 'responsable_entite') {
            return $mission->recipients->contains('id', $user->id);
        }

        if ($user->profile === 'metier' && $user->metier_role === 'agent') {
            return $mission->responses->contains('assigned_agent_id', $user->id);
        }

        return false;
    }

    private function applyVisibilityFilter($query, User $user): void
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

    private function resolveResponsibleRecipients(array $entityIds)
    {
        return $this->recipientResolver->resolveRecipients($entityIds);
    }

    private function generateRecommendationReference(Mission $mission): string
    {
        $count = $mission->relationLoaded('recommendations')
            ? $mission->recommendations->count()
            : $mission->recommendations()->count();
        $number = str_pad((string) ($count + 1), 2, '0', STR_PAD_LEFT);

        return $mission->reference.'-R'.$number;
    }

    private function storeRecommendationAttachments(Request $request, int $missionId): array
    {
        $paths = [];

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $paths[] = $file->store("mission-recommendations/{$missionId}", 'local');
            }
        }

        return $paths;
    }

    private function generateReference(): string
    {
        $year = now()->format('Y');
        $count = Mission::query()->whereYear('created_at', $year)->count() + 1;

        return sprintf('MIS-%s-%04d', $year, $count);
    }

    private function formatMissionListItem(Mission $mission, ?User $viewer = null): array
    {
        $myResponse = $this->resolveViewerResponse($mission, $viewer);

        $item = [
            'id' => $mission->id,
            'reference' => $mission->reference,
            'title' => $mission->title,
            'mission_type' => $mission->mission_type,
            'mission_type_fr' => $mission->mission_type_fr,
            'status' => $mission->status,
            'status_fr' => $mission->status_fr,
            'period' => $mission->period,
            'auditor' => $mission->auditor,
            'issue_date' => $mission->issue_date?->format('Y-m-d'),
            'start_date' => $mission->start_date?->format('Y-m-d'),
            'end_date' => $mission->end_date?->format('Y-m-d'),
            'environment_label' => $mission->entities
                ->map(fn ($entity) => $entity->environment?->name)
                ->filter()
                ->unique()
                ->implode(', ') ?: '—',
            'environment_id' => $mission->entities->first()?->environment_id,
            'entities' => $mission->entities->map(fn ($entity) => $this->formatEntityItem($entity))->values(),
            'recommendation' => $this->formatRecommendationSummary($mission->recommendations->first()),
            'recommendations' => $mission->recommendations
                ->map(fn (Recommendation $recommendation) => $this->formatRecommendationSummary($recommendation))
                ->values(),
            'created_by_name' => $mission->creator?->name,
            'created_at' => $mission->created_at?->toIso8601String(),
            'my_response' => null,
            'my_response_fr' => null,
            'can_choose_response' => false,
            'mission_response' => null,
            'can_edit' => false,
            'can_delete' => false,
            'created_by' => $mission->created_by,
        ];

        if ($viewer && $viewer->profile === 'metier' && $viewer->metier_role === 'responsable_entite') {
            $recipient = $mission->recipients->firstWhere('id', $viewer->id);
            $responsableResponse = $mission->responses->firstWhere('responsable_id', $viewer->id);

            if ($recipient) {
                $hasDraftAction = $responsableResponse
                    && $responsableResponse->response_type === 'action'
                    && $responsableResponse->workflow_status === 'en_saisie';

                $item['can_choose_response'] = empty($recipient->pivot?->response) || $hasDraftAction;
                $item['my_response'] = $hasDraftAction ? null : $recipient->pivot?->response;
                $item['my_response_fr'] = $hasDraftAction ? null : $this->responseLabel($recipient->pivot?->response);
            }

            if ($responsableResponse) {
                $item['mission_response'] = $this->responseController->formatResponse($responsableResponse, $viewer);
            }
        }

        if ($viewer && $viewer->profile === 'metier' && $viewer->metier_role === 'agent') {
            $agentResponse = $mission->responses->firstWhere('assigned_agent_id', $viewer->id);
            if ($agentResponse) {
                $item['mission_response'] = $this->responseController->formatResponse($agentResponse, $viewer);
                $item['my_response'] = 'action';
                $item['my_response_fr'] = 'Action (agent)';
            }
        }

        if ($viewer && ($viewer->isSuperAdmin() || in_array($viewer->profile, ['controle', 'audit'], true))) {
            $item['can_edit'] = $this->canManageMission($viewer, $mission);
            $item['can_delete'] = $this->canManageMission($viewer, $mission);
            $item['can_add_recommendation'] = $this->canAddRecommendationToMission($viewer, $mission);
            $item['next_recommendation_reference'] = $this->canAddRecommendationToMission($viewer, $mission)
                ? $this->generateRecommendationReference($mission)
                : null;
            $item['owner_responses'] = $mission->recipients->map(fn (User $recipient) => [
                'id' => $recipient->id,
                'name' => $recipient->name,
                'response' => $recipient->pivot?->response,
                'response_fr' => $this->responseLabel($recipient->pivot?->response),
            ])->values();
        }

        if ($viewer && in_array($viewer->profile, ['controle', 'audit'], true)) {
            $item['responses'] = $mission->responses
                ->where('workflow_status', 'transmis')
                ->map(fn (MissionResponse $r) => $this->responseController->formatResponse($r, $viewer))
                ->values();
        }

        $recoStats = $this->computeRecoStats($mission);
        $item['reco_stats'] = $recoStats;
        $item['recommendations_count'] = $recoStats['total'];

        return $item;
    }

    private function computeRecoStats(Mission $mission): array
    {
        $recos = $mission->relationLoaded('recommendations')
            ? $mission->recommendations
            : $mission->recommendations()->get();

        $total = $recos->count();
        $implemented = 0;
        $inProgress = 0;
        $noStart = 0;

        foreach ($recos as $reco) {
            $status = $reco->status ?? 'emise';

            if (in_array($status, ['traitee', 'transmis', 'cloturee'], true)) {
                $implemented++;
            } elseif (in_array($status, ['en_cours'], true)) {
                $inProgress++;
            } else {
                $noStart++;
            }
        }

        $rate = $total > 0 ? (int) round(($implemented / $total) * 100) : 0;

        return [
            'total' => $total,
            'implemented' => $implemented,
            'in_progress' => $inProgress,
            'no_start' => $noStart,
            'implementation_rate' => $rate,
        ];
    }

    private function formatEntityItem($entity): array
    {
        return [
            'id' => $entity->id,
            'name' => $entity->name,
            'code' => $entity->code,
            'environment_name' => $entity->environment?->name,
            'responsible_name' => $this->recipientResolver->resolveResponsibleNames([(int) $entity->id]) ?: null,
        ];
    }

    private function formatRecommendationEntities(Recommendation $recommendation): array
    {
        $entities = $recommendation->relationLoaded('entities')
            ? $recommendation->entities
            : $recommendation->entities()->get();

        $names = $entities->pluck('name')->filter()->values();

        return [
            'entity_ids' => $entities->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'entity_id' => $entities->first()?->id,
            'entity_name' => $names->isNotEmpty() ? $names->implode(', ') : null,
            'entities' => $entities->map(fn ($entity) => [
                'id' => $entity->id,
                'name' => $entity->name,
            ])->values()->all(),
        ];
    }

    private function formatRecommendationSummary(?Recommendation $recommendation): ?array
    {
        if (! $recommendation) {
            return null;
        }

        return array_merge([
            'id' => $recommendation->id,
            'reference' => $recommendation->reference,
            'name' => $recommendation->name,
            'recommendation_label' => $recommendation->recommendation_label,
            'recommendation_details' => $recommendation->recommendation_details,
            'responsible_name' => $recommendation->responsible_name,
            'due_date' => $recommendation->due_date?->format('Y-m-d'),
            'risk_level_fr' => $recommendation->risk_level_fr,
            'priority_fr' => $recommendation->priority_fr,
            'status' => $recommendation->status,
            'status_fr' => $recommendation->status_fr,
        ], $this->formatRecommendationEntities($recommendation));
    }

    private function formatRecommendationDetail(Recommendation $recommendation, ?Mission $mission = null, ?User $viewer = null): array
    {
        $canManage = $mission && $viewer && $this->canManageMission($viewer, $mission);

        return array_merge([
            'id' => $recommendation->id,
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
            'can_edit' => $canManage,
            'can_delete' => $canManage,
            'can_close' => $viewer && $mission ? $this->canCloseRecommendation($viewer, $recommendation, $mission) : false,
            'follow_ups' => $this->formatRecommendationFollowUps($recommendation, $viewer),
            'action_plans' => $this->formatRecommendationActionPlans($recommendation, $viewer),
        ], $this->formatRecommendationEntities($recommendation));
    }

    private function formatRecommendationActionPlans(Recommendation $recommendation, ?User $viewer = null): array
    {
        $plans = $recommendation->relationLoaded('actionPlans')
            ? $recommendation->actionPlans
            : $recommendation->actionPlans()->with(['user', 'comments.user'])->get();

        return $plans->map(function ($plan) use ($recommendation, $viewer) {
            $status = $this->actionPlanStatusService->resolveStatus($plan);
            $canSync = $viewer && $this->canSyncRecommendationActionPlans($viewer, $recommendation);
            $ownerId = $viewer ? $this->resolveRecommendationActionPlanOwnerId($viewer, $recommendation) : null;
            $isOwnerPlan = $ownerId && (int) $plan->user_id === (int) $ownerId;

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
                'comments' => $this->formatRecommendationActionPlanComments($plan),
                'attachment_paths' => $plan->attachment_paths ?? [],
                'can_view' => true,
                'can_edit' => (bool) ($canSync && $isOwnerPlan),
                'can_edit_attachments' => $viewer
                    ? $this->canEditRecommendationActionPlanAttachments($viewer, $plan, $recommendation)
                    : false,
                'can_delete' => (bool) ($canSync && $isOwnerPlan),
            ];
        })->values()->all();
    }

    private function resolveRecommendationActionPlanOwnerId(User $user, Recommendation $recommendation): ?int
    {
        if ($user->profile === 'metier' && $user->metier_role === 'responsable_entite') {
            $entityIds = $user->entity_ids ?? [];

            if ($entityIds === [] || ! $recommendation->entities()->whereIn('entities.id', $entityIds)->exists()) {
                return null;
            }

            if (! $recommendation->mission->recipients()->where('users.id', $user->id)->exists()) {
                return null;
            }

            return (int) $user->id;
        }

        if ($user->profile === 'metier' && $user->metier_role === 'agent') {
            $response = $recommendation->mission->responses
                ->first(fn ($item) => $item->assigned_agent_id === $user->id
                    && $item->response_type === 'action'
                    && $item->workflow_status === 'en_saisie');

            return $response ? (int) $response->responsable_id : null;
        }

        return null;
    }

    private function canSyncRecommendationActionPlans(User $user, Recommendation $recommendation): bool
    {
        if ($this->resolveRecommendationActionPlanOwnerId($user, $recommendation) === null) {
            return false;
        }

        if ($user->profile === 'metier' && $user->metier_role === 'responsable_entite') {
            return ! $recommendation->mission->responses->contains(fn ($response) => $response->responsable_id === $user->id
                && $response->response_type === 'action'
                && $response->handling_mode === 'agent'
                && $response->workflow_status === 'en_saisie');
        }

        return true;
    }

    private function canEditRecommendationActionPlanAttachments(User $user, $plan, Recommendation $recommendation): bool
    {
        if (! $this->canSyncRecommendationActionPlans($user, $recommendation)) {
            return false;
        }

        $ownerId = $this->resolveRecommendationActionPlanOwnerId($user, $recommendation);

        if (! $ownerId || (int) $plan->user_id !== (int) $ownerId) {
            return false;
        }

        return true;
    }

    private function formatRecommendationActionPlanComments($plan): array
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

    private function formatRecommendationFollowUps(Recommendation $recommendation, ?User $viewer = null): array
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

    private function syncMissionStatusFromRecommendations(Mission $mission): void
    {
        $this->recommendationStatusService->syncMissionStatus($mission);
    }

    private function resolveViewerResponse(Mission $mission, ?User $viewer): ?MissionResponse
    {
        if (! $viewer) {
            return null;
        }

        if ($viewer->profile === 'metier' && $viewer->metier_role === 'responsable_entite') {
            return $mission->responses->firstWhere('responsable_id', $viewer->id);
        }

        if ($viewer->profile === 'metier' && $viewer->metier_role === 'agent') {
            return $mission->responses->firstWhere('assigned_agent_id', $viewer->id);
        }

        return null;
    }

    private function responseLabel(?string $response): ?string
    {
        return match ($response) {
            'action' => 'Action',
            'passivite' => 'Passivité',
            default => null,
        };
    }

    private function formatMissionDetail(Mission $mission, ?User $viewer = null): array
    {
        $item = $this->formatMissionListItem($mission, $viewer);

        $firstRecommendation = $mission->recommendations->first();

        return array_merge($item, [
            'issue_date' => $mission->issue_date?->format('Y-m-d'),
            'start_date' => $mission->start_date?->format('Y-m-d'),
            'end_date' => $mission->end_date?->format('Y-m-d'),
            'entity_ids' => $mission->entities->pluck('id')->map(fn ($id) => (int) $id)->values(),
            'responsible_preview' => $firstRecommendation?->responsible_name
                ?? $this->recipientResolver->resolveResponsibleNames(
                    $mission->entities->pluck('id')->map(fn ($id) => (int) $id)->all()
                ),
            'report_reference' => $mission->report_reference,
            'report_attachment_paths' => $mission->report_attachment_paths ?? [],
            'comments' => $mission->comments,
            'recommendation' => $firstRecommendation
                ? $this->formatRecommendationDetail($firstRecommendation, $mission, $viewer)
                : null,
            'recommendations' => $mission->recommendations
                ->map(fn (Recommendation $recommendation) => $this->formatRecommendationDetail($recommendation, $mission, $viewer))
                ->values(),
            'recipients' => $mission->recipients->map(fn (User $recipient) => [
                'id' => $recipient->id,
                'name' => $recipient->name,
                'email' => $recipient->email,
                'notified_at' => $recipient->pivot?->notified_at,
                'response' => $recipient->pivot?->response,
                'response_fr' => $this->responseLabel($recipient->pivot?->response),
            ])->values(),
            'all_responses' => $mission->responses->map(
                fn (MissionResponse $r) => $this->responseController->formatResponse($r, $viewer)
            )->values(),
        ]);
    }

    private function allowedMissionTypesForUser(?User $user): array
    {
        if (! $user || $user->isSuperAdmin()) {
            return self::MISSION_TYPES;
        }

        return collect(config('mission-parametrage.mission_types', []))
            ->filter(fn (array $type) => in_array($user->profile, $type['profiles'] ?? [], true))
            ->pluck('value')
            ->all();
    }
}
