<?php

namespace App\Http\Controllers\API;

use App\Models\Entity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maravel\Http\Controllers\APIController;

/**
 * @group Utilisateurs
 *
 * EndPoints pour gérer les utilisateurs
 */
class UserController extends APIController
{
    protected string $modelClass = User::class;

    protected array $indexSearchFieldList = ['name', 'email'];

    protected array $storeRelationArray = ['environments', 'entities'];

    protected array $updateRelationArray = ['environments', 'entities'];

    private const PROFILE_RULE = 'super_admin,admin,superviseur,regulateur,controle,audit,metier';

    public function __construct()
    {
        parent::__construct();

        $this->indexManualFilter = function ($query, $user) {
            $query->with(['environments', 'entities']);

            if ($user->isEnvironmentAdmin()) {
                $adminEnvironmentIds = $user->environment_ids;
                if (! empty($adminEnvironmentIds)) {
                    $query->whereHas('environments', function ($environmentQuery) use ($adminEnvironmentIds) {
                        $environmentQuery->whereIn('environments.id', $adminEnvironmentIds);
                    });
                }
            }

            return $query;
        };

        $this->storeValidationArray = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'profile' => 'required|in:' . self::PROFILE_RULE,
            'environment_ids' => 'nullable|array',
            'environment_ids.*' => 'integer|exists:environments,id',
            'entity_ids' => 'nullable|array',
            'entity_ids.*' => 'integer|exists:entities,id',
            'metier_role' => 'nullable|required_if:profile,metier|in:responsable_entite,groupe,visiteur,agent',
            'controle_role' => 'nullable|required_if:profile,controle|in:agent_controle_interne,responsable_controle_permanent',
            'audit_role' => 'nullable|required_if:profile,audit|in:agent_audit,responsable_audit',
            'subsidiary_id' => 'nullable|exists:subsidiaries,id',
            'department_id' => 'nullable|exists:departments,id',
            'job_title' => 'nullable|string|max:255',
            'activated' => 'sometimes|boolean',
            'password_change_required' => 'sometimes|boolean',
        ];

        $this->updateGetValidationArrayFunction = function (int $id) {
            return [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
                'password' => 'sometimes|string|min:8',
                'profile' => 'sometimes|in:' . self::PROFILE_RULE,
                'environment_ids' => 'nullable|array',
                'environment_ids.*' => 'integer|exists:environments,id',
                'entity_ids' => 'nullable|array',
                'entity_ids.*' => 'integer|exists:entities,id',
                'metier_role' => 'nullable|in:responsable_entite,groupe,visiteur,agent',
                'controle_role' => 'nullable|in:agent_controle_interne,responsable_controle_permanent',
                'audit_role' => 'nullable|in:agent_audit,responsable_audit',
                'subsidiary_id' => 'nullable|exists:subsidiaries,id',
                'department_id' => 'nullable|exists:departments,id',
                'job_title' => 'nullable|string|max:255',
                'activated' => 'sometimes|boolean',
                'password_change_required' => 'sometimes|boolean',
            ];
        };

        $this->storeManualValidationsFunction = fn (array $requestData) => $this->validateScope($requestData, auth()->user());

        $this->updateManualValidationsFunction = fn (array $requestData, User $model) => $this->validateScope($requestData, auth()->user(), $model);

        $this->storeBeforeCreateFunction = function (array $requestData) {
            if (isset($requestData['password'])) {
                $requestData['password'] = Hash::make($requestData['password']);
            }

            return $this->normalizeUserPayload($requestData);
        };

        $this->storeAfterCreateFunction = function (User $model, array $requestData) {
            $this->syncUserScopes($model, $requestData);

            return $model;
        };

        $this->updateBeforeUpdateFunction = function (User $model, array $requestData) {
            if (isset($requestData['password'])) {
                $requestData['password'] = Hash::make($requestData['password']);
            }

            return $this->normalizeUserPayload($requestData, $model);
        };

        $this->updateAfterUpdateFunction = function (User $model, array $requestData) {
            if (array_key_exists('environment_ids', $requestData) || array_key_exists('entity_ids', $requestData)) {
                $this->syncUserScopes($model, $requestData);
            }

            return $model;
        };
    }

    public function show(Request $request, int $id)
    {
        $request->merge([
            'with_environments' => 'true',
            'with_entities' => 'true',
        ]);

        return parent::show($request, $id);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();
        $this->authorize('updatePassword', $user);

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'Le mot de passe actuel est requis',
            'new_password.required' => 'Le nouveau mot de passe est requis',
            'new_password.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères',
            'new_password.confirmed' => 'La confirmation du mot de passe ne correspond pas',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->responseError([
                'current_password' => ['Le mot de passe actuel est incorrect'],
            ], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->password_change_required = false;
        $user->save();

        return $this->responseOk([
            'message' => 'Mot de passe modifié avec succès',
            'user' => $user,
        ]);
    }

    private function normalizeScopeInput(array $requestData): array
    {
        if (! isset($requestData['environment_ids']) && isset($requestData['environment_id'])) {
            $requestData['environment_ids'] = [(int) $requestData['environment_id']];
        }

        if (! isset($requestData['entity_ids']) && isset($requestData['entity_id'])) {
            $requestData['entity_ids'] = [(int) $requestData['entity_id']];
        }

        if (isset($requestData['environment_ids'])) {
            $requestData['environment_ids'] = array_values(array_unique(array_map('intval', $requestData['environment_ids'])));
        }

        if (isset($requestData['entity_ids'])) {
            $requestData['entity_ids'] = array_values(array_unique(array_map('intval', $requestData['entity_ids'])));
        }

        return $requestData;
    }

    private function validateScope(array $requestData, ?User $actor, ?User $target = null): ?array
    {
        $requestData = $this->normalizeScopeInput($requestData);
        $profile = $requestData['profile'] ?? $target?->profile;

        if ($profile === 'super_admin') {
            return null;
        }

        $environmentIds = $requestData['environment_ids'] ?? $target?->environment_ids ?? [];
        $entityIds = $requestData['entity_ids'] ?? $target?->entity_ids ?? [];

        if (empty($environmentIds)) {
            return [
                'errors' => ['environment_ids' => ['Au moins un environnement est requis pour ce profil']],
                'status' => 422,
            ];
        }

        if ($profile === 'admin' && count($environmentIds) < 1) {
            return [
                'errors' => ['environment_ids' => ['Un administrateur doit être rattaché à au moins un environnement']],
                'status' => 422,
            ];
        }

        if (! empty($entityIds)) {
            $validEntityCount = Entity::query()
                ->whereIn('id', $entityIds)
                ->whereIn('environment_id', $environmentIds)
                ->count();

            if ($validEntityCount !== count($entityIds)) {
                return [
                    'errors' => ['entity_ids' => ['Certaines entités ne correspondent pas aux environnements sélectionnés']],
                    'status' => 422,
                ];
            }
        }

        if ($actor?->isEnvironmentAdmin()) {
            if (($requestData['profile'] ?? $target?->profile) === 'super_admin') {
                return [
                    'errors' => ['profile' => ['Vous ne pouvez pas créer un super administrateur']],
                    'status' => 403,
                ];
            }

            $adminEnvironmentIds = $actor->environment_ids;
            if (! empty(array_diff($environmentIds, $adminEnvironmentIds))) {
                return [
                    'errors' => ['environment_ids' => ['Les utilisateurs doivent appartenir à vos environnements']],
                    'status' => 403,
                ];
            }
        }

        if (($requestData['profile'] ?? null) === 'super_admin' && ! $actor?->isSuperAdmin()) {
            return [
                'errors' => ['profile' => ['Seul le super administrateur peut créer ce profil']],
                'status' => 403,
            ];
        }

        return null;
    }

    private function normalizeUserPayload(array $requestData, ?User $model = null): array
    {
        $profile = $requestData['profile'] ?? $model?->profile;

        if ($profile === 'super_admin') {
            $requestData['metier_role'] = null;
            $requestData['controle_role'] = null;
            $requestData['audit_role'] = null;
            $requestData['environment_ids'] = [];
            $requestData['entity_ids'] = [];
        }

        if ($profile !== 'metier') {
            $requestData['metier_role'] = null;
        }

        if ($profile !== 'controle') {
            $requestData['controle_role'] = null;
        }

        if ($profile !== 'audit') {
            $requestData['audit_role'] = null;
        }

        unset($requestData['environment_id'], $requestData['entity_id']);

        return $requestData;
    }

    private function syncUserScopes(User $model, array $requestData): void
    {
        if (($requestData['profile'] ?? $model->profile) === 'super_admin') {
            $model->environments()->sync([]);
            $model->entities()->sync([]);

            return;
        }

        if (array_key_exists('environment_ids', $requestData)) {
            $model->environments()->sync($requestData['environment_ids'] ?? []);
        }

        if (array_key_exists('entity_ids', $requestData)) {
            $model->entities()->sync($requestData['entity_ids'] ?? []);
        }
    }
}
