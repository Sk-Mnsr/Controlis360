<?php

namespace App\Http\Controllers\API;

use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Str;
use Maravel\Http\Controllers\APIController;

/**
 * @group Applications
 *
 * Inventaire du module Cartographie des applications
 */
class ApplicationController extends APIController
{
    protected string $modelClass = Application::class;

    protected array $indexSearchFieldList = [
        'name',
        'code',
        'business_domain',
        'owner',
        'technology',
        'type',
        'users',
    ];

    private const FIELD_RULES = [
        'name' => 'required|string|max:255',
        'code' => 'nullable|string|max:50',
        'business_domain' => 'nullable|string|max:255',
        'main_function' => 'nullable|string',
        'users' => 'nullable|string|max:255',
        'technology' => 'nullable|string|max:255',
        'type' => 'nullable|string|max:255',
        'criticality' => 'nullable|in:faible,moyenne,haute,critique',
        'status' => 'nullable|in:active,inactive,planned,retired',
        'owner' => 'nullable|string|max:255',
        'go_live_date' => 'nullable|date',
        'license_version' => 'nullable|string|max:255',
        'license_expiry_date' => 'nullable|date',
        'license_update' => 'nullable|string|max:255',
        'license_last_renewal_date' => 'nullable|date',
        'license_next_renewal_date' => 'nullable|date',
        'infrastructure' => 'nullable|string|max:255',
        'hosting_type' => 'nullable|string|max:255',
        'backup' => 'nullable|string|max:255',
        'sla' => 'nullable|string|max:255',
        'comment' => 'nullable|string',
        'cost' => 'nullable|string|max:255',
        'impact' => 'nullable|string|max:255',
        'risk' => 'nullable|string|max:255',
        'last_version' => 'nullable|string|max:255',
        'environment_id' => 'nullable|exists:environments,id',
        'entity_id' => 'nullable|exists:entities,id',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->indexWithArray = ['environment', 'entity'];
        $this->showWithArray = ['environment', 'entity', 'createdBy'];

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

        $this->storeValidationArray = array_merge(self::FIELD_RULES, [
            'code' => 'nullable|string|max:50|unique:applications,code',
        ]);

        $this->updateGetValidationArrayFunction = function (int $id) {
            $rules = self::FIELD_RULES;
            $rules['name'] = 'sometimes|string|max:255';
            $rules['code'] = 'sometimes|nullable|string|max:50|unique:applications,code,'.$id;

            return $rules;
        };

        $this->storeBeforeCreateFunction = function (array $requestData) {
            $requestData['created_by_id'] = auth()->id();
            $requestData['status'] = $requestData['status'] ?? 'active';

            if (empty($requestData['code'])) {
                $requestData['code'] = $this->nextAppCode();
            } else {
                $requestData['code'] = Str::upper(trim((string) $requestData['code']));
            }

            return $requestData;
        };

        $this->updateBeforeUpdateFunction = function (Application $model, array $requestData) {
            if (array_key_exists('code', $requestData) && $requestData['code']) {
                $requestData['code'] = Str::upper(trim((string) $requestData['code']));
            }

            return $requestData;
        };
    }

    private function nextAppCode(): string
    {
        $last = Application::query()
            ->where('code', 'like', 'APP-%')
            ->orderByDesc('id')
            ->value('code');

        $number = 1;
        if (is_string($last) && preg_match('/APP-(\d+)/i', $last, $matches)) {
            $number = ((int) $matches[1]) + 1;
        } else {
            $number = ((int) Application::query()->count()) + 1;
        }

        do {
            $candidate = 'APP-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT);
            $exists = Application::query()->where('code', $candidate)->exists();
            $number++;
        } while ($exists);

        return $candidate;
    }
}
