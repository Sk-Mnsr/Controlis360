<?php

namespace App\Services;

use App\Models\Mission;
use App\Models\MissionResponse;
use App\Models\Recommendation;
use App\Models\User;

class RecommendationStatusService
{
    public function markCreated(Recommendation $recommendation): void
    {
        if (! $recommendation->status) {
            $recommendation->update(['status' => 'emise']);
        }

        $this->syncMissionStatus($recommendation->mission);
    }

    public function markMissionInProgress(Mission $mission): void
    {
        $mission->recommendations()
            ->where('status', 'emise')
            ->update(['status' => 'en_cours']);

        $this->syncMissionStatus($mission);
    }

    public function markOwnerRecommendationsInProgress(Mission $mission, User $owner): int
    {
        $entityIds = $owner->entity_ids;

        if ($entityIds === []) {
            return 0;
        }

        $updated = Recommendation::query()
            ->where('mission_id', $mission->id)
            ->where('status', 'emise')
            ->whereHas('entities', fn ($query) => $query->whereIn('entities.id', $entityIds))
            ->update(['status' => 'en_cours']);

        if ($updated > 0) {
            $this->syncMissionStatus($mission->fresh('recommendations'));
        }

        return $updated;
    }

    public function markOwnerRecommendationsTreatedFromResponse(Mission $mission, MissionResponse $response): void
    {
        $owner = $response->relationLoaded('responsable')
            ? $response->responsable
            : $response->responsable()->first();

        $entityIds = $owner?->entity_ids ?? [];

        if ($entityIds === []) {
            $this->markMissionTreatedFromResponse($mission, $response);

            return;
        }

        $mission->recommendations()
            ->whereIn('status', ['emise', 'en_cours'])
            ->whereHas('entities', fn ($query) => $query->whereIn('entities.id', $entityIds))
            ->update(['status' => 'traitee']);

        $this->syncMissionStatus($mission->fresh('recommendations'));
    }

    public function markMissionTreatedFromResponse(Mission $mission, MissionResponse $response): void
    {
        $status = $this->treatmentStatusFromResponse($response);

        $mission->recommendations()
            ->whereIn('status', ['emise', 'en_cours', 'traitee'])
            ->update(['status' => $status]);

        $this->syncMissionStatus($mission);
    }

    public function markMissionClosed(Mission $mission): void
    {
        $mission->recommendations()
            ->whereIn('status', ['traitee', 'transmis'])
            ->update(['status' => 'cloturee']);

        $this->syncMissionStatus($mission);
    }

    public function markRecommendationClosed(Recommendation $recommendation): void
    {
        if ($recommendation->status === 'cloturee') {
            return;
        }

        $recommendation->update(['status' => 'cloturee']);
        $this->syncMissionStatus($recommendation->mission->fresh('recommendations'));
    }

    public function syncMissionStatus(Mission $mission): void
    {
        $mission->load('recommendations');
        $statuses = $mission->recommendations->pluck('status');

        if ($statuses->isEmpty()) {
            return;
        }

        if ($statuses->every(fn (string $status) => $status === 'cloturee')) {
            $mission->update(['status' => 'ferme']);

            return;
        }

        $mission->update(['status' => 'ouvert']);
    }

    private function treatmentStatusFromResponse(MissionResponse $response): string
    {
        if ($response->response_type === 'passivite') {
            return 'traitee';
        }

        $rate = $response->progress_rate;

        if ($rate !== null && (int) $rate >= 100) {
            return 'traitee';
        }

        return 'en_cours';
    }
}
