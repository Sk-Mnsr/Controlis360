<?php

namespace App\Http\Controllers\API;

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

    protected array $storeRelationArray = [];

    protected array $updateRelationArray = ['environment', 'entity'];

    public function __construct()
    {
        parent::__construct();

        $profileRule = 'required|in:super_admin,admin,controle,metier';

        $this->indexManualFilter = function ($query, $user) {
            $query->with(['environment', 'entity']);

            if ($user->isEnvironmentAdmin() && $user->environment_id) {
                $query->where('environment_id', $user->environment_id);
            }

            return $query;
        };

        $this->storeValidationArray = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'profile' => $profileRule,
            'environment_id' => 'nullable|exists:environments,id',
            'entity_id' => 'nullable|exists:entities,id',
            'metier_role' => 'nullable|required_if:profile,metier|in:responsable_entite,groupe,visiteur',
            'controle_role' => 'nullable|required_if:profile,controle|in:agent_controle_interne,responsable_controle_permanent',
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
                'profile' => 'sometimes|in:super_admin,admin,controle,metier',
                'environment_id' => 'nullable|exists:environments,id',
                'entity_id' => 'nullable|exists:entities,id',
                'metier_role' => 'nullable|in:responsable_entite,groupe,visiteur',
                'controle_role' => 'nullable|in:agent_controle_interne,responsable_controle_permanent',
                'subsidiary_id' => 'nullable|exists:subsidiaries,id',
                'department_id' => 'nullable|exists:departments,id',
                'job_title' => 'nullable|string|max:255',
                'activated' => 'sometimes|boolean',
                'password_change_required' => 'sometimes|boolean',
            ];
        };

        $this->storeManualValidationsFunction = function (array $requestData) {
            $user = auth()->user();

            if ($user?->isEnvironmentAdmin()) {
                if ($requestData['profile'] === 'super_admin') {
                    return [
                        'errors' => ['profile' => ['Vous ne pouvez pas créer un super administrateur']],
                        'status' => 403,
                    ];
                }

                if ((int) ($requestData['environment_id'] ?? 0) !== (int) $user->environment_id) {
                    return [
                        'errors' => ['environment_id' => ['Les utilisateurs doivent appartenir à votre environnement']],
                        'status' => 403,
                    ];
                }
            }

            if ($requestData['profile'] === 'super_admin' && ! $user?->isSuperAdmin()) {
                return [
                    'errors' => ['profile' => ['Seul le super administrateur peut créer ce profil']],
                    'status' => 403,
                ];
            }

            if ($requestData['profile'] === 'admin' && empty($requestData['environment_id'])) {
                return [
                    'errors' => ['environment_id' => ['Un administrateur doit être rattaché à un environnement']],
                    'status' => 422,
                ];
            }

            return null;
        };

        $this->storeBeforeCreateFunction = function (array $requestData) {
            if (isset($requestData['password'])) {
                $requestData['password'] = Hash::make($requestData['password']);
            }

            if (($requestData['profile'] ?? '') === 'super_admin') {
                $requestData['environment_id'] = null;
                $requestData['entity_id'] = null;
                $requestData['metier_role'] = null;
                $requestData['controle_role'] = null;
            }

            if (($requestData['profile'] ?? '') !== 'metier') {
                $requestData['metier_role'] = null;
            }

            if (($requestData['profile'] ?? '') !== 'controle') {
                $requestData['controle_role'] = null;
            }

            return $requestData;
        };

        $this->updateBeforeUpdateFunction = function ($model, array $requestData) {
            if (isset($requestData['password'])) {
                $requestData['password'] = Hash::make($requestData['password']);
            }

            if (isset($requestData['profile'])) {
                if ($requestData['profile'] !== 'metier') {
                    $requestData['metier_role'] = null;
                }

                if ($requestData['profile'] !== 'controle') {
                    $requestData['controle_role'] = null;
                }
            }

            return $requestData;
        };
    }

    public function show(Request $request, int $id)
    {
        $request->merge([
            'with_environment' => 'true',
            'with_entity' => 'true',
        ]);

        return parent::show($request, $id);
    }

    /**
     * Mettre à jour le mot de passe de l'utilisateur connecté
     *
     * @bodyParam current_password  string  required Le mot de passe actuel.     Example: oldpassword
     * @bodyParam new_password      string  required Le nouveau mot de passe.    Example: newpassword
     * @bodyParam new_password_confirmation string required La confirmation du nouveau mot de passe. Example: newpassword
     *
     * @response 200
     */
    public function updatePassword(Request $request)
    {
        // Vérifier l'autorisation
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

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->responseError([
                'current_password' => ['Le mot de passe actuel est incorrect']
            ], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->password_change_required = false;
        $user->save();

        return $this->responseOk([
            'message' => 'Mot de passe modifié avec succès',
            'user' => $user
        ]);
    }
}
