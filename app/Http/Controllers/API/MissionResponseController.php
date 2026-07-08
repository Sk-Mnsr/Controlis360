<?php

namespace App\Http\Controllers\API;

use App\Mail\MissionAssignedToAgentMail;
use App\Mail\MissionResponseForwardedMail;
use App\Models\Mission;
use App\Models\MissionResponse;
use App\Models\User;
use App\Services\ActionPlanStatusService;
use App\Services\RecommendationStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maravel\Http\Controllers\APIController;

/**
 * @group Réponses mission
 */
class MissionResponseController extends APIController
{
    public function __construct(
        private RecommendationStatusService $recommendationStatusService,
        private ActionPlanStatusService $actionPlanStatusService,
    ) {}

    public function agents(Request $request, int $missionId)
    {
        $user = $request->user();
        $mission = $this->loadMissionForResponsable($missionId, $user);

        if (! $mission) {
            return $this->responseError(['message' => ['Mission introuvable ou accès refusé.']], 403);
        }

        $entityIds = $mission->entities->pluck('id')->all();
        $userEntityIds = $user->entity_ids;
        $sharedEntityIds = array_values(array_intersect($entityIds, $userEntityIds));

        $agents = User::query()
            ->where('profile', 'metier')
            ->where('metier_role', 'agent')
            ->where('activated', true)
            ->whereHas('entities', function ($query) use ($sharedEntityIds) {
                $query->whereIn('entities.id', $sharedEntityIds);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return $this->responseOk($agents);
    }

    public function startAction(Request $request, int $missionId)
    {
        $user = $request->user();
        $mission = $this->loadMissionForResponsable($missionId, $user);

        if (! $mission) {
            return $this->responseError(['message' => ['Mission introuvable ou accès refusé.']], 403);
        }

        if ($this->hasCommittedResponse($mission, $user)) {
            return $this->responseError(['message' => ['Vous avez déjà répondu à cette mission.']], 422);
        }

        $existingDraft = MissionResponse::query()
            ->where('mission_id', $mission->id)
            ->where('responsable_id', $user->id)
            ->where('response_type', 'action')
            ->where('workflow_status', 'en_saisie')
            ->first();

        if ($existingDraft) {
            $existingDraft->load(['assignedAgent', 'responsable']);

            return $this->responseOk($this->formatResponse($existingDraft, $user));
        }

        $validator = Validator::make($request->all(), [
            'handling_mode' => 'required|in:self,agent',
            'agent_id' => 'required_if:handling_mode,agent|nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        $data = $validator->validated();
        $agent = null;

        if ($data['handling_mode'] === 'agent') {
            $agent = $this->resolveAgentForMission($mission, $user, (int) $data['agent_id']);

            if (! $agent) {
                return $this->responseError(['agent_id' => ['Agent invalide pour cette entité.']], 422);
            }
        }

        $response = DB::transaction(function () use ($mission, $user, $data, $agent) {
            return MissionResponse::query()->create([
                'mission_id' => $mission->id,
                'responsable_id' => $user->id,
                'response_type' => 'action',
                'handling_mode' => $data['handling_mode'],
                'assigned_agent_id' => $agent?->id,
                'workflow_status' => 'en_saisie',
                'responsible_name' => $user->name,
            ]);
        });

        if ($agent) {
            Mail::to($agent->email)->send(new MissionAssignedToAgentMail($mission, $agent, $user));
        }

        $response->load(['assignedAgent', 'responsable']);

        return $this->responseOk($this->formatResponse($response, $user), 201);
    }

    public function updateAction(Request $request, int $missionId, int $responseId)
    {
        $user = $request->user();
        $response = $this->loadActionResponse($missionId, $responseId);

        if (! $response) {
            return $this->responseError(['message' => ['Réponse introuvable.']], 404);
        }

        if (! $this->canEditActionResponse($user, $response)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        if (in_array($response->workflow_status, ['a_valider', 'transmis'], true)) {
            return $this->responseError(['message' => ['Cette réponse ne peut plus être modifiée.']], 422);
        }

        $validator = Validator::make($request->all(), [
            'responsible_name' => 'nullable|string|max:255',
            'action_start_date' => 'nullable|date',
            'planned_end_date' => 'nullable|date|after_or_equal:action_start_date',
            'progress_rate' => 'nullable|integer|min:0|max:100',
            'comment' => 'nullable|string',
            'action_plan' => 'nullable|string',
            'needs_infrastructure_change' => 'sometimes|boolean',
            'investment_amount' => 'nullable|numeric|min:0',
            'go_no_go' => 'nullable|in:go,no_go',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
            'remove_attachments' => 'nullable|array',
            'remove_attachments.*' => 'string',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        $data = $validator->validated();
        $paths = $response->attachment_paths ?? [];

        foreach ($data['remove_attachments'] ?? [] as $path) {
            if (in_array($path, $paths, true)) {
                Storage::disk('local')->delete($path);
                $paths = array_values(array_filter($paths, fn ($p) => $p !== $path));
            }
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $paths[] = $file->store("mission-responses/{$missionId}", 'local');
            }
        }

        $response->update([
            'responsible_name' => $data['responsible_name'] ?? $response->responsible_name,
            'action_start_date' => $data['action_start_date'] ?? $response->action_start_date,
            'planned_end_date' => $data['planned_end_date'] ?? $response->planned_end_date,
            'progress_rate' => $data['progress_rate'] ?? $response->progress_rate,
            'comment' => $data['comment'] ?? $response->comment,
            'action_plan' => $data['action_plan'] ?? $response->action_plan,
            'needs_infrastructure_change' => $data['needs_infrastructure_change'] ?? $response->needs_infrastructure_change,
            'investment_amount' => $data['investment_amount'] ?? $response->investment_amount,
            'go_no_go' => $data['go_no_go'] ?? $response->go_no_go,
            'attachment_paths' => $paths,
        ]);

        $response->load(['assignedAgent', 'responsable']);

        return $this->responseOk($this->formatResponse($response, $user));
    }

    public function uploadAttachments(Request $request, int $missionId, int $responseId)
    {
        $user = $request->user();
        $response = $this->loadActionResponse($missionId, $responseId);

        if (! $response) {
            return $this->responseError(['message' => ['Réponse introuvable.']], 404);
        }

        if ($response->response_type !== 'action') {
            return $this->responseError(['message' => ['Réponse invalide.']], 422);
        }

        if (! $this->canEditActionResponse($user, $response)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $validator = Validator::make($request->all(), [
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
            'attachment_names' => 'nullable|array',
            'attachment_names.*' => 'nullable|string|max:255',
            'attachment_dates' => 'nullable|array',
            'attachment_dates.*' => 'nullable|date',
            'remove_attachments' => 'nullable|array',
            'remove_attachments.*' => 'string',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        $data = $validator->validated();
        $attachments = $this->normalizeResponseAttachments($response->attachment_paths ?? []);

        foreach ($data['remove_attachments'] ?? [] as $path) {
            $attachments = $this->removeResponseAttachment($attachments, $path);
        }

        if ($request->hasFile('attachments')) {
            $names = $request->input('attachment_names', []);
            $dates = $request->input('attachment_dates', []);

            foreach ($request->file('attachments') as $index => $file) {
                $attachments[] = [
                    'path' => $file->store("mission-responses/{$missionId}", 'local'),
                    'name' => trim((string) ($names[$index] ?? $file->getClientOriginalName())),
                    'attached_at' => $dates[$index] ?? now()->toDateString(),
                ];
            }
        }

        $response->update(['attachment_paths' => $attachments]);
        $response->load(['assignedAgent', 'responsable']);

        return $this->responseOk($this->formatResponse($response, $user));
    }

    public function submitAgent(Request $request, int $missionId, int $responseId)
    {
        $user = $request->user();
        $response = $this->loadActionResponse($missionId, $responseId);

        if (! $response) {
            return $this->responseError(['message' => ['Réponse introuvable.']], 404);
        }

        if ($user->profile !== 'metier' || $user->metier_role !== 'agent' || $response->assigned_agent_id !== $user->id) {
            return $this->responseError(['message' => ['Seul l\'agent affecté peut soumettre cette réponse.']], 403);
        }

        if ($response->workflow_status !== 'en_saisie') {
            return $this->responseError(['message' => ['Cette réponse a déjà été soumise.']], 422);
        }

        $response->update(['workflow_status' => 'a_valider']);
        $response->load(['assignedAgent', 'responsable']);

        return $this->responseOk($this->formatResponse($response, $user));
    }

    public function forward(Request $request, int $missionId, int $responseId)
    {
        $user = $request->user();
        $response = $this->loadActionResponse($missionId, $responseId);

        if (! $response) {
            return $this->responseError(['message' => ['Réponse introuvable.']], 404);
        }

        if ($response->responsable_id !== $user->id) {
            return $this->responseError(['message' => ['Seul le responsable peut transmettre cette réponse.']], 403);
        }

        if ($response->workflow_status === 'transmis') {
            return $this->responseError(['message' => ['Cette réponse a déjà été transmise.']], 422);
        }

        if ($response->handling_mode === 'agent' && $response->workflow_status !== 'a_valider') {
            return $this->responseError(['message' => ['Attendez la soumission de l\'agent avant de transmettre.']], 422);
        }

        $response->update([
            'workflow_status' => 'transmis',
            'validated_by' => $user->id,
            'validated_at' => now(),
            'forwarded_at' => now(),
        ]);

        $response->mission->recipients()->updateExistingPivot($user->id, [
            'response' => 'action',
            'responded_at' => now(),
        ]);

        $response->refresh();
        $this->recommendationStatusService->markOwnerRecommendationsTreatedFromResponse($response->mission, $response);
        $this->actionPlanStatusService->markTransmittedForOwner(
            $response->mission_id,
            $user->id,
            $user->entity_ids ?? [],
        );

        $mission = $response->mission->load(['creator', 'recommendations', 'entities']);
        $creator = $mission->creator;

        if ($creator) {
            Mail::to($creator->email)->send(new MissionResponseForwardedMail($mission, $response, $creator, $user));
        }

        $response->load(['assignedAgent', 'responsable']);

        return $this->responseOk($this->formatResponse($response, $user));
    }

    public function submitPassivity(Request $request, int $missionId)
    {
        $user = $request->user();
        $mission = $this->loadMissionForResponsable($missionId, $user);

        if (! $mission) {
            return $this->responseError(['message' => ['Mission introuvable ou accès refusé.']], 403);
        }

        if ($this->hasCommittedResponse($mission, $user)) {
            return $this->responseError(['message' => ['Vous avez déjà répondu à cette mission.']], 422);
        }

        $validator = Validator::make($request->all(), [
            'passivity_comment' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        $paths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $paths[] = $file->store("mission-responses/{$missionId}/passivity", 'local');
            }
        }

        $response = DB::transaction(function () use ($mission, $user, $validator, $paths) {
            MissionResponse::query()
                ->where('mission_id', $mission->id)
                ->where('responsable_id', $user->id)
                ->where('response_type', 'action')
                ->where('workflow_status', 'en_saisie')
                ->delete();

            $mission->recipients()->updateExistingPivot($user->id, [
                'response' => 'passivite',
                'responded_at' => now(),
            ]);

            $created = MissionResponse::query()->create([
                'mission_id' => $mission->id,
                'responsable_id' => $user->id,
                'response_type' => 'passivite',
                'workflow_status' => 'transmis',
                'passivity_comment' => $validator->validated()['passivity_comment'],
                'passivity_attachment_paths' => $paths,
                'forwarded_at' => now(),
            ]);

            $this->recommendationStatusService->markOwnerRecommendationsTreatedFromResponse($mission, $created);

            return $created;
        });

        $mission = $mission->load(['creator', 'recommendations', 'entities']);
        $creator = $mission->creator;

        if ($creator) {
            Mail::to($creator->email)->send(new MissionResponseForwardedMail($mission, $response, $creator, $user));
        }

        $response->load(['assignedAgent', 'responsable']);

        return $this->responseOk($this->formatResponse($response, $user), 201);
    }

    public function cancelAction(Request $request, int $missionId, int $responseId)
    {
        $user = $request->user();
        $response = $this->loadActionResponse($missionId, $responseId);

        if (! $response) {
            return $this->responseError(['message' => ['Réponse introuvable.']], 404);
        }

        if ($response->responsable_id !== $user->id) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        if ($response->workflow_status !== 'en_saisie') {
            return $this->responseError(['message' => ['Seul un brouillon peut être annulé.']], 422);
        }

        $response->delete();

        $response->mission->recipients()->updateExistingPivot($user->id, [
            'response' => null,
            'responded_at' => null,
        ]);

        return $this->responseOk(['message' => ['Brouillon d\'action annulé.']]);
    }

    public function validateTransmission(Request $request, int $missionId, int $responseId)
    {
        $user = $request->user();
        $response = MissionResponse::query()
            ->with(['mission'])
            ->where('mission_id', $missionId)
            ->find($responseId);

        if (! $response) {
            return $this->responseError(['message' => ['Réponse introuvable.']], 404);
        }

        if (! $this->canValidateTransmission($user, $response)) {
            return $this->responseError(['message' => ['Validation non autorisée.']], 403);
        }

        if ($response->workflow_status !== 'transmis') {
            return $this->responseError(['message' => ['Seule une réponse transmise peut être validée.']], 422);
        }

        $this->recommendationStatusService->markMissionClosed($response->mission);

        $response->load(['assignedAgent', 'responsable']);

        return $this->responseOk([
            'response' => $this->formatResponse($response, $user),
            'message' => 'Réponse validée. Les recommandations sont clôturées.',
        ]);
    }

    private function canValidateTransmission(User $user, MissionResponse $response): bool
    {
        if ($response->mission->created_by !== $user->id) {
            return false;
        }

        return $user->isSuperAdmin() || in_array($user->profile, ['controle', 'audit'], true);
    }

    private function loadMissionForResponsable(int $missionId, User $user): ?Mission
    {
        if ($user->profile !== 'metier' || $user->metier_role !== 'responsable_entite') {
            return null;
        }

        return Mission::query()
            ->with(['entities', 'recipients'])
            ->whereHas('recipients', fn ($q) => $q->where('users.id', $user->id))
            ->find($missionId);
    }

    private function loadActionResponse(int $missionId, int $responseId): ?MissionResponse
    {
        return MissionResponse::query()
            ->with(['mission.creator', 'assignedAgent', 'responsable'])
            ->where('mission_id', $missionId)
            ->where('response_type', 'action')
            ->find($responseId);
    }

    private function hasCommittedResponse(Mission $mission, User $user): bool
    {
        $recipient = $mission->recipients->firstWhere('id', $user->id);

        return ! empty($recipient?->pivot?->response);
    }

    private function resolveAgentForMission(Mission $mission, User $responsable, int $agentId): ?User
    {
        $entityIds = $mission->entities->pluck('id')->all();
        $sharedEntityIds = array_values(array_intersect($entityIds, $responsable->entity_ids));

        return User::query()
            ->where('id', $agentId)
            ->where('profile', 'metier')
            ->where('metier_role', 'agent')
            ->where('activated', true)
            ->whereHas('entities', function ($query) use ($sharedEntityIds) {
                $query->whereIn('entities.id', $sharedEntityIds);
            })
            ->first();
    }

    private function canEditActionResponse(User $user, MissionResponse $response): bool
    {
        if ($response->response_type !== 'action') {
            return false;
        }

        if ($response->responsable_id === $user->id && $response->handling_mode === 'self') {
            return true;
        }

        if ($response->assigned_agent_id === $user->id && $response->handling_mode === 'agent') {
            return $response->workflow_status === 'en_saisie';
        }

        if ($response->responsable_id === $user->id && $response->handling_mode === 'agent') {
            return $response->workflow_status === 'a_valider';
        }

        return false;
    }

    public function formatResponse(MissionResponse $response, ?User $viewer = null): array
    {
        $canEdit = $viewer ? $this->canEditActionResponse($viewer, $response) : false;
        $canSubmitAgent = $viewer
            && $viewer->id === $response->assigned_agent_id
            && $response->workflow_status === 'en_saisie'
            && $response->handling_mode === 'agent';
        $canForward = $viewer
            && $viewer->id === $response->responsable_id
            && $response->workflow_status !== 'transmis'
            && ($response->handling_mode === 'self' || $response->workflow_status === 'a_valider');
        $canValidate = $viewer
            && $this->canValidateTransmission($viewer, $response);
        $canCancel = $viewer
            && $viewer->id === $response->responsable_id
            && $response->response_type === 'action'
            && $response->workflow_status === 'en_saisie';

        return [
            'id' => $response->id,
            'mission_id' => $response->mission_id,
            'response_type' => $response->response_type,
            'response_type_fr' => $response->response_type_fr,
            'handling_mode' => $response->handling_mode,
            'workflow_status' => $response->workflow_status,
            'workflow_status_fr' => $response->workflow_status_fr ?? $this->workflowStatusLabel($response->workflow_status),
            'responsible_name' => $response->responsible_name,
            'action_start_date' => $response->action_start_date?->format('Y-m-d'),
            'planned_end_date' => $response->planned_end_date?->format('Y-m-d'),
            'progress_rate' => $response->progress_rate,
            'comment' => $response->comment,
            'action_plan' => $response->action_plan,
            'needs_infrastructure_change' => $response->needs_infrastructure_change,
            'investment_amount' => $response->investment_amount,
            'go_no_go' => $response->go_no_go,
            'go_no_go_fr' => $response->go_no_go_fr,
            'attachment_paths' => collect($this->normalizeResponseAttachments($response->attachment_paths ?? []))
                ->pluck('path')
                ->values()
                ->all(),
            'attachments' => $this->formatResponseAttachments($response),
            'passivity_comment' => $response->passivity_comment,
            'passivity_attachment_paths' => $response->passivity_attachment_paths ?? [],
            'assigned_agent' => $response->assignedAgent ? [
                'id' => $response->assignedAgent->id,
                'name' => $response->assignedAgent->name,
                'email' => $response->assignedAgent->email,
            ] : null,
            'responsable_id' => $response->responsable_id,
            'responsable' => $response->responsable ? [
                'id' => $response->responsable->id,
                'name' => $response->responsable->name,
            ] : null,
            'forwarded_at' => $response->forwarded_at?->toIso8601String(),
            'can_edit' => $canEdit,
            'can_submit_agent' => $canSubmitAgent,
            'can_forward' => $canForward,
            'can_validate' => $canValidate,
            'can_cancel' => $canCancel,
        ];
    }

    private function workflowStatusLabel(?string $status): ?string
    {
        return match ($status) {
            'en_saisie' => 'En saisie',
            'a_valider' => 'À valider',
            'transmis' => 'Transmis',
            default => $status,
        };
    }

    private function normalizeResponseAttachments(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (is_string($item) && $item !== '') {
                $normalized[] = [
                    'path' => $item,
                    'name' => basename($item),
                    'attached_at' => null,
                ];

                continue;
            }

            if (! is_array($item) || empty($item['path'])) {
                continue;
            }

            $normalized[] = [
                'path' => (string) $item['path'],
                'name' => trim((string) ($item['name'] ?? basename((string) $item['path']))) ?: basename((string) $item['path']),
                'attached_at' => $item['attached_at'] ?? null,
            ];
        }

        return $normalized;
    }

    private function formatResponseAttachments(MissionResponse $response): array
    {
        $items = $this->normalizeResponseAttachments($response->attachment_paths ?? []);
        $lineNumber = 1;

        return array_map(function (array $item) use (&$lineNumber) {
            $path = $item['path'];

            return [
                'line_number' => $lineNumber++,
                'path' => $path,
                'name' => $item['name'],
                'attached_at' => $item['attached_at'],
                'can_preview' => $this->isPreviewableAttachmentPath($path),
            ];
        }, $items);
    }

    private function removeResponseAttachment(array $attachments, string $path): array
    {
        return array_values(array_filter(
            $attachments,
            function (array $item) use ($path) {
                if ($item['path'] !== $path) {
                    return true;
                }

                if (Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                }

                return false;
            },
        ));
    }

    private function isPreviewableAttachmentPath(string $path): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'pdf'], true);
    }
}
