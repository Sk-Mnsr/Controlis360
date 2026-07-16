<?php

namespace App\Services;

use App\Models\Mission;
use App\Models\MissionResponse;
use App\Models\Recommendation;
use App\Models\RecommendationActionPlan;
use App\Models\RegulatoryReportingFiche;
use App\Models\User;

class AttachmentAccessService
{
    public function canDownload(User $user, string $path): bool
    {
        if ($path === '' || str_contains($path, '..')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (preg_match('#^regulatory-reporting/(\d+)/#', $path, $matches)) {
            $fiche = RegulatoryReportingFiche::query()->find((int) $matches[1]);

            if (! $fiche) {
                return false;
            }

            if (in_array($user->profile, ['conformite'], true)) {
                if ($fiche->environment_id === null) {
                    return true;
                }

                return $user->belongsToEnvironment((int) $fiche->environment_id);
            }

            if ($user->profile === 'metier' && $user->metier_role === 'responsable_entite') {
                return $fiche->etabli_par_entity_id
                    && $user->belongsToEntity((int) $fiche->etabli_par_entity_id);
            }

            return false;
        }

        if (preg_match('#^action-plans/(\d+)/#', $path, $matches)) {
            $plan = RecommendationActionPlan::query()
                ->with(['recommendation'])
                ->find((int) $matches[1]);

            return $plan && $this->canViewRecommendation($user, $plan->recommendation);
        }

        if (preg_match('#^mission-responses/(\d+)(?:/passivity)?/#', $path, $matches)) {
            return $this->canViewMission($user, (int) $matches[1]);
        }

        if (preg_match('#^mission-recommendations/(\d+)/#', $path, $matches)) {
            return $this->canViewMission($user, (int) $matches[1]);
        }

        return false;
    }

    private function canViewMission(User $user, int $missionId): bool
    {
        $query = Mission::query()->where('id', $missionId);
        $this->applyMissionVisibilityFilter($query, $user);

        return $query->exists();
    }

    private function canViewRecommendation(User $user, ?Recommendation $recommendation): bool
    {
        if (! $recommendation) {
            return false;
        }

        if (! $this->canViewRecommendations($user)) {
            return false;
        }

        return $this->canViewMission($user, (int) $recommendation->mission_id);
    }

    private function canViewRecommendations(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (in_array($user->profile, ['controle', 'audit'], true)) {
            return true;
        }

        if ($user->profile === 'metier' && $user->metier_role === 'responsable_entite') {
            return true;
        }

        if ($user->profile === 'metier' && $user->metier_role === 'agent') {
            return MissionResponse::query()
                ->where('assigned_agent_id', $user->id)
                ->exists();
        }

        return false;
    }

    private function applyMissionVisibilityFilter($query, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        if (in_array($user->profile, ['controle', 'audit'], true)) {
            $environmentIds = $user->environment_ids;

            if (! empty($environmentIds)) {
                $query->whereHas('entities', function ($entityQuery) use ($environmentIds) {
                    $entityQuery->whereIn('environment_id', $environmentIds);
                });
            }

            return;
        }

        if ($user->profile === 'metier' && $user->metier_role === 'responsable_entite') {
            $query->whereHas('recipients', function ($recipientQuery) use ($user) {
                $recipientQuery->where('users.id', $user->id);
            });

            return;
        }

        if ($user->profile === 'metier' && $user->metier_role === 'agent') {
            $query->whereHas('responses', function ($responseQuery) use ($user) {
                $responseQuery->where('assigned_agent_id', $user->id);
            });
        }
    }
}
