<?php

namespace App\Http\Controllers\API;

use App\Mail\GenericAccountPendingValidationMail;
use App\Mail\GenericAccountValidatedMail;
use App\Models\Environment;
use App\Models\GenericAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Maravel\Http\Controllers\APIController;

class GenericAccountController extends APIController
{
    private const FIELD_RULES = [
        'user_id' => 'required|string|max:120',
        'user_name' => 'nullable|string|max:255',
        'statut' => 'nullable|string|in:active,desactive',
        'forgotten' => 'nullable|string|in:N,Y',
        'account_type' => 'nullable|string|in:Compte de service,Compte applicatif,Compte technique,Compte système,Compte partagé,Compte administrateur',
        'purpose' => 'nullable|string',
        'system_application' => 'nullable|string|max:255',
        'owner' => 'nullable|string|max:255',
        'usage' => 'nullable|string|max:255',
        'privileges_role' => 'nullable|string|max:255',
        'associated_nominative_account' => 'nullable|string|max:255',
        'last_review_date' => 'nullable|date',
        'action_observation' => 'nullable|string',
        'existence_justification' => 'nullable|string',
        'usage_mode' => 'nullable|string|in:Manuel,Automatique',
        'interactive_access' => 'nullable|string|in:Oui,Non',
        'password_managed_by' => 'nullable|string|max:255',
        'mfa' => 'nullable|string|in:Oui,Non,N/A,À confirmer',
        'logging_enabled' => 'nullable|string|in:Oui,Non',
        'periodic_review' => 'nullable|string|in:Oui,Non',
        'risk' => 'nullable|string|in:Élevé,Moyen,Faible',
        'corrective_measure' => 'nullable|string',
        'environment_id' => 'nullable|integer|exists:environments,id',
    ];

    public function index(Request $request)
    {
        $user = $request->user();

        if (! $this->canAccess($user)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        $available = $this->availableEnvironments($user);
        $requestedEnvironmentId = $request->integer('environment_id') ?: null;

        if ($requestedEnvironmentId && ! $available->contains('id', $requestedEnvironmentId)) {
            return $this->responseError(['environment_id' => ['Filiale non autorisée']], 403);
        }

        $environmentId = $requestedEnvironmentId
            ?: ($available->count() === 1 ? $available->first()->id : null);

        $query = GenericAccount::query()
            ->with(['creator:id,name', 'updater:id,name', 'validator:id,name', 'environment:id,name,code'])
            ->orderBy('user_id')
            ->orderBy('id');

        if ($environmentId) {
            $query->where('environment_id', $environmentId);
        } elseif (! $user->isSuperAdmin()) {
            $ids = $available->pluck('id')->all();
            $query->whereIn('environment_id', $ids ?: [-1]);
        }

        $rows = $query->get()->map(fn (GenericAccount $row) => $this->serialize($row));

        return $this->responseOk([
            'rows' => $rows,
            'environment_id' => $environmentId,
            'filiales' => $available->map(fn ($env) => [
                'id' => $env->id,
                'name' => $env->name,
                'code' => $env->code,
            ])->values()->all(),
            'can_select_filiale' => $user->isSuperAdmin() || $user->isEnvironmentAdmin() || $available->count() > 1,
            'permissions' => $this->permissions($user),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (! $this->canWrite($user)) {
            return $this->responseError(['auth' => ['Seul un Agent IT ou Responsable IT peut enregistrer une ligne']], 403);
        }

        $validator = Validator::make($request->all(), self::FIELD_RULES);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $data = $validator->validated();
        $environmentId = $this->resolveWritableEnvironmentId($user, $data['environment_id'] ?? null);

        if ($environmentId === false) {
            return $this->responseError(['environment_id' => ['Filiale non autorisée']], 403);
        }

        $row = GenericAccount::query()->create([
            ...$this->payloadFromValidated($data),
            'environment_id' => $environmentId,
            'workflow_status' => GenericAccount::WORKFLOW_PENDING,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'validated_by' => null,
            'validated_at' => null,
        ]);

        $row = $row->fresh(['creator', 'updater', 'validator', 'environment']);
        $this->notifyResponsablesPending($row, $user);

        return $this->responseOk($this->serialize($row), 201);
    }

    public function update(Request $request, int $id)
    {
        $user = $request->user();

        if (! $this->canWrite($user)) {
            return $this->responseError(['auth' => ['Seul un Agent IT ou Responsable IT peut modifier une ligne']], 403);
        }

        $row = GenericAccount::query()->find($id);

        if (! $row || ! $this->userCanAccessRow($user, $row)) {
            return $this->responseError(['message' => ['Ligne introuvable']], 404);
        }

        $validator = Validator::make($request->all(), self::FIELD_RULES);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $data = $validator->validated();
        $environmentId = array_key_exists('environment_id', $data)
            ? $this->resolveWritableEnvironmentId($user, $data['environment_id'] ?? null)
            : $row->environment_id;

        if ($environmentId === false) {
            return $this->responseError(['environment_id' => ['Filiale non autorisée']], 403);
        }

        $row->fill($this->payloadFromValidated($data));
        $row->environment_id = $environmentId;
        $row->workflow_status = GenericAccount::WORKFLOW_PENDING;
        $row->updated_by = $user->id;
        $row->validated_by = null;
        $row->validated_at = null;
        $row->save();

        $row = $row->fresh(['creator', 'updater', 'validator', 'environment']);
        $this->notifyResponsablesPending($row, $user);

        return $this->responseOk($this->serialize($row));
    }

    public function validateRow(Request $request, int $id)
    {
        $user = $request->user();

        if (! $this->canValidate($user)) {
            return $this->responseError(['auth' => ['Seul le Responsable IT peut valider']], 403);
        }

        $row = GenericAccount::query()->find($id);

        if (! $row || ! $this->userCanAccessRow($user, $row)) {
            return $this->responseError(['message' => ['Ligne introuvable']], 404);
        }

        if ($row->workflow_status === GenericAccount::WORKFLOW_VALIDATED) {
            return $this->responseError(['workflow_status' => ['Cette ligne est déjà validée']], 422);
        }

        $row->workflow_status = GenericAccount::WORKFLOW_VALIDATED;
        $row->validated_by = $user->id;
        $row->validated_at = now();
        $row->save();

        $row = $row->fresh(['creator', 'updater', 'validator', 'environment']);
        $this->notifyCreatorValidated($row, $user);

        return $this->responseOk($this->serialize($row));
    }

    public function destroy(Request $request, int $id)
    {
        $user = $request->user();

        if (! $this->canWrite($user) && ! $this->canValidate($user)) {
            return $this->responseError(['auth' => ['Accès non autorisé']], 403);
        }

        $row = GenericAccount::query()->find($id);

        if (! $row || ! $this->userCanAccessRow($user, $row)) {
            return $this->responseError(['message' => ['Ligne introuvable']], 404);
        }

        if ($row->isValidated() && ! $this->canValidate($user) && ! $user->isPlatformAdministrator()) {
            return $this->responseError(['auth' => ['Une ligne validée ne peut être supprimée que par le Responsable IT']], 403);
        }

        $row->delete();

        return $this->responseOk(['deleted' => true]);
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
        $profile = $this->gouvernanceProfile($user);

        return in_array($profile, ['super_admin', 'admin', 'agent_it', 'responsable_it'], true);
    }

    private function canValidate(User $user): bool
    {
        $profile = $this->gouvernanceProfile($user);

        return in_array($profile, ['super_admin', 'admin', 'responsable_it'], true);
    }

    private function permissions(User $user): array
    {
        $canValidate = $this->canValidate($user);
        $canWrite = $this->canWrite($user);

        return [
            'can_create' => $canWrite,
            'can_edit' => $canWrite,
            'can_validate' => $canValidate,
            // Agent IT : suppression uniquement des lignes non validées.
            'can_delete' => $canWrite,
            'can_delete_validated' => $canValidate || $user->isPlatformAdministrator(),
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

        if ($available->count() === 1) {
            return (int) $available->first()->id;
        }

        if ($user->isSuperAdmin() && $available->isNotEmpty()) {
            return (int) $available->first()->id;
        }

        return $available->isNotEmpty() ? (int) $available->first()->id : null;
    }

    private function userCanAccessRow(User $user, GenericAccount $row): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $row->environment_id) {
            return true;
        }

        return $this->availableEnvironments($user)->contains('id', (int) $row->environment_id);
    }

    private function notifyResponsablesPending(GenericAccount $row, User $sender): void
    {
        $recipients = $this->responsablesItForEnvironment($row->environment_id)
            ->reject(fn (User $user) => $user->id === $sender->id || blank($user->email));

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(
                    new GenericAccountPendingValidationMail($row, $recipient, $sender)
                );
            } catch (\Throwable $e) {
                Log::warning('Envoi mail validation compte générique échoué', [
                    'account_id' => $row->id,
                    'recipient_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function notifyCreatorValidated(GenericAccount $row, User $validator): void
    {
        $creator = $row->creator;
        if (! $creator || blank($creator->email) || $creator->id === $validator->id) {
            return;
        }

        try {
            Mail::to($creator->email)->send(
                new GenericAccountValidatedMail($row, $creator, $validator)
            );
        } catch (\Throwable $e) {
            Log::warning('Envoi mail compte générique validé échoué', [
                'account_id' => $row->id,
                'recipient_id' => $creator->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function responsablesItForEnvironment(?int $environmentId): Collection
    {
        return User::query()
            ->where('activated', true)
            ->whereNotNull('email')
            ->where(function ($query) {
                $query->where('profile', 'responsable_it')
                    ->orWhere('module_profiles->gouvernance-it->profile', 'responsable_it');
            })
            ->when($environmentId, function ($query) use ($environmentId) {
                $query->where(function ($inner) use ($environmentId) {
                    $inner->whereHas('environments', fn ($env) => $env->where('environments.id', $environmentId))
                        ->orWhereDoesntHave('environments');
                });
            })
            ->get();
    }

    private function payloadFromValidated(array $data): array
    {
        $fields = array_keys(self::FIELD_RULES);
        $payload = [];

        foreach ($fields as $field) {
            if ($field === 'environment_id') {
                continue;
            }

            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        return $payload;
    }

    private function serialize(GenericAccount $row): array
    {
        return [
            'id' => $row->id,
            'environment_id' => $row->environment_id,
            'environment' => $row->environment ? [
                'id' => $row->environment->id,
                'name' => $row->environment->name,
                'code' => $row->environment->code,
            ] : null,
            'user_id' => $row->user_id,
            'user_name' => $row->user_name,
            'statut' => $row->statut,
            'forgotten' => $row->forgotten,
            'account_type' => $row->account_type,
            'purpose' => $row->purpose,
            'system_application' => $row->system_application,
            'owner' => $row->owner,
            'usage' => $row->usage,
            'privileges_role' => $row->privileges_role,
            'associated_nominative_account' => $row->associated_nominative_account,
            'last_review_date' => $row->last_review_date?->format('Y-m-d'),
            'action_observation' => $row->action_observation,
            'existence_justification' => $row->existence_justification,
            'usage_mode' => $row->usage_mode,
            'interactive_access' => $row->interactive_access,
            'password_managed_by' => $row->password_managed_by,
            'mfa' => $row->mfa,
            'logging_enabled' => $row->logging_enabled,
            'periodic_review' => $row->periodic_review,
            'risk' => $row->risk,
            'corrective_measure' => $row->corrective_measure,
            'workflow_status' => $row->workflow_status,
            'workflow_status_fr' => $row->isValidated() ? 'Validé' : 'En attente de validation',
            'created_by' => $row->creator?->name,
            'updated_by' => $row->updater?->name,
            'validated_by' => $row->validator?->name,
            'validated_at' => $row->validated_at?->toIso8601String(),
            'created_at' => $row->created_at?->toIso8601String(),
            'updated_at' => $row->updated_at?->toIso8601String(),
        ];
    }
}
