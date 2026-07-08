<?php

namespace App\Services;

use App\Models\Recommendation;
use App\Models\RecommendationActionPlan;
use Carbon\Carbon;

class ActionPlanStatusService
{
    public function syncStatus(RecommendationActionPlan $plan): RecommendationActionPlan
    {
        $status = $this->resolveStatus($plan);

        if ($plan->status !== $status) {
            $plan->status = $status;
            $plan->save();
        }

        return $plan->refresh();
    }

    public function resolveStatus(RecommendationActionPlan $plan): string
    {
        if ($plan->status === 'annule') {
            return 'annule';
        }

        if ($plan->status === 'cloture') {
            return 'cloture';
        }

        if ($plan->is_waiting) {
            return 'en_attente';
        }

        if (! trim((string) $plan->action_plan)) {
            return 'non_demarre';
        }

        if ($plan->due_date && $plan->due_date->lt(Carbon::today())) {
            return 'en_retard';
        }

        return 'en_cours';
    }

    public function delayDays(RecommendationActionPlan $plan): ?int
    {
        $status = $this->resolveStatus($plan);

        if ($status === 'non_demarre' || ! $plan->due_date) {
            return null;
        }

        if ($status !== 'en_retard') {
            return 0;
        }

        return (int) $plan->due_date->diffInDays(Carbon::today());
    }

    public function statusColor(string $status): string
    {
        return collect(config('mission-parametrage.action_plan_statuses', []))
            ->firstWhere('value', $status)['color'] ?? 'slate';
    }

    public function markTransmittedForOwner(int $missionId, int $userId, array $entityIds): void
    {
        if ($entityIds === []) {
            return;
        }

        RecommendationActionPlan::query()
            ->whereHas('recommendation', function ($query) use ($missionId, $entityIds) {
                $query->where('mission_id', $missionId)
                    ->whereHas('entities', fn ($entityQuery) => $entityQuery->whereIn('entities.id', $entityIds));
            })
            ->where('user_id', $userId)
            ->whereNull('transmission_date')
            ->update([
                'transmission_date' => Carbon::today()->toDateString(),
                'status' => 'cloture',
            ]);
    }

    public function closeForRecommendation(Recommendation $recommendation): void
    {
        $recommendation->actionPlans()
            ->where('status', '!=', 'annule')
            ->update(['status' => 'cloture']);
    }
}
