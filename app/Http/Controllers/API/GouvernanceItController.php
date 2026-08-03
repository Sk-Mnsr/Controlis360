<?php

namespace App\Http\Controllers\API;

use App\Models\Entity;
use App\Models\Environment;
use App\Models\GouvernanceItActivity;
use App\Models\GouvernanceItActivityMessage;
use App\Models\GouvernanceItEnsemble;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maravel\Http\Controllers\APIController;

class GouvernanceItController extends APIController
{
    private const GOUVERNANCE_IT_PROFILES = [
        'super_admin',
        'admin',
        'agent_it',
        'responsable_it',
        'responsable_regional',
    ];

    private const OPERATIONS_PROFILES = [
        'super_admin',
        'admin',
        'agent_it',
        'responsable_it',
    ];

    /**
     * Contexte filiale + équipe IT pour Centre Support / Systèmes & Réseaux.
     */
    public function context(Request $request)
    {
        $user = $request->user();

        if (! in_array($user->profile, self::GOUVERNANCE_IT_PROFILES, true)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        [$environment, $itEntity, $owners] = $this->resolveScope($user);
        $staff = $this->itStaffForEnvironment($environment?->id);

        return $this->responseOk([
            'filiale' => $environment?->name ?? '—',
            'environment_id' => $environment?->id,
            'responsable' => $this->formatNameList($staff['responsables']),
            'team' => $this->formatNameList($staff['agents']),
            'equipe_it' => $owners ? $this->formatNameList($owners) : '—',
            'owners' => $owners,
            'entity_id' => $itEntity?->id,
            'entity_name' => $itEntity?->name,
            'sections' => GouvernanceItActivity::SECTIONS,
            'can_manage_operations' => in_array($user->profile, self::OPERATIONS_PROFILES, true),
        ]);
    }

    public function ensemblesIndex(Request $request)
    {
        $user = $request->user();

        if (! in_array($user->profile, self::OPERATIONS_PROFILES, true)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        $validator = Validator::make($request->all(), [
            'module_slug' => 'required|in:'.implode(',', GouvernanceItActivity::MODULES),
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        [$environment] = $this->resolveScope($user);
        $moduleSlug = $request->string('module_slug')->toString();

        $query = GouvernanceItEnsemble::query()
            ->with(['activities' => fn ($q) => $q->orderBy('section')->orderBy('sort_order')->orderBy('id')])
            ->where('module_slug', $moduleSlug)
            ->orderByDesc('id');

        if ($environment) {
            $query->where('environment_id', $environment->id);
        } elseif (! $user->isSuperAdmin()) {
            $query->whereRaw('1 = 0');
        }

        $ensembles = $query->get()->map(fn (GouvernanceItEnsemble $ensemble) => $this->serializeEnsemble($ensemble));

        return $this->responseOk($ensembles);
    }

    public function ensemblesStore(Request $request)
    {
        $user = $request->user();

        if (! in_array($user->profile, self::OPERATIONS_PROFILES, true)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        $validator = Validator::make($request->all(), [
            'module_slug' => 'required|in:'.implode(',', GouvernanceItActivity::MODULES),
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        [$environment, $itEntity] = $this->resolveScope($user);

        $ensemble = GouvernanceItEnsemble::query()->create([
            'environment_id' => $environment?->id,
            'entity_id' => $itEntity?->id,
            'module_slug' => $request->string('module_slug')->toString(),
            'label' => GouvernanceItEnsemble::makeAutoLabel(),
            'created_by' => $user->id,
        ]);

        return $this->responseOk($this->serializeEnsemble($ensemble->load('activities')));
    }

    public function ensemblesDestroy(Request $request, int $id)
    {
        $user = $request->user();

        if (! in_array($user->profile, self::OPERATIONS_PROFILES, true)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        $ensemble = GouvernanceItEnsemble::query()->with('activities')->find($id);
        if (! $ensemble) {
            return $this->responseError(['id' => ['Ensemble introuvable']], 404);
        }

        if (! $this->userCanAccessEnsemble($user, $ensemble)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        $hasSent = $ensemble->activities->contains(fn (GouvernanceItActivity $activity) => $activity->workflow_status === 'sent');
        if ($hasSent) {
            return $this->responseError([
                'ensemble' => ['Impossible de supprimer un ensemble contenant des lignes déjà envoyées'],
            ], 422);
        }

        $ensemble->delete();

        return $this->responseOk(['deleted' => true]);
    }

    /**
     * Inbox Responsable Régional :
     * lignes filtrées par filiale (environnement) sélectionnée parmi celles affectées.
     */
    public function regionalInbox(Request $request)
    {
        $user = $request->user();

        if (! in_array($user->profile, ['super_admin', 'admin', 'responsable_regional'], true)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        $emptySections = collect(array_keys(GouvernanceItActivity::SECTIONS))
            ->mapWithKeys(fn ($key) => [$key => []])
            ->all();

        $availableEnvironments = $this->availableFilialesForUser($user);
        if ($availableEnvironments->isEmpty()) {
                return $this->responseOk([
                    'filiale' => '—',
                    'environment_id' => null,
                    'filiales' => [],
                    'responsable' => '—',
                    'team' => '—',
                    'equipe_it' => '—',
                    'sections' => $emptySections,
                    'section_labels' => GouvernanceItActivity::SECTIONS,
                    'rows' => [],
                ]);
            }

        $requestedEnvironmentId = $request->integer('environment_id') ?: null;
        $allowedIds = $availableEnvironments->pluck('id')->all();

        if ($requestedEnvironmentId && ! in_array($requestedEnvironmentId, $allowedIds, true)) {
            return $this->responseError(['environment_id' => ['Filiale non autorisée']], 403);
        }

        $selectedEnvironmentId = $requestedEnvironmentId ?: (int) $availableEnvironments->first()->id;
        $selectedEnvironment = $availableEnvironments->firstWhere('id', $selectedEnvironmentId);

        $today = now()->toDateString();

        $query = GouvernanceItActivity::query()
            ->with(['environment', 'ensemble', 'creator'])
            ->where('environment_id', $selectedEnvironmentId)
            ->orderByRaw("CASE WHEN priorite = 'P1' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN statut = 'OPEN' AND date_livraison IS NOT NULL AND date_livraison < ? THEN 0 ELSE 1 END", [$today])
            ->orderByRaw("CASE WHEN workflow_status = 'sent' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE priorite WHEN 'P1' THEN 1 WHEN 'P2' THEN 2 WHEN 'P3' THEN 3 ELSE 4 END")
            ->orderBy('date_livraison')
            ->orderByDesc('id');

        $rows = $query->get()->map(function (GouvernanceItActivity $activity) use ($today) {
            $payload = $this->serializeActivity($activity);
            $reasons = [];

            if ($activity->priorite === 'P1') {
                $reasons[] = 'P1';
            }
            if (
                $activity->statut === 'OPEN'
                && $activity->date_livraison
                && $activity->date_livraison->toDateString() < $today
            ) {
                $reasons[] = 'Echéance dépassée';
            }
            if ($activity->workflow_status === 'sent') {
                $reasons[] = 'Envoyé';
            }

            $payload['reception_reasons'] = $reasons;
            $payload['is_attention'] = count($reasons) > 0;
            $payload['filiale'] = $activity->environment?->name ?? '—';
            $payload['ensemble_label'] = $activity->ensemble?->label ?? '—';
            $payload['module_label'] = match ($activity->module_slug) {
                'centre_support' => 'CENTRE SUPPORT',
                'systemes_reseaux' => 'SYSTEMES ET RESEAUX',
                'base_donnees' => 'BASE DE DONNEES',
                default => $activity->module_slug,
            };
            $payload['created_by_name'] = $activity->creator?->name;

            return $payload;
        });

        $sections = [];
        foreach (array_keys(GouvernanceItActivity::SECTIONS) as $sectionKey) {
            $sections[$sectionKey] = $rows
                ->filter(fn (array $row) => ($row['section'] ?? null) === $sectionKey)
                ->values()
                ->all();
        }

        $staff = $this->itStaffForEnvironment($selectedEnvironmentId);

        return $this->responseOk([
            'filiale' => $selectedEnvironment?->name ?? '—',
            'environment_id' => $selectedEnvironmentId,
            'filiales' => $availableEnvironments->map(fn ($env) => [
                'id' => $env->id,
                'name' => $env->name,
                'code' => $env->code,
            ])->values()->all(),
            'responsable' => $this->formatNameList($staff['responsables']),
            'team' => $this->formatNameList($staff['agents']),
            'equipe_it' => $this->formatNameList(array_merge($staff['responsables'], $staff['agents'])),
            'sections' => $sections,
            'section_labels' => GouvernanceItActivity::SECTIONS,
            'rows' => $rows->values()->all(),
        ]);
    }

    public function activitiesStore(Request $request)
    {
        $user = $request->user();

        if (! in_array($user->profile, self::OPERATIONS_PROFILES, true)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        $validator = Validator::make($request->all(), $this->activityRules());

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        [$environment, $itEntity] = $this->resolveScope($user);
        $data = $validator->validated();

        $ensemble = GouvernanceItEnsemble::query()->find($data['ensemble_id']);
        if (! $ensemble) {
            return $this->responseError(['ensemble_id' => ['Ensemble introuvable']], 404);
        }

        if (! $this->userCanAccessEnsemble($user, $ensemble)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        if ($ensemble->module_slug !== $data['module_slug']) {
            return $this->responseError(['module_slug' => ['Module incompatible avec cet ensemble']], 422);
        }

        if (! $this->userCanManageSection($user, $data['section'])) {
            return $this->responseError(['section' => ['L\'Agent IT ne peut créer que des Points d\'Attention']], 403);
        }

        $maxSort = GouvernanceItActivity::query()
            ->where('ensemble_id', $ensemble->id)
            ->where('section', $data['section'])
            ->max('sort_order');

        $activity = GouvernanceItActivity::query()->create([
            'environment_id' => $environment?->id ?? $ensemble->environment_id,
            'entity_id' => $itEntity?->id ?? $ensemble->entity_id,
            'ensemble_id' => $ensemble->id,
            'module_slug' => $data['module_slug'],
            'section' => $data['section'],
            'sort_order' => ((int) $maxSort) + 1,
            'title' => $data['title'] ?? null,
            'owner' => $data['owner'] ?? null,
            'priorite' => $data['priorite'] ?? null,
            'statut' => $data['statut'] ?? 'OPEN',
            'date_livraison' => $data['date_livraison'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'finish_date' => $data['finish_date'] ?? null,
            'lead_time_days' => GouvernanceItActivity::computeLeadTimeDays(
                $data['start_date'] ?? null,
                $data['finish_date'] ?? null,
            ),
            'commentaire' => $data['commentaire'] ?? null,
            'impact' => $data['impact'] ?? null,
            'workflow_status' => 'draft',
            'created_by' => $user->id,
        ]);

        if (! empty($activity->finish_date)) {
            $activity->statut = 'CLOSE';
            $activity->save();
        }

        return $this->responseOk($this->serializeActivity($activity->fresh()));
    }

    public function activitiesUpdate(Request $request, int $id)
    {
        $user = $request->user();

        if (! in_array($user->profile, self::OPERATIONS_PROFILES, true)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        $activity = GouvernanceItActivity::query()->find($id);
        if (! $activity) {
            return $this->responseError(['id' => ['Ligne introuvable']], 404);
        }

        if (! $this->userCanAccessActivity($user, $activity)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        if (! $this->userCanManageActivity($user, $activity)) {
            return $this->responseError(['auth' => ['Vous ne pouvez modifier que vos lignes (Owner) ou votre périmètre']], 403);
        }

        if ($activity->workflow_status === 'sent') {
            return $this->responseError(['workflow_status' => ['Cette ligne a déjà été envoyée']], 422);
        }

        $validator = Validator::make($request->all(), $this->activityRules(false));

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $data = $validator->validated();

        $activity->fill([
            'title' => array_key_exists('title', $data) ? $data['title'] : $activity->title,
            'owner' => array_key_exists('owner', $data) ? $data['owner'] : $activity->owner,
            'priorite' => array_key_exists('priorite', $data) ? $data['priorite'] : $activity->priorite,
            'statut' => $data['statut'] ?? $activity->statut,
            'date_livraison' => array_key_exists('date_livraison', $data) ? $data['date_livraison'] : $activity->date_livraison?->format('Y-m-d'),
            'start_date' => array_key_exists('start_date', $data) ? $data['start_date'] : $activity->start_date?->format('Y-m-d'),
            'finish_date' => array_key_exists('finish_date', $data) ? $data['finish_date'] : $activity->finish_date?->format('Y-m-d'),
            'commentaire' => array_key_exists('commentaire', $data) ? $data['commentaire'] : $activity->commentaire,
            'impact' => array_key_exists('impact', $data) ? $data['impact'] : $activity->impact,
            'workflow_status' => 'saved',
        ]);

        // Toute modification Agent IT (hors Points d'Attention) annule une validation existante.
        if ($this->activityRequiresValidation($activity) && $user->profile === 'agent_it') {
            $activity->validation_status = null;
            $activity->validated_by = null;
            $activity->validated_at = null;
            $activity->submitted_for_validation_at = null;
        }

        if (! empty($activity->finish_date)) {
            $activity->statut = 'CLOSE';
        }

        $activity->lead_time_days = GouvernanceItActivity::computeLeadTimeDays(
            $activity->start_date?->format('Y-m-d'),
            $activity->finish_date?->format('Y-m-d'),
        );
        $activity->save();

        return $this->responseOk($this->serializeActivity($activity->fresh()));
    }

    public function activitiesSubmitValidation(Request $request, int $id)
    {
        $user = $request->user();

        if ($user->profile !== 'agent_it' && ! in_array($user->profile, ['super_admin', 'admin'], true)) {
            return $this->responseError(['auth' => ['Seul un Agent IT peut soumettre une ligne à validation']], 403);
        }

        $activity = GouvernanceItActivity::query()->find($id);
        if (! $activity) {
            return $this->responseError(['id' => ['Ligne introuvable']], 404);
        }

        if (! $this->userCanAccessActivity($user, $activity)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        if (! $this->userCanManageActivity($user, $activity)) {
            return $this->responseError(['auth' => ['Vous ne pouvez soumettre que vos lignes (Owner) ou votre périmètre']], 403);
        }

        if ($activity->workflow_status === 'sent') {
            return $this->responseError(['workflow_status' => ['Cette ligne a déjà été envoyée']], 422);
        }

        if (! $this->activityRequiresValidation($activity)) {
            return $this->responseError(['section' => ['Les Points d\'Attention ne nécessitent pas de validation']], 422);
        }

        if ($activity->validation_status === 'pending') {
            return $this->responseError(['validation_status' => ['Cette ligne est déjà en attente de validation']], 422);
        }

        if (! trim((string) $activity->title)) {
            return $this->responseError(['title' => ['Le titre est obligatoire avant la soumission']], 422);
        }

        if (! trim((string) $activity->impact)) {
            return $this->responseError(['impact' => ['L\'impact est obligatoire avant la soumission']], 422);
        }

        $activity->validation_status = 'pending';
        $activity->submitted_for_validation_at = now();
        $activity->validated_by = null;
        $activity->validated_at = null;
        $activity->workflow_status = 'saved';
        $activity->save();

        return $this->responseOk($this->serializeActivity($activity->fresh()));
    }

    public function activitiesValidate(Request $request, int $id)
    {
        $user = $request->user();

        if (! in_array($user->profile, ['super_admin', 'admin', 'responsable_it'], true)) {
            return $this->responseError(['auth' => ['Seul un Responsable IT peut valider une ligne']], 403);
        }

        $activity = GouvernanceItActivity::query()->find($id);
        if (! $activity) {
            return $this->responseError(['id' => ['Ligne introuvable']], 404);
        }

        if (! $this->userCanAccessActivity($user, $activity)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        if ($activity->workflow_status === 'sent') {
            return $this->responseError(['workflow_status' => ['Cette ligne a déjà été envoyée']], 422);
        }

        if ($activity->validation_status !== 'pending') {
            return $this->responseError(['validation_status' => ['Aucune validation en attente pour cette ligne']], 422);
        }

        $activity->validation_status = 'validated';
        $activity->validated_by = $user->id;
        $activity->validated_at = now();
        $activity->save();

        return $this->responseOk($this->serializeActivity($activity->fresh()));
    }

    public function activitiesSend(Request $request, int $id)
    {
        $user = $request->user();

        if (! in_array($user->profile, self::OPERATIONS_PROFILES, true)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        $activity = GouvernanceItActivity::query()->find($id);
        if (! $activity) {
            return $this->responseError(['id' => ['Ligne introuvable']], 404);
        }

        if (! $this->userCanAccessActivity($user, $activity)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        if (! $this->userCanManageActivity($user, $activity)) {
            return $this->responseError(['auth' => ['Vous ne pouvez envoyer que vos lignes (Owner) ou votre périmètre']], 403);
        }

        if ($activity->workflow_status === 'sent') {
            return $this->responseError(['workflow_status' => ['Cette ligne a déjà été envoyée']], 422);
        }

        if (! trim((string) $activity->title)) {
            return $this->responseError(['title' => ['Le titre est obligatoire avant l\'envoi']], 422);
        }

        if (! trim((string) $activity->impact)) {
            return $this->responseError(['impact' => ['L\'impact est obligatoire avant l\'envoi']], 422);
        }

        if ($user->profile === 'agent_it' && $this->activityRequiresValidation($activity)) {
            if ($activity->validation_status !== 'validated') {
                return $this->responseError([
                    'validation_status' => ['Cette ligne doit être validée par le Responsable IT avant envoi au Responsable Régional'],
                ], 422);
            }
        }

        $hasRegional = User::query()
            ->where('profile', 'responsable_regional')
            ->where('activated', true)
            ->when(
                $activity->environment_id,
                fn ($q) => $q->whereHas('environments', fn ($env) => $env->where('environments.id', $activity->environment_id)),
            )
            ->exists();

        if (! $hasRegional && ! $user->isSuperAdmin()) {
            return $this->responseError(['send' => ['Aucun Responsable Régional trouvé pour cette filiale']], 422);
        }

        $activity->workflow_status = 'sent';
        $activity->sent_by = $user->id;
        $activity->sent_at = now();
        if ($activity->statut === null) {
            $activity->statut = 'OPEN';
        }
        $activity->save();

        return $this->responseOk($this->serializeActivity($activity->fresh()));
    }

    public function activitiesDestroy(Request $request, int $id)
    {
        $user = $request->user();

        if (! in_array($user->profile, self::OPERATIONS_PROFILES, true)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        $activity = GouvernanceItActivity::query()->find($id);
        if (! $activity) {
            return $this->responseError(['id' => ['Ligne introuvable']], 404);
        }

        if (! $this->userCanAccessActivity($user, $activity)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        if (! $this->userCanManageActivity($user, $activity)) {
            return $this->responseError(['auth' => ['Vous ne pouvez supprimer que vos lignes (Owner) ou votre périmètre']], 403);
        }

        if ($activity->workflow_status === 'sent') {
            return $this->responseError(['workflow_status' => ['Impossible de supprimer une ligne envoyée']], 422);
        }

        $activity->delete();

        return $this->responseOk(['deleted' => true]);
    }

    /**
     * Fil de discussion (chat) sur une ligne — distinct du champ Commentaire.
     */
    public function messagesIndex(Request $request, int $id)
    {
        $user = $request->user();

        if (! in_array($user->profile, self::GOUVERNANCE_IT_PROFILES, true)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        $activity = GouvernanceItActivity::query()->find($id);
        if (! $activity) {
            return $this->responseError(['id' => ['Ligne introuvable']], 404);
        }

        if (! $this->userCanChatOnActivity($user, $activity)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        $messages = GouvernanceItActivityMessage::query()
            ->with('user:id,name,profile')
            ->where('activity_id', $activity->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (GouvernanceItActivityMessage $message) => $this->serializeMessage($message));

        return $this->responseOk([
            'activity_id' => $activity->id,
            'activity_title' => $activity->title,
            'messages' => $messages,
        ]);
    }

    public function messagesStore(Request $request, int $id)
    {
        $user = $request->user();

        if (! in_array($user->profile, self::GOUVERNANCE_IT_PROFILES, true)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        $activity = GouvernanceItActivity::query()->find($id);
        if (! $activity) {
            return $this->responseError(['id' => ['Ligne introuvable']], 404);
        }

        if (! $this->userCanChatOnActivity($user, $activity)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        $validator = Validator::make($request->all(), [
            'body' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $message = GouvernanceItActivityMessage::query()->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'body' => trim($validator->validated()['body']),
        ]);

        $message->load('user:id,name,profile');

        return $this->responseOk($this->serializeMessage($message));
    }

    /**
     * Pièces jointes d'une ligne (upload / suppression).
     */
    public function attachmentsUpdate(Request $request, int $id)
    {
        $user = $request->user();

        if (! in_array($user->profile, self::GOUVERNANCE_IT_PROFILES, true)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        $activity = GouvernanceItActivity::query()->find($id);
        if (! $activity) {
            return $this->responseError(['id' => ['Ligne introuvable']], 404);
        }

        if (! $this->userCanChatOnActivity($user, $activity)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        $validator = Validator::make($request->all(), [
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
            'attachment_names' => 'nullable|array',
            'attachment_names.*' => 'nullable|string|max:255',
            'remove_attachments' => 'nullable|array',
            'remove_attachments.*' => 'string',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $items = $this->normalizeActivityAttachments($activity->attachment_paths ?? []);

        foreach ($request->input('remove_attachments', []) as $path) {
            $items = array_values(array_filter($items, function (array $item) use ($path) {
                if ($item['path'] !== $path) {
                    return true;
                }
                if (Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                }

                return false;
            }));
        }

        if ($request->hasFile('attachments')) {
            $names = $request->input('attachment_names', []);
            foreach ($request->file('attachments') as $index => $file) {
                $storedPath = $file->store("gouvernance-it/{$activity->id}", 'local');
                $customName = trim((string) ($names[$index] ?? ''));
                $items[] = [
                    'path' => $storedPath,
                    'name' => $customName !== ''
                        ? $customName
                        : ($file->getClientOriginalName() ?: basename($storedPath)),
                    'attached_at' => now()->toIso8601String(),
                    'uploaded_by' => $user->id,
                ];
            }
        }

        $activity->attachment_paths = array_values($items);
        $activity->save();

        return $this->responseOk($this->serializeActivity($activity->fresh()));
    }

    private function activityRules(bool $creating = true): array
    {
        return [
            'ensemble_id' => ($creating ? 'required' : 'sometimes').'|integer|exists:gouvernance_it_ensembles,id',
            'module_slug' => ($creating ? 'required' : 'sometimes').'|in:'.implode(',', GouvernanceItActivity::MODULES),
            'section' => ($creating ? 'required' : 'sometimes').'|in:'.implode(',', array_keys(GouvernanceItActivity::SECTIONS)),
            'title' => 'nullable|string|max:500',
            'owner' => 'nullable|string|max:255',
            'priorite' => 'nullable|in:P1,P2,P3',
            'statut' => 'nullable|in:OPEN,CLOSE',
            'date_livraison' => 'nullable|date',
            'start_date' => 'nullable|date',
            'finish_date' => 'nullable|date',
            'impact' => ($creating ? 'nullable' : 'required').'|string|max:5000',
            'commentaire' => 'nullable|string|max:5000',
        ];
    }

    private function serializeEnsemble(GouvernanceItEnsemble $ensemble): array
    {
        $grouped = $ensemble->activities->groupBy('section');
        $sections = [];
        foreach (array_keys(GouvernanceItActivity::SECTIONS) as $section) {
            $sections[$section] = ($grouped[$section] ?? collect())
                ->values()
                ->map(fn (GouvernanceItActivity $row) => $this->serializeActivity($row))
                ->all();
        }

        return [
            'id' => $ensemble->id,
            'label' => $ensemble->label,
            'module_slug' => $ensemble->module_slug,
            'environment_id' => $ensemble->environment_id,
            'entity_id' => $ensemble->entity_id,
            'created_at' => $ensemble->created_at?->toIso8601String(),
            'sections' => $sections,
        ];
    }

    private function serializeActivity(GouvernanceItActivity $activity): array
    {
        return [
            'id' => $activity->id,
            'ensemble_id' => $activity->ensemble_id,
            'module_slug' => $activity->module_slug,
            'section' => $activity->section,
            'section_label' => GouvernanceItActivity::SECTIONS[$activity->section] ?? $activity->section,
            'sort_order' => $activity->sort_order,
            'title' => $activity->title,
            'owner' => $activity->owner,
            'priorite' => $activity->priorite,
            'statut' => $activity->statut,
            'date_livraison' => $activity->date_livraison?->format('Y-m-d'),
            'start_date' => $activity->start_date?->format('Y-m-d'),
            'finish_date' => $activity->finish_date?->format('Y-m-d'),
            'lead_time_days' => $activity->lead_time_days,
            'impact' => $activity->impact,
            'commentaire' => $activity->commentaire,
            'attachments' => $this->formatActivityAttachments($activity),
            'attachments_count' => count($this->normalizeActivityAttachments($activity->attachment_paths ?? [])),
            'workflow_status' => $activity->workflow_status,
            'validation_status' => $activity->validation_status,
            'validated_at' => $activity->validated_at?->toIso8601String(),
            'submitted_for_validation_at' => $activity->submitted_for_validation_at?->toIso8601String(),
            'sent_at' => $activity->sent_at?->toIso8601String(),
            'locked' => $activity->workflow_status === 'sent',
            'requires_validation' => $this->activityRequiresValidation($activity),
            'messages_count' => (int) ($activity->messages_count ?? $activity->messages()->count()),
        ];
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, array{path: string, name: string, attached_at: ?string, uploaded_by: ?int}>
     */
    private function normalizeActivityAttachments(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (is_string($item) && $item !== '') {
                $normalized[] = [
                    'path' => $item,
                    'name' => basename($item),
                    'attached_at' => null,
                    'uploaded_by' => null,
                ];
                continue;
            }

            if (! is_array($item) || empty($item['path'])) {
                continue;
            }

            $path = (string) $item['path'];
            $normalized[] = [
                'path' => $path,
                'name' => trim((string) ($item['name'] ?? basename($path))) ?: basename($path),
                'attached_at' => $item['attached_at'] ?? null,
                'uploaded_by' => isset($item['uploaded_by']) ? (int) $item['uploaded_by'] : null,
            ];
        }

        return $normalized;
    }

    private function formatActivityAttachments(GouvernanceItActivity $activity): array
    {
        return array_map(function (array $item) {
            $path = $item['path'];
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            return [
                'path' => $path,
                'name' => $item['name'],
                'attached_at' => $item['attached_at'],
                'uploaded_by' => $item['uploaded_by'],
                'can_preview' => in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'pdf'], true),
            ];
        }, $this->normalizeActivityAttachments($activity->attachment_paths ?? []));
    }

    private function serializeMessage(GouvernanceItActivityMessage $message): array
    {
        $profile = $message->user?->profile;

        return [
            'id' => $message->id,
            'activity_id' => $message->activity_id,
            'body' => $message->body,
            'user_id' => $message->user_id,
            'author_name' => $message->user?->name ?? '—',
            'author_profile' => $profile,
            'author_profile_label' => match ($profile) {
                'agent_it' => 'Agent IT',
                'responsable_it' => 'Responsable IT',
                'responsable_regional' => 'Responsable Régional',
                'super_admin' => 'Super Admin',
                'admin' => 'Admin',
                default => $profile ?? '—',
            },
            'created_at' => $message->created_at?->toIso8601String(),
            'created_at_label' => $message->created_at?->format('d/m/Y H:i'),
        ];
    }

    private function userCanChatOnActivity(User $user, GouvernanceItActivity $activity): bool
    {
        if ($user->isSuperAdmin() || $user->profile === 'admin') {
            return true;
        }

        if ($user->profile === 'responsable_regional') {
            return $this->availableFilialesForUser($user)
                ->pluck('id')
                ->contains((int) $activity->environment_id);
        }

        if (in_array($user->profile, self::OPERATIONS_PROFILES, true)) {
            return $this->userCanAccessActivity($user, $activity);
        }

        return false;
    }

    private function userCanAccessActivity(User $user, GouvernanceItActivity $activity): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        [$environment] = $this->resolveScope($user);

        return $environment && (int) $activity->environment_id === (int) $environment->id;
    }

    private function userCanAccessEnsemble(User $user, GouvernanceItEnsemble $ensemble): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        [$environment] = $this->resolveScope($user);

        return $environment && (int) $ensemble->environment_id === (int) $environment->id;
    }

    private function availableFilialesForUser(User $user)
    {
        if ($user->isSuperAdmin()) {
            return Environment::query()->orderBy('name')->get(['id', 'name', 'code']);
        }

        return $user->environments()->orderBy('name')->get(['environments.id', 'environments.name', 'environments.code']);
    }

    /**
     * @return array{responsables: array<int, string>, agents: array<int, string>}
     */
    private function itStaffForEnvironment(?int $environmentId): array
    {
        if (! $environmentId) {
            return ['responsables' => [], 'agents' => []];
        }

        $itEntity = Entity::query()
            ->where('environment_id', $environmentId)
            ->where('code', 'IT')
            ->where('is_active', true)
            ->first();

        if (! $itEntity) {
            return ['responsables' => [], 'agents' => []];
        }

        $users = User::query()
            ->where('activated', true)
            ->whereHas('entities', fn ($query) => $query->where('entities.id', $itEntity->id))
            ->where(function ($query) {
                $query->whereIn('profile', ['agent_it', 'responsable_it'])
                    ->orWhere(function ($metierQuery) {
                        $metierQuery
                            ->where('profile', 'metier')
                            ->whereIn('metier_role', ['responsable_entite', 'agent']);
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'profile', 'metier_role']);

        $responsables = $users
            ->filter(fn (User $member) => $member->profile === 'responsable_it'
                || ($member->profile === 'metier' && $member->metier_role === 'responsable_entite'))
            ->pluck('name')
            ->unique()
            ->values()
            ->all();

        $agents = $users
            ->filter(fn (User $member) => $member->profile === 'agent_it'
                || ($member->profile === 'metier' && $member->metier_role === 'agent'))
            ->pluck('name')
            ->unique()
            ->values()
            ->all();

        return [
            'responsables' => $responsables,
            'agents' => $agents,
        ];
    }

    /**
     * @param  array<int, string>  $names
     */
    private function formatNameList(array $names): string
    {
        if (! $names) {
            return '—';
        }

        return implode('/', array_map(fn ($name) => $this->firstName($name), $names));
    }

    private function activityRequiresValidation(GouvernanceItActivity $activity): bool
    {
        return $activity->section !== 'points_attention';
    }

    private function userCanManageSection(User $user, string $section): bool
    {
        if ($user->profile !== 'agent_it') {
            return true;
        }

        return $section === 'points_attention';
    }

    private function userIsOwnerOfActivity(User $user, GouvernanceItActivity $activity): bool
    {
        if (! trim((string) $activity->owner)) {
            return false;
        }

        return strcasecmp(trim((string) $activity->owner), trim((string) $user->name)) === 0;
    }

    private function userCanManageActivity(User $user, GouvernanceItActivity $activity): bool
    {
        return $this->userCanManageSection($user, $activity->section)
            || $this->userIsOwnerOfActivity($user, $activity);
    }

    /**
     * @return array{0: ?Environment, 1: ?Entity, 2: array<int, string>}
     */
    private function resolveScope(User $user): array
    {
        $environment = $user->environments()->orderBy('name')->first();

        if (! $environment && $user->isSuperAdmin()) {
            $environment = Entity::query()
                ->with('environment')
                ->where('code', 'IT')
                ->where('is_active', true)
                ->first()
                ?->environment;
        }

        $itEntity = null;
        if ($environment) {
            $itEntity = Entity::query()
                ->where('environment_id', $environment->id)
                ->where('code', 'IT')
                ->where('is_active', true)
                ->first();
        }

        if (! $itEntity && $user->entities()->exists()) {
            $itEntity = $user->entities()
                ->with('environment')
                ->where('code', 'IT')
                ->where('is_active', true)
                ->first();

            if ($itEntity && ! $environment) {
                $environment = $itEntity->environment;
            }
        }

        $owners = [];
        if ($itEntity) {
            $owners = User::query()
                ->where('activated', true)
                ->whereHas('entities', fn ($query) => $query->where('entities.id', $itEntity->id))
                ->where(function ($query) {
                    $query->whereIn('profile', ['agent_it', 'responsable_it'])
                        ->orWhere(function ($metierQuery) {
                            $metierQuery
                                ->where('profile', 'metier')
                                ->whereIn('metier_role', ['responsable_entite', 'agent']);
                        });
                })
                ->orderBy('name')
                ->pluck('name')
                ->unique()
                ->values()
                ->all();
        }

        return [$environment, $itEntity, $owners];
    }

    private function firstName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return $parts[0] ?? $name;
    }
}
