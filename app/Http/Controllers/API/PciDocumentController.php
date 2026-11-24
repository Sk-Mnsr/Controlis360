<?php

namespace App\Http\Controllers\API;

use App\Mail\PciDocumentPendingValidationMail;
use App\Models\Environment;
use App\Models\PciDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maravel\Http\Controllers\APIController;

class PciDocumentController extends APIController
{
    private const FIELD_RULES = [
        'reference' => 'nullable|string|max:40',
        'document_name' => 'required|string|max:255',
        'category' => 'nullable|string|max:120',
        'direction' => 'nullable|string|max:160',
        'gouvernance_owner' => 'nullable|string|max:160',
        'controle_interne' => 'nullable|string|max:160',
        'responsible' => 'nullable|string|max:160',
        'created_on' => 'nullable|date',
        'last_review_date' => 'nullable|date',
        'periodicity_months' => 'nullable|integer|min:1|max:120',
        'validated_on' => 'nullable|date',
        'validated_by' => 'nullable|string|max:160',
        'version' => 'nullable|string|max:40',
        'observations' => 'nullable|string|max:5000',
        'environment_id' => 'nullable|integer|exists:environments,id',
    ];

    private const ATTACHMENT_RULES = [
        'attachments' => 'nullable|array',
        'attachments.*' => 'file|max:20480',
        'attachment_names' => 'nullable|array',
        'attachment_names.*' => 'nullable|string|max:255',
        'attachment_rights' => 'nullable|array',
        'attachment_rights.*' => 'nullable|string|in:Lecture,Modification,Confidentiel',
        'sync_attachments' => 'nullable|boolean',
        'kept_paths' => 'nullable|array',
        'kept_paths.*' => 'string',
        'kept_names' => 'nullable|array',
        'kept_names.*' => 'nullable|string|max:255',
        'kept_rights' => 'nullable|array',
        'kept_rights.*' => 'nullable|string|in:Lecture,Modification,Confidentiel',
    ];

    private const ATTACHMENT_MESSAGES = [
        'attachments.*.file' => 'Le fichier n’a pas pu être reçu. Taille maximale : 20 Mo.',
        'attachments.*.max' => 'Chaque fichier doit faire au plus 20 Mo.',
        'attachment_rights.*.in' => 'Le droit doit être Lecture, Modification ou Confidentiel.',
    ];

    public function index(Request $request)
    {
        $user = $request->user();

        if (! $this->canAccess($user)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        $available = $this->availableEnvironments($user);
        $requestedEnvironmentId = $request->integer('environment_id') ?: null;

        if ($requestedEnvironmentId && ! $available->contains('id', $requestedEnvironmentId) && ! $user->isSuperAdmin()) {
            return $this->responseError(['environment_id' => ['Filiale non autorisée']], 403);
        }

        $environmentId = $requestedEnvironmentId
            ?: ($available->count() === 1 ? $available->first()->id : null);

        $query = PciDocument::query()
            ->with(['environment:id,name,code', 'workflowValidator:id,name'])
            ->orderBy('reference')
            ->orderBy('id');

        if ($environmentId) {
            $query->where('environment_id', $environmentId);
        } elseif (! $user->isSuperAdmin()) {
            $ids = $available->pluck('id')->all();
            $query->whereIn('environment_id', $ids ?: [-1]);
        }

        $rows = $query->get()->map(fn (PciDocument $row) => $this->serialize($row))->values();

        return $this->responseOk([
            'rows' => $rows,
            'stats' => $this->stats($rows),
            'environment_id' => $environmentId,
            'filiales' => $available->map(fn ($env) => [
                'id' => $env->id,
                'name' => $env->name,
                'code' => $env->code,
            ])->values()->all(),
            'can_select_filiale' => $user->isSuperAdmin() || $available->count() > 1,
            'permissions' => $this->permissions($user),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (! $this->canWrite($user)) {
            return $this->responseError(['auth' => ['Seul un Agent IT ou Responsable IT peut enregistrer un document']], 403);
        }

        $validator = Validator::make($request->all(), [...self::FIELD_RULES, ...self::ATTACHMENT_RULES], self::ATTACHMENT_MESSAGES);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        if ($nameError = $this->attachmentNameError($request)) {
            return $this->responseError(['attachment_names' => [$nameError]], 422);
        }

        $data = $validator->validated();
        $environmentId = $this->resolveWritableEnvironmentId($user, $data['environment_id'] ?? null);

        if ($environmentId === false) {
            return $this->responseError(['environment_id' => ['Filiale non autorisée']], 403);
        }

        $reference = $this->resolveReference($data['reference'] ?? null, $environmentId);

        if ($this->referenceTaken($reference, $environmentId)) {
            return $this->responseError(['reference' => ['Cette référence existe déjà.']], 422);
        }

        $row = PciDocument::query()->create([
            ...$this->payloadFromValidated($data),
            'reference' => $reference,
            'periodicity_months' => (int) ($data['periodicity_months'] ?? 12),
            'environment_id' => $environmentId,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $uploaded = $this->uploadedAttachments($request, $row->id);
        if ($uploaded !== []) {
            $row->attachment_paths = $uploaded;
        }
        $this->applyWorkflow($row);
        $row->save();
        $row->load(['environment:id,name,code', 'workflowValidator:id,name']);
        $this->notifyIfPending($row, $user);

        return $this->responseOk($this->serialize($row), 201);
    }

    public function update(Request $request, int $id)
    {
        $user = $request->user();

        if (! $this->canWrite($user)) {
            return $this->responseError(['auth' => ['Seul un Agent IT ou Responsable IT peut modifier un document']], 403);
        }

        $row = PciDocument::query()->find($id);

        if (! $row || ! $this->userCanAccessRow($user, $row)) {
            return $this->responseError(['message' => ['Document introuvable']], 404);
        }

        if ($row->workflow_status === PciDocument::WORKFLOW_PENDING_DELETION) {
            return $this->responseError(['workflow_status' => ['Un retrait est en attente de validation']], 422);
        }

        $validator = Validator::make($request->all(), [...self::FIELD_RULES, ...self::ATTACHMENT_RULES], self::ATTACHMENT_MESSAGES);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        if ($nameError = $this->attachmentNameError($request)) {
            return $this->responseError(['attachment_names' => [$nameError]], 422);
        }

        $data = $validator->validated();
        $environmentId = $this->resolveWritableEnvironmentId($user, $data['environment_id'] ?? $row->environment_id);

        if ($environmentId === false) {
            return $this->responseError(['environment_id' => ['Filiale non autorisée']], 403);
        }

        $reference = $this->resolveReference($data['reference'] ?? $row->reference, $environmentId, $row->id);

        if ($this->referenceTaken($reference, $environmentId, $row->id)) {
            return $this->responseError(['reference' => ['Cette référence existe déjà.']], 422);
        }

        $row->fill([
            ...$this->payloadFromValidated($data),
            'reference' => $reference,
            'periodicity_months' => (int) ($data['periodicity_months'] ?? $row->periodicity_months ?? 12),
            'environment_id' => $environmentId,
            'updated_by' => $user->id,
        ]);
        $uploaded = $this->uploadedAttachments($request, $row->id);
        if ($request->boolean('sync_attachments') || $uploaded !== []) {
            $kept = $request->boolean('sync_attachments')
                ? $this->syncedAttachments($row, $request)
                : $this->normalizeAttachments($row->attachment_paths ?? []);
            $row->attachment_paths = array_values([...$kept, ...$uploaded]);
        }
        $this->applyWorkflow($row);
        $row->save();
        $row->load(['environment:id,name,code', 'workflowValidator:id,name']);
        $this->notifyIfPending($row, $user);

        return $this->responseOk($this->serialize($row));
    }

    public function destroy(Request $request, int $id)
    {
        $user = $request->user();

        if (! $this->canWrite($user)) {
            return $this->responseError(['auth' => ['Seul un Agent IT ou Responsable IT peut demander un retrait']], 403);
        }

        $row = PciDocument::query()->find($id);

        if (! $row || ! $this->userCanAccessRow($user, $row)) {
            return $this->responseError(['message' => ['Document introuvable']], 404);
        }

        if ($row->workflow_status === PciDocument::WORKFLOW_PENDING_DELETION) {
            return $this->responseError(['workflow_status' => ['Un retrait est déjà en attente de validation']], 422);
        }

        $row->workflow_status = PciDocument::WORKFLOW_PENDING_DELETION;
        $row->updated_by = $user->id;
        $row->save();
        $row->load(['environment:id,name,code', 'workflowValidator:id,name']);
        $this->notifyIfPending($row, $user);

        return $this->responseOk($this->serialize($row));
    }

    public function confirmDeletion(Request $request, int $id)
    {
        $user = $request->user();

        if (! $this->canValidate($user)) {
            return $this->responseError(['auth' => ['Seul le Responsable IT peut valider un retrait']], 403);
        }

        $row = PciDocument::query()->find($id);

        if (! $row || ! $this->userCanAccessRow($user, $row)) {
            return $this->responseError(['message' => ['Document introuvable']], 404);
        }

        if ($row->workflow_status !== PciDocument::WORKFLOW_PENDING_DELETION) {
            return $this->responseError(['workflow_status' => ['Aucun retrait n’est en attente de validation']], 422);
        }

        $this->deleteStoredFiles($row);
        $row->delete();

        return $this->responseOk(['deleted' => true]);
    }

    public function cancelDeletion(Request $request, int $id)
    {
        $user = $request->user();

        if (! $this->canWrite($user) && ! $this->canValidate($user)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        $row = PciDocument::query()->find($id);

        if (! $row || ! $this->userCanAccessRow($user, $row)) {
            return $this->responseError(['message' => ['Document introuvable']], 404);
        }

        if ($row->workflow_status !== PciDocument::WORKFLOW_PENDING_DELETION) {
            return $this->responseError(['workflow_status' => ['Aucun retrait n’est en attente de validation']], 422);
        }

        $row->workflow_status = $row->workflow_validated_by
            ? PciDocument::WORKFLOW_VALIDATED
            : PciDocument::WORKFLOW_PENDING;
        $row->updated_by = $user->id;
        $row->save();
        $row->load(['environment:id,name,code', 'workflowValidator:id,name']);

        return $this->responseOk($this->serialize($row));
    }

    public function attachments(Request $request, int $id)
    {
        $user = $request->user();

        if (! $this->canWrite($user)) {
            return $this->responseError(['auth' => ['Seul un Agent IT ou Responsable IT peut ajouter un fichier']], 403);
        }

        $row = PciDocument::query()->find($id);

        if (! $row || ! $this->userCanAccessRow($user, $row)) {
            return $this->responseError(['message' => ['Document introuvable']], 404);
        }

        if ($row->workflow_status === PciDocument::WORKFLOW_PENDING_DELETION) {
            return $this->responseError(['workflow_status' => ['Un retrait est en attente de validation']], 422);
        }

        $validator = Validator::make($request->all(), [
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:20480',
            'attachment_names' => 'nullable|array',
            'attachment_names.*' => 'nullable|string|max:255',
            'attachment_rights' => 'nullable|array',
            'attachment_rights.*' => 'nullable|string|in:Lecture,Modification,Confidentiel',
            'remove_paths' => 'nullable|array',
            'remove_paths.*' => 'string',
        ], [
            'attachments.*.file' => 'Le fichier n’a pas pu être reçu. Taille maximale : '.ini_get('upload_max_filesize').'.',
            'attachments.*.max' => 'Chaque fichier doit faire au plus 20 Mo.',
            'attachment_rights.*.in' => 'Le droit doit être Lecture, Modification ou Confidentiel.',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        if ($nameError = $this->attachmentNameError($request)) {
            return $this->responseError(['attachment_names' => [$nameError]], 422);
        }

        $items = $this->normalizeAttachments($row->attachment_paths ?? []);

        foreach ($request->input('remove_paths', []) as $path) {
            $items = array_values(array_filter($items, function (array $item) use ($path, $row) {
                if ($item['path'] !== $path || ! $this->pathBelongsToDocument($path, $row->id)) {
                    return true;
                }

                if (Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                }

                return false;
            }));
        }

        $uploaded = $this->uploadedAttachments($request, $row->id);
        if ($uploaded !== []) {
            $items = array_values([...$items, ...$uploaded]);
        }

        $row->attachment_paths = array_values($items);
        $row->updated_by = $user->id;
        $this->applyWorkflow($row);
        $row->save();
        $row->load(['environment:id,name,code', 'workflowValidator:id,name']);
        $this->notifyIfPending($row, $user);

        return $this->responseOk($this->serialize($row));
    }

    public function validateDocument(Request $request, int $id)
    {
        $user = $request->user();

        if (! $this->canValidate($user)) {
            return $this->responseError(['auth' => ['Seul le Responsable IT peut valider']], 403);
        }

        $row = PciDocument::query()->find($id);

        if (! $row || ! $this->userCanAccessRow($user, $row)) {
            return $this->responseError(['message' => ['Document introuvable']], 404);
        }

        if ($row->workflow_status === PciDocument::WORKFLOW_VALIDATED && $row->workflow_validated_by) {
            return $this->responseError(['workflow_status' => ['Ce document est déjà validé']], 422);
        }

        $row->workflow_status = PciDocument::WORKFLOW_VALIDATED;
        $row->workflow_validated_by = $user->id;
        $row->workflow_validated_at = now();
        $row->save();
        $row->load(['environment:id,name,code', 'workflowValidator:id,name']);

        return $this->responseOk($this->serialize($row));
    }

    private function gouvernanceProfile(User $user): ?string
    {
        if ($user->isPlatformAdministrator()) {
            return $user->profile;
        }

        return $user->moduleProfile('gouvernance-it') ?? $user->profile;
    }

    private function canAccess(User $user): bool
    {
        return in_array($this->gouvernanceProfile($user), [
            'super_admin',
            'admin',
            'agent_it',
            'responsable_it',
            'responsable_regional',
        ], true);
    }

    private function canWrite(User $user): bool
    {
        return in_array($this->gouvernanceProfile($user), [
            'super_admin',
            'admin',
            'agent_it',
            'responsable_it',
        ], true);
    }

    private function canValidate(User $user): bool
    {
        return in_array($this->gouvernanceProfile($user), [
            'super_admin',
            'admin',
            'responsable_it',
        ], true);
    }

    private function applyWorkflow(PciDocument $row): void
    {
        $row->workflow_status = PciDocument::WORKFLOW_PENDING;
        $row->workflow_validated_by = null;
        $row->workflow_validated_at = null;
    }

    private function notifyIfPending(PciDocument $row, User $sender): void
    {
        $action = match ($row->workflow_status) {
            PciDocument::WORKFLOW_PENDING => 'update',
            PciDocument::WORKFLOW_PENDING_DELETION => 'deletion',
            default => null,
        };

        if (! $action) {
            return;
        }

        $recipients = User::query()
            ->where('activated', true)
            ->whereNotNull('email')
            ->where(function ($query) {
                $query->where('profile', 'responsable_it')
                    ->orWhere('module_profiles->gouvernance-it->profile', 'responsable_it');
            })
            ->when($row->environment_id, function ($query) use ($row) {
                $query->where(function ($inner) use ($row) {
                    $inner->whereHas('environments', fn ($env) => $env->where('environments.id', $row->environment_id))
                        ->orWhereDoesntHave('environments');
                });
            })
            ->get()
            ->reject(fn (User $user) => $user->id === $sender->id || blank($user->email));

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(new PciDocumentPendingValidationMail($row, $recipient, $sender, $action));
            } catch (\Throwable $e) {
                Log::warning('Envoi du mail de validation Doc Gouvernance échoué', [
                    'document_id' => $row->id,
                    'recipient_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function permissions(User $user): array
    {
        $canWrite = $this->canWrite($user);

        return [
            'can_create' => $canWrite,
            'can_edit' => $canWrite,
            'can_delete' => $canWrite,
            'can_validate' => $this->canValidate($user),
        ];
    }

    private function availableEnvironments(User $user)
    {
        if ($user->isSuperAdmin()) {
            return Environment::query()->orderBy('name')->get(['id', 'name', 'code']);
        }

        return $user->environments()->orderBy('name')->get(['environments.id', 'environments.name', 'environments.code']);
    }

    private function resolveWritableEnvironmentId(User $user, mixed $requested): int|false|null
    {
        $available = $this->availableEnvironments($user);

        if ($requested) {
            $id = (int) $requested;
            if (! $available->contains('id', $id) && ! $user->isSuperAdmin()) {
                return false;
            }

            return $id;
        }

        if ($available->isNotEmpty()) {
            return (int) $available->first()->id;
        }

        return null;
    }

    private function userCanAccessRow(User $user, PciDocument $row): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $row->environment_id) {
            return true;
        }

        return $this->availableEnvironments($user)->contains('id', (int) $row->environment_id);
    }

    private function resolveReference(?string $reference, int|null $environmentId, ?int $ignoreId = null): string
    {
        $reference = trim((string) $reference);

        if ($reference !== '') {
            return $reference;
        }

        $max = PciDocument::query()
            ->when($environmentId, fn ($query) => $query->where('environment_id', $environmentId))
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->pluck('reference')
            ->map(function ($value) {
                if (preg_match('/^PCI-(\d+)$/i', (string) $value, $matches)) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max();

        return 'PCI-'.str_pad((string) (($max ?? 0) + 1), 3, '0', STR_PAD_LEFT);
    }

    private function referenceTaken(string $reference, int|null $environmentId, ?int $ignoreId = null): bool
    {
        return PciDocument::query()
            ->when($environmentId, fn ($query) => $query->where('environment_id', $environmentId), fn ($query) => $query->whereNull('environment_id'))
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->whereRaw('lower(reference) = ?', [mb_strtolower($reference)])
            ->exists();
    }

    private function payloadFromValidated(array $data): array
    {
        $fields = [
            'document_name',
            'category',
            'direction',
            'gouvernance_owner',
            'controle_interne',
            'responsible',
            'created_on',
            'last_review_date',
            'periodicity_months',
            'validated_on',
            'validated_by',
            'version',
            'observations',
        ];

        $payload = [];

        foreach ($fields as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];
            $payload[$field] = is_string($value) ? (trim($value) === '' ? null : trim($value)) : $value;
        }

        return $payload;
    }

    private function serialize(PciDocument $row): array
    {
        $state = $row->reviewState();

        return [
            'id' => $row->id,
            'environment_id' => $row->environment_id,
            'environment' => $row->environment ? [
                'id' => $row->environment->id,
                'name' => $row->environment->name,
                'code' => $row->environment->code,
            ] : null,
            'reference' => $row->reference,
            'document_name' => $row->document_name,
            'category' => $row->category,
            'direction' => $row->direction,
            'gouvernance_owner' => $row->gouvernance_owner,
            'controle_interne' => $row->controle_interne,
            'responsible' => $row->responsible,
            'created_on' => $row->created_on?->toDateString(),
            'last_review_date' => $row->last_review_date?->toDateString(),
            'periodicity_months' => (int) ($row->periodicity_months ?: 12),
            'validated_on' => $row->validated_on?->toDateString(),
            'validated_by' => $row->validated_by,
            ...$this->workflowPresentation($row),
            'workflow_validator_name' => $row->workflowValidator?->name,
            'workflow_validated_at' => $row->workflow_validated_at?->toIso8601String(),
            'version' => $row->version,
            'observations' => $row->observations,
            'attachments' => $this->normalizeAttachments($row->attachment_paths ?? []),
            ...$state,
        ];
    }

    private function stats($rows): array
    {
        $counts = [
            'enregistres' => $rows->count(),
            'a_jour' => 0,
            'echeance_90' => 0,
            'en_retard' => 0,
            'a_renseigner' => 0,
        ];

        foreach ($rows as $row) {
            $status = $row['status'] ?? '';

            if ($status === 'a_jour') {
                $counts['a_jour']++;
            } elseif (in_array($status, ['echeance_90', 'echeance_7'], true)) {
                $counts['echeance_90']++;
            } elseif ($status === 'en_retard') {
                $counts['en_retard']++;
            } elseif ($status === 'a_renseigner') {
                $counts['a_renseigner']++;
            }
        }

        return $counts;
    }

    private function normalizeAttachments(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (is_string($item) && $item !== '') {
                $normalized[] = [
                    'path' => $item,
                    'name' => basename($item),
                    'original_name' => basename($item),
                    'right' => 'Lecture',
                    'attached_at' => null,
                ];
                continue;
            }

            if (! is_array($item) || empty($item['path'])) {
                continue;
            }

            $normalized[] = [
                'path' => (string) $item['path'],
                'name' => (string) ($item['name'] ?? basename((string) $item['path'])),
                'original_name' => (string) ($item['original_name'] ?? basename((string) $item['path'])),
                'right' => (string) ($item['right'] ?? 'Lecture'),
                'attached_at' => $item['attached_at'] ?? null,
            ];
        }

        return $normalized;
    }

    /**
     * @return list<array{path: string, name: string, original_name: string, right: string, attached_at: ?string}>
     */
    private function syncedAttachments(PciDocument $row, Request $request): array
    {
        $current = [];
        foreach ($this->normalizeAttachments($row->attachment_paths ?? []) as $item) {
            $current[$item['path']] = $item;
        }

        $next = [];
        $paths = $request->input('kept_paths', []);
        $names = $request->input('kept_names', []);
        $rights = $request->input('kept_rights', []);

        foreach ($paths as $index => $path) {
            $path = (string) $path;
            if (! isset($current[$path]) || ! $this->pathBelongsToDocument($path, $row->id)) {
                continue;
            }

            $item = $current[$path];
            $name = trim((string) ($names[$index] ?? ''));
            $right = $rights[$index] ?? ($item['right'] ?? 'Lecture');
            if (! in_array($right, ['Lecture', 'Modification', 'Confidentiel'], true)) {
                $right = 'Lecture';
            }
            if ($name !== '') {
                $item['name'] = $name;
            }
            $item['right'] = $right;
            $next[] = $item;
            unset($current[$path]);
        }

        foreach ($current as $removed) {
            if ($this->pathBelongsToDocument($removed['path'], $row->id) && Storage::disk('local')->exists($removed['path'])) {
                Storage::disk('local')->delete($removed['path']);
            }
        }

        return $next;
    }

    private function attachmentNameError(Request $request): ?string
    {
        if (! $request->hasFile('attachments')) {
            return null;
        }

        $names = $request->input('attachment_names', []);

        foreach ($request->file('attachments') as $index => $file) {
            if ($file && trim((string) ($names[$index] ?? '')) === '') {
                return 'Saisissez le nom de chaque fichier.';
            }
        }

        return null;
    }

    /**
     * @return list<array{path: string, name: string, original_name: string, right: string, attached_at: string}>
     */
    private function uploadedAttachments(Request $request, int $documentId): array
    {
        if (! $request->hasFile('attachments')) {
            return [];
        }

        $names = $request->input('attachment_names', []);
        $rights = $request->input('attachment_rights', []);
        $items = [];

        foreach ($request->file('attachments') as $index => $file) {
            if (! $file) {
                continue;
            }

            $right = $rights[$index] ?? 'Lecture';
            if (! in_array($right, ['Lecture', 'Modification', 'Confidentiel'], true)) {
                $right = 'Lecture';
            }

            $storedPath = $file->store("pci-documents/{$documentId}", 'local');
            $items[] = [
                'path' => $storedPath,
                'name' => trim((string) ($names[$index] ?? '')),
                'original_name' => $file->getClientOriginalName() ?: basename($storedPath),
                'right' => $right,
                'attached_at' => now()->toIso8601String(),
            ];
        }

        return $items;
    }

    private function pathBelongsToDocument(string $path, int $documentId): bool
    {
        return str_starts_with($path, "pci-documents/{$documentId}/") && ! str_contains($path, '..');
    }

    private function deleteStoredFiles(PciDocument $row): void
    {
        foreach ($this->normalizeAttachments($row->attachment_paths ?? []) as $item) {
            if ($this->pathBelongsToDocument($item['path'], $row->id) && Storage::disk('local')->exists($item['path'])) {
                Storage::disk('local')->delete($item['path']);
            }
        }
    }

    /**
     * @return array{workflow_status: string, workflow_status_fr: string}
     */
    private function workflowPresentation(PciDocument $row): array
    {
        if ($row->workflow_status === PciDocument::WORKFLOW_PENDING_DELETION) {
            return [
                'workflow_status' => PciDocument::WORKFLOW_PENDING_DELETION,
                'workflow_status_fr' => 'Retrait à valider',
            ];
        }

        $pending = $row->isPending() || blank($row->workflow_validated_by);

        return [
            'workflow_status' => $pending ? PciDocument::WORKFLOW_PENDING : PciDocument::WORKFLOW_VALIDATED,
            'workflow_status_fr' => $pending ? 'En attente de validation' : 'Validé',
        ];
    }
}
