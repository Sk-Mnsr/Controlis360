<?php

namespace App\Http\Controllers\API;

use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maravel\Http\Controllers\APIController;

/**
 * @group Entités
 */
class EntityController extends APIController
{
    protected string $modelClass = Entity::class;

    protected array $indexSearchFieldList = ['name', 'code'];

    public function __construct()
    {
        parent::__construct();

        $this->indexWithArray = ['environment'];

        $this->indexManualFilter = function ($query, $user) {
            if ($user->isEnvironmentAdmin() && $user->environment_id) {
                $query->where('environment_id', $user->environment_id);
            }

            return $query;
        };

        $this->storeValidationArray = [
            'environment_id' => 'required|exists:environments,id',
            'type' => 'required|in:department,agency',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
        ];

        $this->updateGetValidationArrayFunction = function (int $id) {
            return [
                'environment_id' => 'sometimes|exists:environments,id',
                'type' => 'sometimes|in:department,agency',
                'name' => 'sometimes|string|max:255',
                'code' => 'sometimes|string|max:50',
                'is_active' => 'sometimes|boolean',
                'sort_order' => 'sometimes|integer|min:0',
            ];
        };

        $this->storeManualValidationsFunction = function (array $requestData) {
            $user = auth()->user();

            if ($user?->isEnvironmentAdmin() && (int) $requestData['environment_id'] !== (int) $user->environment_id) {
                return [
                    'errors' => ['environment_id' => ['Vous ne pouvez gérer que votre environnement']],
                    'status' => 403,
                ];
            }

            return null;
        };

        $this->storeBeforeCreateFunction = function (array $requestData) {
            if (empty($requestData['code'])) {
                $requestData['code'] = Str::upper(Str::slug($requestData['name'], '_'));
            }

            return $requestData;
        };
    }

    /**
     * Entités d'un environnement donné.
     */
    public function byEnvironment(Request $request, int $environmentId)
    {
        $user = $request->user();

        if ($user->isEnvironmentAdmin() && ! $user->belongsToEnvironment($environmentId)) {
            return $this->responseError(['auth' => ['Accès non autorisé à cet environnement']], 403);
        }

        $entities = Entity::query()
            ->with('environment')
            ->where('environment_id', $environmentId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->responseOk($entities);
    }
}
