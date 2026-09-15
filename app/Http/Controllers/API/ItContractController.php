<?php

namespace App\Http\Controllers\API;

use App\Models\ItContract;
use App\Models\User;
use Maravel\Http\Controllers\APIController;

/**
 * @group Contrats IT
 *
 * Onglet Contracts — Analyse IT Audit Tool
 */
class ItContractController extends APIController
{
    protected string $modelClass = ItContract::class;

    protected array $indexSearchFieldList = [
        'file_name',
        'provider',
        'beneficiary',
        'scope',
        'purchase_owner',
    ];

    private const FIELD_RULES = [
        'sort_order' => 'nullable|integer|min:0',
        'file_name' => 'nullable|string|max:255',
        'file_link' => 'nullable|string|max:500',
        'provider' => 'nullable|string|max:255',
        'beneficiary' => 'nullable|string|max:255',
        'scope' => 'nullable|string',
        'contract_type' => 'nullable|string|max:50',
        'volume' => 'nullable|string|max:255',
        'cost' => 'nullable|string|max:255',
        'cost_mechanism' => 'nullable|string|max:255',
        'importance' => 'nullable|string|max:50',
        'start_date' => 'nullable|string|max:50',
        'duration' => 'nullable|string|max:100',
        'sla' => 'nullable|string|max:255',
        'termination_notice' => 'nullable|string|max:100',
        'signed_both' => 'nullable|string|max:20',
        'discount' => 'nullable|string|max:100',
        'purchase_owner' => 'nullable|string|max:255',
        'comments' => 'nullable|string',
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

            return $query;
        };

        $this->storeValidationArray = self::FIELD_RULES;
        $this->updateGetValidationArrayFunction = fn (int $id) => self::FIELD_RULES;

        $this->storeBeforeCreateFunction = function (array $requestData) {
            $requestData['created_by_id'] = auth()->id();

            return $requestData;
        };
    }
}
