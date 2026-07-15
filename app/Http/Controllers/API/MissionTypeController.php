<?php

namespace App\Http\Controllers\API;

use App\Models\MissionType;
use App\Models\User;
use App\Services\MissionTypeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maravel\Http\Controllers\APIController;

/**
 * @group Types de mission
 */
class MissionTypeController extends APIController
{
    public function __construct(private MissionTypeService $missionTypeService) {}

    public function index(Request $request)
    {
        $types = $this->missionTypeService
            ->allowedForUser($request->user())
            ->map(fn (MissionType $type) => $this->missionTypeService->formatForSelect($type));

        return $this->responseOk($types);
    }

    public function manage(Request $request)
    {
        if (! $this->canManageMissionTypes($request->user())) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $types = $this->missionTypeService
            ->allForManagement()
            ->map(fn (MissionType $type) => $this->missionTypeService->formatForSelect($type));

        return $this->responseOk($types);
    }

    public function store(Request $request)
    {
        if (! $this->canManageMissionTypes($request->user())) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $validator = Validator::make($request->all(), $this->validationRules());

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        $data = $validator->validated();
        $code = trim((string) ($data['code'] ?? '')) ?: $this->missionTypeService->generateUniqueCode($data['name']);

        if ($this->missionTypeService->codeExists($code)) {
            return $this->responseError(['code' => ['Ce code existe déjà.']], 422);
        }

        $type = MissionType::query()->create([
            'code' => $code,
            'name' => trim($data['name']),
            'profiles' => array_values(array_unique($data['profiles'] ?? ['audit', 'controle'])),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'accent_color' => trim((string) ($data['accent_color'] ?? '')) ?: '#047857',
            'sort_order' => (int) ($data['sort_order'] ?? ((int) MissionType::query()->max('sort_order') + 1)),
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
        ]);

        return $this->responseOk($this->missionTypeService->formatForSelect($type), 201);
    }

    public function update(Request $request, int $id)
    {
        if (! $this->canManageMissionTypes($request->user())) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $type = MissionType::query()->findOrFail($id);

        $validator = Validator::make($request->all(), $this->validationRules($type->id, false));

        if ($validator->fails()) {
            return $this->responseError($validator->errors(), 422);
        }

        $data = $validator->validated();

        $type->update([
            'name' => trim($data['name']),
            'profiles' => array_values(array_unique($data['profiles'] ?? $type->profiles ?? [])),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'accent_color' => trim((string) ($data['accent_color'] ?? '')) ?: $type->accent_color,
            'sort_order' => (int) ($data['sort_order'] ?? $type->sort_order),
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $type->is_active,
        ]);

        return $this->responseOk($this->missionTypeService->formatForSelect($type->fresh()));
    }

    public function destroy(Request $request, int $id)
    {
        if (! $this->canManageMissionTypes($request->user())) {
            return $this->responseError(['message' => ['Accès non autorisé.']], 403);
        }

        $type = MissionType::query()->withCount('missions')->findOrFail($id);

        if (! $this->missionTypeService->canDelete($type)) {
            return $this->responseError([
                'message' => ['Impossible de supprimer un type utilisé par des missions. Désactivez-le plutôt.'],
            ], 422);
        }

        $type->delete();

        return $this->responseOk(['message' => ['Type de mission supprimé.']]);
    }

    private function validationRules(?int $ignoreId = null, bool $creating = true): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'profiles' => 'nullable|array|min:1',
            'profiles.*' => 'in:audit,controle',
            'description' => 'nullable|string|max:2000',
            'accent_color' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];

        if ($creating) {
            $rules['code'] = 'nullable|string|max:100|regex:/^[a-z0-9_]+$/';
        }

        return $rules;
    }

    private function canManageMissionTypes(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->isSuperAdmin()
            || in_array($user->profile, ['controle', 'audit'], true);
    }
}
