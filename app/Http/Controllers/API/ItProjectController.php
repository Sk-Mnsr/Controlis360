<?php

namespace App\Http\Controllers\API;

use App\Models\ItProject;
use App\Models\User;
use Maravel\Http\Controllers\APIController;

/**
 * @group Projets IT
 *
 * Onglets Projets — Analyse IT Audit Tool
 */
class ItProjectController extends APIController
{
    protected string $modelClass = ItProject::class;

    protected array $indexSearchFieldList = [
        'name',
        'objective',
        'status',
        'owner',
        'comment',
    ];

    private const FIELD_RULES = [
        'priority' => 'nullable|integer|min:0|max:999',
        'name' => 'required|string|max:255',
        'objective' => 'nullable|string',
        'status' => 'nullable|string|max:100',
        'progress_pct' => 'nullable|integer|min:0|max:100',
        'results_benefits' => 'nullable|string',
        'owner' => 'nullable|string|max:255',
        'comment' => 'nullable|string',
        'start_date' => 'nullable|string|max:50',
        'end_date' => 'nullable|string|max:50',
        'delivery_date' => 'nullable|string|max:50',
        'environment_id' => 'nullable|exists:environments,id',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->indexWithArray = ['environment'];
        $this->showWithArray = ['environment', 'createdBy'];

        $this->indexManualFilter = function ($query, User $user) {
            if (! $user->isSuperAdmin() && $user->isEnvironmentAdmin()) {
                $environmentIds = $user->environment_ids;
                if (! empty($environmentIds)) {
                    $query->where(function ($builder) use ($environmentIds) {
                        $builder->whereIn('environment_id', $environmentIds)
                            ->orWhereNull('environment_id');
                    });
                }
            }

            return $query->orderByRaw('priority IS NULL, priority ASC')->orderBy('name');
        };

        $this->storeValidationArray = self::FIELD_RULES;
        $this->updateGetValidationArrayFunction = function (int $id) {
            $rules = self::FIELD_RULES;
            $rules['name'] = 'sometimes|string|max:255';

            return $rules;
        };

        $this->storeBeforeCreateFunction = function (array $requestData) {
            $requestData['created_by_id'] = auth()->id();

            return $requestData;
        };
    }
}
