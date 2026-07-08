<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Maravel\Http\Controllers\APIController;

/**
 * @group Types de mission
 */
class MissionTypeController extends APIController
{
    public function index()
    {
        $user = request()->user();
        $types = collect(config('mission-parametrage.mission_types', []))
            ->filter(fn (array $type) => $this->typeAllowedForUser($type, $user))
            ->map(fn (array $type) => [
                'value' => $type['value'],
                'label' => $type['label'],
            ])
            ->values();

        return $this->responseOk($types);
    }

    private function typeAllowedForUser(array $type, ?User $user): bool
    {
        if (! $user || $user->isSuperAdmin()) {
            return true;
        }

        $profiles = $type['profiles'] ?? [];

        return in_array($user->profile, $profiles, true);
    }
}
