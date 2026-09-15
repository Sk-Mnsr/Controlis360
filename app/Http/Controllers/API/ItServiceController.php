<?php

namespace App\Http\Controllers\API;

use App\Models\ApplicationType;
use App\Services\ItServiceDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maravel\Http\Controllers\APIController;

/**
 * @group Services IT
 *
 * Accueil Cartographie des applications — types, questionnaire et taux de remplissage
 */
class ItServiceController extends APIController
{
    public function __construct(private ItServiceDashboardService $dashboardService) {}

    public function dashboard()
    {
        return $this->responseOk($this->dashboardService->dashboard());
    }

    public function questions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'scope' => 'required|in:generic,security,type',
            'application_type_id' => 'nullable|required_if:scope,type|exists:application_types,id',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $data = $validator->validated();
        $scope = $data['scope'];
        $typeId = isset($data['application_type_id']) ? (int) $data['application_type_id'] : null;

        $type = null;
        if ($scope === 'type' && $typeId) {
            $type = ApplicationType::query()->find($typeId);
        }

        return $this->responseOk([
            'scope' => $scope,
            'application_type' => $type ? [
                'id' => $type->id,
                'code' => $type->code,
                'name' => $type->name,
                'accent_color' => $type->accent_color,
            ] : null,
            'questions' => $this->dashboardService->questionsForScope($scope, $typeId),
            'fill_rate' => $this->dashboardService->scopeFillRate($scope, $typeId),
        ]);
    }

    public function saveAnswers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'scope' => 'required|in:generic,security,type',
            'application_type_id' => 'nullable|required_if:scope,type|exists:application_types,id',
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer|exists:application_questions,id',
            'answers.*.value' => 'nullable|string',
            'answers.*.details' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $data = $validator->validated();
        $fill = $this->dashboardService->saveAnswers(
            $data['scope'],
            isset($data['application_type_id']) ? (int) $data['application_type_id'] : null,
            $data['answers'],
            $request->user()?->id,
        );

        return $this->responseOk([
            'fill_rate' => $fill,
            'dashboard' => $this->dashboardService->dashboard(),
        ]);
    }

    public function updateService(Request $request, int $applicationTypeId)
    {
        $type = ApplicationType::query()->findOrFail($applicationTypeId);

        $validator = Validator::make($request->all(), [
            'exists_flag' => 'nullable|string|max:10',
            'solution_name' => 'nullable|string|max:255',
            'editor' => 'nullable|string|max:255',
            'importance' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:255',
            'last_version' => 'nullable|string|max:255',
            'sla_exists' => 'nullable|string|max:10',
            'hosting_mode' => 'nullable|string|max:255',
            'users_count' => 'nullable|string|max:255',
            'licenses_count' => 'nullable|string|max:255',
            'license_type' => 'nullable|string|max:255',
            'customization_level' => 'nullable|string|max:255',
            'backups' => 'nullable|string|max:10',
            'etp_support' => 'nullable|string',
            'etp_changes' => 'nullable|string',
            'archi_ho' => 'nullable|string|max:10',
            'environment_id' => 'nullable|exists:environments,id',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $service = $this->dashboardService->upsertService(
            $type->id,
            $validator->validated(),
            $request->user()?->id,
        );

        return $this->responseOk([
            'service' => $service,
            'dashboard' => $this->dashboardService->dashboard(),
        ]);
    }
}
