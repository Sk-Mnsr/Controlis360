<?php

namespace App\Http\Controllers\API;

use App\Models\RegulatoryReportingContribution;
use App\Models\RegulatoryReportingFiche;
use App\Models\User;
use App\Services\RegulatoryReportingRecipientResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maravel\Http\Controllers\APIController;

/**
 * @group Fiches de reporting réglementaire
 */
class RegulatoryReportingFicheController extends APIController
{
    public function __construct(
        private RegulatoryReportingRecipientResolver $recipientResolver,
    ) {}

    public function index(Request $request)
    {
        if (! $this->canAccessConformite($request->user())) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $query = RegulatoryReportingFiche::query()
            ->with([
                'creator:id,name',
                'environment:id,name,code',
                'deposantEntity:id,name,code',
                'etabliParEntity:id,name,code',
                'contributions',
            ])
            ->withCount('contributions')
            ->orderByDesc('id');

        $this->applyEnvironmentScope($query, $request->user());

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('fiche_number', 'like', "%{$search}%")
                    ->orWhere('type_reporting', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        return $this->responseOk($query->paginate((int) $request->query('per_page', 20)));
    }

    public function inbox(Request $request)
    {
        $user = $request->user();

        if (! $this->canAccessConformite($user) && ! $this->canReceiveFiches($user)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $query = RegulatoryReportingFiche::query()
            ->with([
                'creator:id,name',
                'environment:id,name,code',
                'deposantEntity:id,name,code',
                'etabliParEntity:id,name,code',
                'contributions.creator:id,name',
            ])
            ->orderByDesc('id');

        if (! $user->isSuperAdmin()) {
            $entityIds = $user->entity_ids;
            if (empty($entityIds)) {
                return $this->responseOk([
                    'data' => [],
                    'current_page' => 1,
                    'per_page' => 20,
                    'total' => 0,
                ]);
            }

            $query->whereIn('etabli_par_entity_id', $entityIds);
            $this->applyEnvironmentScope($query, $user);
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('fiche_number', 'like', "%{$search}%")
                    ->orWhere('type_reporting', 'like', "%{$search}%")
                    ->orWhere('etabli_par', 'like', "%{$search}%");
            });
        }

        return $this->responseOk($query->paginate((int) $request->query('per_page', 20)));
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $fiche = RegulatoryReportingFiche::query()
            ->with([
                'creator:id,name',
                'environment:id,name,code',
                'deposantEntity:id,name,code',
                'etabliParEntity:id,name,code',
                'contributions.creator:id,name',
            ])
            ->findOrFail($id);

        if (! $this->canViewFiche($user, $fiche)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        return $this->responseOk($this->formatFiche($fiche, $user));
    }

    public function store(Request $request)
    {
        if (! $this->canManageFiches($request->user())) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $validator = Validator::make($request->all(), $this->validationRules());

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        $data = $this->normalizePayload($validator->validated(), $request->user());
        $data['status'] = 'envoyee';

        $fiche = RegulatoryReportingFiche::query()->create([
            ...$data,
            'attachment_paths' => [],
            'created_by' => $request->user()->id,
        ]);

        $paths = $this->storeAttachments($request, $fiche->id);
        $fiche->update([
            'attachment_paths' => $paths,
            'pj_required' => $data['pj_required'] || count($paths) > 0,
        ]);

        return $this->responseOk(
            $this->formatFiche($fiche->fresh()->load([
                'creator:id,name',
                'environment:id,name,code',
                'deposantEntity:id,name,code',
                'etabliParEntity:id,name,code',
                'contributions.creator:id,name',
            ]), $request->user()),
            201,
        );
    }

    public function update(Request $request, int $id)
    {
        if (! $this->canManageFiches($request->user())) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $fiche = RegulatoryReportingFiche::query()->findOrFail($id);

        if (! $this->canViewFiche($request->user(), $fiche)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $validator = Validator::make($request->all(), $this->validationRules(true));

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        $data = $this->normalizePayload($validator->validated(), $request->user(), $fiche);

        $keptPaths = $this->resolveKeptAttachments($request, $fiche);
        $newPaths = $this->storeAttachments($request, $fiche->id);
        $paths = array_values(array_unique([...$keptPaths, ...$newPaths]));

        $removed = array_diff($fiche->attachment_paths ?? [], $keptPaths);
        foreach ($removed as $path) {
            Storage::disk('local')->delete($path);
        }

        $fiche->update([
            ...$data,
            'attachment_paths' => $paths,
            'pj_required' => ($data['pj_required'] ?? false) || count($paths) > 0,
        ]);

        return $this->responseOk($this->formatFiche(
            $fiche->fresh()->load([
                'creator:id,name',
                'environment:id,name,code',
                'deposantEntity:id,name,code',
                'etabliParEntity:id,name,code',
                'contributions.creator:id,name',
            ]),
            $request->user(),
        ));
    }

    public function storeContribution(Request $request, int $id)
    {
        $user = $request->user();
        $fiche = RegulatoryReportingFiche::query()->findOrFail($id);

        if (! $this->recipientResolver->userCanReceive($user, $fiche->etabli_par_entity_id)
            && ! $user->isSuperAdmin()) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        if (! $this->canViewFiche($user, $fiche)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $validator = Validator::make($request->all(), [
            'valeur' => 'nullable|string|max:255',
            'contenu' => 'nullable|string',
            'date' => 'nullable|date',
            'attachment_names' => 'nullable|array',
            'attachment_names.*' => 'nullable|string|max:255',
            'attachment_files' => 'nullable|array',
            'attachment_files.*' => 'nullable|file|max:10240',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        $data = $validator->validated();
        $names = $data['attachment_names'] ?? [];
        if (! is_array($names)) {
            $names = [];
        }

        $files = $request->file('attachment_files', []);
        if (! is_array($files)) {
            $files = $files ? [0 => $files] : [];
        }

        $indexes = collect(array_keys($names))
            ->merge(array_keys($files))
            ->map(fn ($key) => (int) $key)
            ->unique()
            ->sort()
            ->values();

        $attachments = [];
        foreach ($indexes as $index) {
            $name = trim((string) ($names[$index] ?? ''));
            $file = $files[$index] ?? null;
            $path = null;

            if ($file) {
                $path = $file->store("regulatory-reporting/{$fiche->id}/contributions", 'local');
            }

            if ($path || $name !== '') {
                $attachments[] = [
                    'nom' => $name !== '' ? $name : null,
                    'path' => $path,
                ];
            }
        }

        $contribution = RegulatoryReportingContribution::query()->create([
            'fiche_id' => $fiche->id,
            'valeur' => trim((string) ($data['valeur'] ?? '')) ?: null,
            'contenu' => trim((string) ($data['contenu'] ?? '')) ?: null,
            'date' => $data['date'] ?? null,
            'nom' => $attachments[0]['nom'] ?? null,
            'attachment_path' => $attachments[0]['path'] ?? null,
            'attachments' => $attachments,
            'created_by' => $user->id,
        ]);

        if ($fiche->status === 'envoyee') {
            $fiche->update(['status' => 'en_traitement']);
        }

        return $this->responseOk(
            $contribution->load('creator:id,name'),
            201,
        );
    }

    public function destroy(Request $request, int $id)
    {
        if (! $this->canManageFiches($request->user())) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $fiche = RegulatoryReportingFiche::query()->findOrFail($id);

        if (! $this->canViewFiche($request->user(), $fiche)) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        foreach ($fiche->attachment_paths ?? [] as $path) {
            Storage::disk('local')->delete($path);
        }

        foreach ($fiche->contributions as $contribution) {
            foreach ($contribution->attachment_items as $item) {
                if (! empty($item['path'])) {
                    Storage::disk('local')->delete($item['path']);
                }
            }
        }

        $fiche->delete();

        return $this->responseOk(['deleted' => true]);
    }

    private function formatFiche(RegulatoryReportingFiche $fiche, User $user): array
    {
        return [
            ...$fiche->toArray(),
            'can_edit' => $this->canManageFiches($user) && $this->canViewFiche($user, $fiche),
            'can_contribute' => $user->isSuperAdmin()
                || $this->recipientResolver->userCanReceive($user, $fiche->etabli_par_entity_id),
        ];
    }

    private function validationRules(bool $isUpdate = false): array
    {
        return [
            'fiche_number' => 'nullable|string|max:80',
            'type_reporting' => 'nullable|string',
            'destinataires' => 'nullable',
            'reference' => 'nullable|string',
            'pj_required' => 'sometimes|boolean',
            'elements' => 'nullable',
            'canals' => 'nullable',
            'periodicites' => 'nullable',
            'deposant' => 'nullable|string|max:255',
            'etabli_par' => 'nullable|string|max:255',
            'deposant_entity_id' => 'nullable|integer|exists:entities,id',
            'etabli_par_entity_id' => 'nullable|integer|exists:entities,id',
            'date_validation' => 'nullable|date',
            'delai_transmission' => 'nullable|date',
            'environment_id' => 'nullable|integer|exists:environments,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
            'keep_attachment_paths' => ($isUpdate ? 'nullable' : 'prohibited').'|array',
            'keep_attachment_paths.*' => 'string|max:500',
            'attachments_synced' => 'sometimes|boolean',
        ];
    }

    private function normalizePayload(array $data, User $user, ?RegulatoryReportingFiche $fiche = null): array
    {
        unset($data['attachments'], $data['keep_attachment_paths'], $data['attachments_synced']);

        $stringLists = ['destinataires', 'elements', 'canals', 'periodicites'];
        foreach ($stringLists as $key) {
            $data[$key] = $this->normalizeListInput($data[$key] ?? []);
        }

        foreach (['fiche_number', 'type_reporting', 'reference'] as $key) {
            if (array_key_exists($key, $data)) {
                $trimmed = trim((string) ($data[$key] ?? ''));
                $data[$key] = $trimmed !== '' ? $trimmed : null;
            }
        }

        $data['pj_required'] = filter_var($data['pj_required'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! array_key_exists('environment_id', $data) || $data['environment_id'] === null || $data['environment_id'] === '') {
            $data['environment_id'] = $fiche?->environment_id ?? ($user->environment_ids[0] ?? null);
        }

        if (! $user->isSuperAdmin() && $data['environment_id'] !== null) {
            if (! $user->belongsToEnvironment((int) $data['environment_id'])) {
                $data['environment_id'] = $user->environment_ids[0] ?? null;
            }
        }

        if (array_key_exists('deposant_entity_id', $data)) {
            $deposantEntityId = $data['deposant_entity_id'] !== '' && $data['deposant_entity_id'] !== null
                ? (int) $data['deposant_entity_id']
                : null;
            $data['deposant_entity_id'] = $deposantEntityId;
            $data['deposant'] = $this->recipientResolver->resolveEntityLabel($deposantEntityId);
        }

        if (array_key_exists('etabli_par_entity_id', $data)) {
            $etabliParEntityId = $data['etabli_par_entity_id'] !== '' && $data['etabli_par_entity_id'] !== null
                ? (int) $data['etabli_par_entity_id']
                : null;
            $data['etabli_par_entity_id'] = $etabliParEntityId;
            $data['etabli_par'] = $this->recipientResolver->resolveEntityLabel($etabliParEntityId);
        }

        return $data;
    }

    private function normalizeListInput(mixed $values): array
    {
        if (is_string($values)) {
            $decoded = json_decode($values, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $values = $decoded;
            } elseif ($values !== '') {
                $values = [$values];
            } else {
                $values = [];
            }
        }

        if (! is_array($values)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($value) => trim((string) $value),
            $values,
        ), fn ($value) => $value !== ''));
    }

    private function storeAttachments(Request $request, int $ficheId): array
    {
        $paths = [];

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if (! $file) {
                    continue;
                }
                $paths[] = $file->store("regulatory-reporting/{$ficheId}", 'local');
            }
        }

        return $paths;
    }

    private function resolveKeptAttachments(Request $request, RegulatoryReportingFiche $fiche): array
    {
        $existing = $fiche->attachment_paths ?? [];

        if (! $request->boolean('attachments_synced') && ! $request->exists('keep_attachment_paths')) {
            return $existing;
        }

        $kept = $request->input('keep_attachment_paths', []);
        if (! is_array($kept)) {
            return [];
        }

        return array_values(array_filter(
            $kept,
            fn ($path) => is_string($path) && $path !== '' && in_array($path, $existing, true),
        ));
    }

    private function applyEnvironmentScope($query, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $environmentIds = $user->environment_ids;
        $query->where(function ($builder) use ($environmentIds) {
            $builder->whereIn('environment_id', $environmentIds)
                ->orWhereNull('environment_id');
        });
    }

    private function canAccessConformite(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return in_array($user->profile, ['super_admin', 'conformite'], true);
    }

    private function canManageFiches(?User $user): bool
    {
        return $this->canAccessConformite($user);
    }

    private function canReceiveFiches(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->profile === 'metier' && $user->metier_role === 'responsable_entite';
    }

    private function canViewFiche(User $user, RegulatoryReportingFiche $fiche): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($this->canAccessConformite($user)) {
            if ($fiche->environment_id === null) {
                return true;
            }

            return $user->belongsToEnvironment((int) $fiche->environment_id);
        }

        return $this->recipientResolver->userCanReceive($user, $fiche->etabli_par_entity_id)
            && (
                $fiche->environment_id === null
                || $user->belongsToEnvironment((int) $fiche->environment_id)
            );
    }
}
