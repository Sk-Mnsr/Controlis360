<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Services\MissionParametrageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maravel\Http\Controllers\APIController;

/**
 * @group Paramétrage missions
 */
class MissionParametrageController extends APIController
{
    public function __construct(private MissionParametrageService $parametrageService) {}

    public function index(Request $request)
    {
        if (! $this->canAccessParametrage($request->user())) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        return $this->responseOk($this->parametrageService->getSettings());
    }

    public function save(Request $request)
    {
        if (! $this->canManageParametrage($request->user())) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $validator = Validator::make($request->all(), [
            'statuses' => 'nullable|array',
            'statuses.*.value' => 'required_with:statuses|string|max:100',
            'statuses.*.label' => 'required_with:statuses|string|max:255',
            'statuses.*.color' => 'required_with:statuses|string|max:20',
            'recommendation_statuses' => 'nullable|array',
            'recommendation_statuses.*.value' => 'required_with:recommendation_statuses|string|max:100',
            'recommendation_statuses.*.label' => 'required_with:recommendation_statuses|string|max:255',
            'recommendation_statuses.*.color' => 'required_with:recommendation_statuses|string|max:20',
            'deadline_rules' => 'nullable|array',
            'deadline_rules.near_threshold_days' => 'nullable|integer|min:0|max:365',
            'deadline_rules.items' => 'nullable|array|min:1',
            'deadline_rules.items.*.key' => 'required_with:deadline_rules.items|string|max:100',
            'deadline_rules.items.*.label' => 'required_with:deadline_rules.items|string|max:255',
            'deadline_rules.items.*.text_color' => 'required_with:deadline_rules.items|string|max:20',
            'deadline_rules.items.*.badge_color' => 'required_with:deadline_rules.items|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        $settings = $this->parametrageService->updateSettings($validator->validated(), $request->user());

        return $this->responseOk($settings);
    }

    private function canAccessParametrage(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->isSuperAdmin()
            || in_array($user->profile, ['controle', 'audit', 'metier', 'regulateur'], true);
    }

    private function canManageParametrage(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->isSuperAdmin()
            || in_array($user->profile, ['controle', 'audit'], true);
    }
}
