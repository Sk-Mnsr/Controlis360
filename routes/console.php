<?php

use App\Models\Mission;
use App\Models\MissionResponse;
use App\Models\Recommendation;
use App\Services\RecommendationStatusService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('recommendations:repair-treated {--mission= : ID de la mission (toutes par défaut)} {--apply : Appliquer la correction (sinon simulation)}', function (RecommendationStatusService $statusService) {
    $query = Recommendation::query()
        ->with(['mission', 'entities'])
        ->where('status', 'traitee')
        ->whereDoesntHave('actionPlans', fn ($plans) => $plans->where('status', '!=', 'annule'));

    if ($this->option('mission')) {
        $query->where('mission_id', (int) $this->option('mission'));
    }

    $passivityEntityIds = MissionResponse::query()
        ->with('responsable')
        ->where('response_type', 'passivite')
        ->get()
        ->groupBy('mission_id')
        ->map(fn ($responses) => $responses
            ->flatMap(fn (MissionResponse $response) => $response->responsable?->entity_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all());

    $targets = $query->get()->reject(function (Recommendation $reco) use ($passivityEntityIds) {
        $covered = $passivityEntityIds->get($reco->mission_id, []);

        return $reco->entities->pluck('id')->map(fn ($id) => (int) $id)->intersect($covered)->isNotEmpty();
    });

    if ($targets->isEmpty()) {
        $this->info('Aucune recommandation à corriger.');

        return;
    }

    $this->table(
        ['ID', 'Référence', 'Mission'],
        $targets->map(fn (Recommendation $reco) => [
            $reco->id,
            $reco->reference ?? '—',
            $reco->mission?->reference ?? $reco->mission_id,
        ])->all(),
    );

    if (! $this->option('apply')) {
        $this->warn($targets->count().' recommandation(s) seraient remises « En cours ». Relancer avec --apply pour corriger.');

        return;
    }

    Recommendation::query()
        ->whereIn('id', $targets->pluck('id'))
        ->update(['status' => 'en_cours']);

    Mission::query()
        ->whereIn('id', $targets->pluck('mission_id')->unique())
        ->get()
        ->each(fn (Mission $mission) => $statusService->syncMissionStatus($mission));

    $this->info($targets->count().' recommandation(s) remise(s) « En cours ».');
})->purpose('Remet « En cours » les recommandations « Traitée » sans plan d\'action (transmission globale erronée)');
