<?php

namespace App\Http\Controllers\API;

use App\Models\Entity;
use App\Models\Environment;
use App\Models\Country;
use App\Models\Department;
use App\Models\RiskCategory;
use App\Models\RiskClassification;
use App\Models\RiskFamily;
use App\Models\ScaleLevel;
use App\Models\Subsidiary;
use App\Models\TopRisk;
use App\Models\OperationalRiskLog;
use App\Models\OperationalRiskRow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maravel\Http\Controllers\APIController;

/**
 * @group Référentiels
 *
 * Données de référence pour la cartographie des risques.
 */
class ReferentialController extends APIController
{
    /**
     * Liste des pays actifs.
     */
    public function countries()
    {
        return $this->responseOk(
            Country::query()->where('is_active', true)->orderBy('name')->get()
        );
    }

    /**
     * Liste des filiales (optionnellement filtrées par pays).
     */
    public function subsidiaries(Request $request)
    {
        $query = Subsidiary::query()
            ->with('country')
            ->where('is_active', true)
            ->orderBy('name');

        if ($request->filled('country_id')) {
            $query->where('country_id', $request->integer('country_id'));
        }

        return $this->responseOk($query->get());
    }

    /**
     * Liste des départements (optionnellement filtrés par filiale).
     */
    public function departments(Request $request)
    {
        $query = Department::query()
            ->with('subsidiary')
            ->where('is_active', true)
            ->orderBy('sort_order');

        if ($request->filled('subsidiary_id')) {
            $query->where('subsidiary_id', $request->integer('subsidiary_id'));
        }

        return $this->responseOk($query->get());
    }

    /**
     * Échelles P & G (gravité et probabilité).
     */
    public function echellePg()
    {
        $levels = ScaleLevel::query()
            ->whereIn('type', ['gravity', 'probability'])
            ->orderBy('type')
            ->orderBy('level')
            ->get();

        return $this->responseOk([
            'gravity' => $levels->where('type', 'gravity')->values(),
            'probability' => $levels->where('type', 'probability')->values(),
        ]);
    }

    /**
     * Mise à jour des échelles P & G (super administrateur).
     */
    public function updateEchellePg(Request $request)
    {
        if (! $request->user()->canEditMethodology()) {
            return $this->responseError(['auth' => ['Action réservée au super administrateur ou au responsable contrôle']], 403);
        }

        $validator = Validator::make($request->all(), [
            'gravity' => 'required|array|min:1',
            'gravity.*.id' => 'nullable|exists:scale_levels,id',
            'gravity.*.level' => 'required|integer|min:1|max:99',
            'gravity.*.qualification' => 'required|string|max:255',
            'gravity.*.description' => 'required|string',
            'probability' => 'required|array|min:1',
            'probability.*.id' => 'nullable|exists:scale_levels,id',
            'probability.*.level' => 'required|integer|min:1|max:99',
            'probability.*.qualification' => 'required|string|max:255',
            'probability.*.description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        foreach (['gravity', 'probability'] as $type) {
            $duplicate = collect($request->input($type))->pluck('level')->duplicates();
            if ($duplicate->isNotEmpty()) {
                return $this->responseError([
                    $type => ['Chaque niveau doit être unique dans la table.'],
                ], 422);
            }
        }

        $this->syncEchelleType('gravity', $request->input('gravity', []));
        $this->syncEchelleType('probability', $request->input('probability', []));

        return $this->echellePg();
    }

    /**
     * Échelle de contrôle (maturité des dispositifs).
     */
    public function echelleControle()
    {
        $levels = ScaleLevel::query()
            ->where('type', 'control')
            ->orderBy('level')
            ->get();

        return $this->responseOk([
            'control' => $levels->values(),
        ]);
    }

    /**
     * Mise à jour de l'échelle de contrôle (super administrateur).
     */
    public function updateEchelleControle(Request $request)
    {
        if (! $request->user()->canEditMethodology()) {
            return $this->responseError(['auth' => ['Action réservée au super administrateur ou au responsable contrôle']], 403);
        }

        $validator = Validator::make($request->all(), [
            'control' => 'required|array|min:1',
            'control.*.id' => 'nullable|exists:scale_levels,id',
            'control.*.level' => 'required|integer|min:1|max:99',
            'control.*.qualification' => 'required|string|max:255',
            'control.*.description' => 'required|string',
            'control.*.maturity_label' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $duplicate = collect($request->input('control'))->pluck('level')->duplicates();
        if ($duplicate->isNotEmpty()) {
            return $this->responseError([
                'control' => ['Chaque niveau doit être unique dans la table.'],
            ], 422);
        }

        $this->syncEchelleControl($request->input('control', []));

        return $this->echelleControle();
    }

    private function syncEchelleControl(array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $row) {
            $payload = [
                'type' => 'control',
                'level' => $row['level'],
                'qualification' => $row['qualification'],
                'label' => $row['qualification'],
                'description' => $row['description'],
                'maturity_label' => $row['maturity_label'],
            ];

            if (! empty($row['id'])) {
                $level = ScaleLevel::query()
                    ->where('id', $row['id'])
                    ->where('type', 'control')
                    ->first();

                if ($level) {
                    $level->update($payload);
                    $keptIds[] = $level->id;
                    continue;
                }
            }

            $level = ScaleLevel::query()->updateOrCreate(
                ['type' => 'control', 'level' => $row['level']],
                $payload
            );
            $keptIds[] = $level->id;
        }

        ScaleLevel::query()
            ->where('type', 'control')
            ->whereNotIn('id', $keptIds)
            ->delete();
    }

    private function syncEchelleType(string $type, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $row) {
            $payload = [
                'type' => $type,
                'level' => $row['level'],
                'qualification' => $row['qualification'],
                'label' => $row['qualification'],
                'description' => $row['description'],
            ];

            if (! empty($row['id'])) {
                $level = ScaleLevel::query()
                    ->where('id', $row['id'])
                    ->where('type', $type)
                    ->first();

                if ($level) {
                    $level->update($payload);
                    $keptIds[] = $level->id;
                    continue;
                }
            }

            $level = ScaleLevel::query()->updateOrCreate(
                ['type' => $type, 'level' => $row['level']],
                $payload
            );
            $keptIds[] = $level->id;
        }

        ScaleLevel::query()
            ->where('type', $type)
            ->whereNotIn('id', $keptIds)
            ->delete();
    }

    /**
     * Échelles P, G et contrôle.
     */
    public function scales()
    {
        $levels = ScaleLevel::query()->orderBy('type')->orderBy('level')->get();

        return $this->responseOk([
            'gravity' => $levels->where('type', 'gravity')->values(),
            'probability' => $levels->where('type', 'probability')->values(),
            'control' => $levels->where('type', 'control')->values(),
        ]);
    }

    /**
     * Classifications de criticité et matrice G×P.
     */
    public function matrix()
    {
        return $this->responseOk($this->buildMatricePayload());
    }

    /**
     * Matrice des risques et lexique des niveaux de criticité.
     */
    public function matriceRisques()
    {
        return $this->responseOk($this->buildMatricePayload());
    }

    /**
     * Mise à jour du lexique de la matrice (super administrateur).
     */
    public function updateMatriceRisques(Request $request)
    {
        if (! $request->user()->canEditMethodology()) {
            return $this->responseError(['auth' => ['Action réservée au super administrateur ou au responsable contrôle']], 403);
        }

        $validator = Validator::make($request->all(), [
            'classifications' => 'required|array|min:1',
            'classifications.*.id' => 'required|exists:risk_classifications,id',
            'classifications.*.name' => 'required|string|max:255',
            'classifications.*.description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        foreach ($request->input('classifications', []) as $row) {
            RiskClassification::query()
                ->where('id', $row['id'])
                ->update([
                    'name' => $row['name'],
                    'description' => $row['description'],
                ]);
        }

        return $this->matriceRisques();
    }

    private function buildMatricePayload(): array
    {
        $classifications = RiskClassification::query()->orderBy('sort_order')->get();

        $matrix = [];
        for ($probability = 6; $probability >= 1; $probability--) {
            $row = [];
            for ($gravity = 1; $gravity <= 6; $gravity++) {
                $score = $gravity * $probability;
                $classification = RiskClassification::forCell($gravity, $probability);

                $row[] = [
                    'gravity' => $gravity,
                    'probability' => $probability,
                    'score' => $score,
                    'classification' => $classification,
                ];
            }
            $matrix[] = $row;
        }

        return [
            'classifications' => $classifications,
            'matrix' => $matrix,
        ];
    }

    /**
     * Familles de risques avec catégories.
     */
    public function riskFamilies()
    {
        return $this->responseOk($this->buildLexiquePayload());
    }

    /**
     * Lexique des familles de risques.
     */
    public function lexiqueFamilles()
    {
        return $this->responseOk($this->buildLexiquePayload());
    }

    /**
     * Mise à jour du lexique des familles de risques (super administrateur).
     */
    public function updateLexiqueFamilles(Request $request)
    {
        if (! $request->user()->canEditMethodology()) {
            return $this->responseError(['auth' => ['Action réservée au super administrateur ou au responsable contrôle']], 403);
        }

        $validator = Validator::make($request->all(), [
            'categories' => 'required|array|min:1',
            'categories.*.id' => 'nullable|exists:risk_categories,id',
            'categories.*.number' => 'required|integer|min:1|max:99',
            'categories.*.name' => 'required|string|max:255',
            'categories.*.description' => 'nullable|string',
            'categories.*.families' => 'required|array|min:1',
            'categories.*.families.*.id' => 'nullable|exists:risk_families,id',
            'categories.*.families.*.name' => 'required|string|max:255',
            'categories.*.families.*.definition' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $duplicate = collect($request->input('categories'))->pluck('number')->duplicates();
        if ($duplicate->isNotEmpty()) {
            return $this->responseError([
                'categories' => ['Chaque numéro de famille doit être unique.'],
            ], 422);
        }

        $this->syncLexiqueCategories($request->input('categories', []));

        return $this->lexiqueFamilles();
    }

    private function buildLexiquePayload(): array
    {
        return [
            'categories' => RiskCategory::query()
                ->with(['families' => fn ($q) => $q->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get(),
        ];
    }

    private function syncLexiqueCategories(array $categories): void
    {
        $keptCategoryIds = [];

        foreach ($categories as $index => $categoryRow) {
            $payload = [
                'number' => $categoryRow['number'],
                'name' => $categoryRow['name'],
                'description' => $categoryRow['description'] ?? null,
                'sort_order' => $index + 1,
            ];

            if (! empty($categoryRow['id'])) {
                $category = RiskCategory::query()->find($categoryRow['id']);
                if ($category) {
                    $category->update($payload);
                    $keptCategoryIds[] = $category->id;
                    $this->syncLexiqueFamilies($category, $categoryRow['families'] ?? []);
                    continue;
                }
            }

            $category = RiskCategory::query()->updateOrCreate(
                ['number' => $categoryRow['number']],
                $payload
            );
            $keptCategoryIds[] = $category->id;
            $this->syncLexiqueFamilies($category, $categoryRow['families'] ?? []);
        }

        $orphanCategories = RiskCategory::query()
            ->whereNotIn('id', $keptCategoryIds)
            ->get();

        foreach ($orphanCategories as $category) {
            $category->families()->delete();
            $category->delete();
        }
    }

    private function syncLexiqueFamilies(RiskCategory $category, array $families): void
    {
        $keptFamilyIds = [];

        foreach ($families as $index => $familyRow) {
            $payload = [
                'risk_category_id' => $category->id,
                'name' => $familyRow['name'],
                'definition' => $familyRow['definition'] ?? null,
                'sort_order' => $index + 1,
            ];

            if (! empty($familyRow['id'])) {
                $family = RiskFamily::query()
                    ->where('id', $familyRow['id'])
                    ->where('risk_category_id', $category->id)
                    ->first();

                if ($family) {
                    $family->update($payload);
                    $keptFamilyIds[] = $family->id;
                    continue;
                }
            }

            $family = RiskFamily::query()->updateOrCreate(
                ['risk_category_id' => $category->id, 'name' => $familyRow['name']],
                $payload
            );
            $keptFamilyIds[] = $family->id;
        }

        RiskFamily::query()
            ->where('risk_category_id', $category->id)
            ->whereNotIn('id', $keptFamilyIds)
            ->delete();
    }

    /**
     * Plus gros risques opérationnels à fort impact business.
     */
    public function topRisques(Request $request)
    {
        return $this->responseOk($this->buildTopRisquesPayload($request));
    }

    /**
     * Mise à jour des plus gros risques (super administrateur).
     */
    public function updateTopRisques(Request $request)
    {
        if (! $request->user()->canEditMethodology()) {
            return $this->responseError(['auth' => ['Action réservée au super administrateur ou au responsable contrôle']], 403);
        }

        $validator = Validator::make($request->all(), [
            'rows' => 'required|array|min:1',
            'rows.*.id' => 'nullable|exists:top_risks,id',
            'rows.*.process_name' => 'nullable|string|max:255',
            'rows.*.sub_process_name' => 'required|string|max:255',
            'rows.*.line_date' => 'nullable|date',
            'rows.*.major_exceptions' => 'nullable|string',
            'rows.*.risk_family' => 'nullable|string|max:255',
            'rows.*.gravity' => 'nullable|integer|min:1|max:6',
            'rows.*.probability' => 'nullable|integer|min:1|max:6',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $this->syncTopRisques($request->input('rows', []));

        return $this->topRisques($request);
    }

    private function buildTopRisquesPayload(Request $request): array
    {
        $user = $request->user();
        $environmentCode = $request->query('environment');
        $formatter = app(OperationalRiskRowController::class);

        $entitiesQuery = Entity::query()
            ->whereIn('type', ['department', 'agency'])
            ->where('is_active', true)
            ->visibleToUser($user);

        if ($user->environment_id) {
            $entitiesQuery->where('environment_id', $user->environment_id);
        } elseif ($environmentCode) {
            $entitiesQuery->whereHas(
                'environment',
                fn ($query) => $query->where('code', $environmentCode)
            );
        } elseif ($user->isSuperAdmin()) {
            $environmentId = Environment::query()->orderBy('id')->value('id');

            if ($environmentId) {
                $entitiesQuery->where('environment_id', $environmentId);
            }
        }

        $entityIds = $entitiesQuery->pluck('id');

        $rowsQuery = OperationalRiskRow::query()
            ->with(['entity'])
            ->visibleInAnalyse()
            ->whereIn('entity_id', $entityIds)
            ->whereNotNull('gravity')
            ->whereNotNull('probability')
            ->whereRaw('gravity * probability >= 10');

        if ($user->isEntityResponsable()) {
            $rowsQuery->visibleToEntityResponsable($user);
        }

        $rows = $rowsQuery
            ->orderByDesc(\Illuminate\Support\Facades\DB::raw('gravity * probability'))
            ->orderBy('process_name')
            ->orderBy('sub_process_name')
            ->get()
            ->map(function (OperationalRiskRow $row) use ($formatter) {
                $formatted = $formatter->formatRow($row);
                $formatted['process_name'] = $row->process_name ?: $row->entity?->name;
                $formatted['classification'] = $formatted['gross_classification'];

                return $formatted;
            });

        return [
            'title' => 'RISQUES OPERATIONNELS A FORT IMPACT BUSINESS',
            'rows' => $rows,
            'is_dynamic' => true,
        ];
    }

    private function syncTopRisques(array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $index => $row) {
            $payload = [
                'process_name' => $row['process_name'] ?? null,
                'sub_process_name' => $row['sub_process_name'],
                'major_exceptions' => $row['major_exceptions'] ?? null,
                'risk_family' => $row['risk_family'] ?? null,
                'gravity' => $row['gravity'] ?? null,
                'probability' => $row['probability'] ?? null,
                'sort_order' => $index + 1,
            ];

            if (! empty($row['id'])) {
                $risk = TopRisk::query()->find($row['id']);
                if ($risk) {
                    $risk->update($payload);
                    $keptIds[] = $risk->id;
                    continue;
                }
            }

            $risk = TopRisk::query()->create($payload);
            $keptIds[] = $risk->id;
        }

        TopRisk::query()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * Environnements disponibles pour la création de mission (audit / contrôle).
     */
    public function missionEnvironments(Request $request)
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && ! in_array($user->profile, ['controle', 'audit'], true)) {
            return $this->responseError(['auth' => ['Accès non autorisé.']], 403);
        }

        $query = Environment::query()
            ->where('is_active', true)
            ->orderBy('name');

        return $this->responseOk(
            $query->get(['id', 'name', 'code'])
        );
    }

    /**
     * Entités (départements et agences) pour la navigation cartographie.
     */
    public function entitiesDepartments(Request $request)
    {
        $user = $request->user();

        $entities = Entity::query()
            ->with('environment')
            ->whereIn('type', ['department', 'agency'])
            ->where('is_active', true)
            ->visibleToUser($user)
            ->orderBy('environment_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->unique('id')
            ->values();

        return $this->responseOk($entities);
    }

    /**
     * Dashboard cartographie global (risques bruts et résiduels par entité).
     */
    public function cartographieDashboard(Request $request)
    {
        return $this->responseOk($this->buildCartographieDashboardPayload($request));
    }

    /**
     * Données pour la saisie centralisée des risques opérationnels.
     */
    public function saisieRisquesContext(Request $request)
    {
        $user = $request->user();

        if (! $user->canCreateOperationalRiskRow()) {
            return $this->responseError(['auth' => ['Action réservée au personnel du contrôle interne']], 403);
        }

        $entitiesQuery = Entity::query()
            ->with('environment:id,code,name')
            ->whereIn('type', ['department', 'agency'])
            ->where('is_active', true)
            ->orderBy('environment_id')
            ->orderBy('sort_order')
            ->orderBy('name');

        $user = $request->user();

        if ($user->environment_id) {
            $entitiesQuery->where('environment_id', $user->environment_id);
        } elseif ($user->isSuperAdmin()) {
            $environmentId = Environment::query()->orderBy('id')->value('id');
            if ($environmentId) {
                $entitiesQuery->where('environment_id', $environmentId);
            }
        }

        return $this->responseOk($entitiesQuery->get());
    }

    /**
     * Analyse des risques opérationnels d'un département (entité).
     */
    public function analyseRisques(Request $request, string $code)
    {
        $entity = $this->resolveDepartmentEntity($request, $code);

        if (! $entity) {
            return $this->responseError(['code' => ['Département introuvable']], 404);
        }

        return $this->responseOk($this->buildAnalyseRisquesPayload(
            $entity,
            $request->boolean('include_drafts')
                && $request->user()->canCreateOperationalRiskRow()
        ));
    }

    public function analyseRisquesHistorique(Request $request, string $code)
    {
        $entity = $this->resolveDepartmentEntity($request, $code);

        if (! $entity) {
            return $this->responseError(['code' => ['Département introuvable']], 404);
        }

        $logs = OperationalRiskLog::query()
            ->with(['user:id,name,email', 'row:id,sub_process_name,major_exceptions'])
            ->where('entity_id', $entity->id)
            ->orderByDesc('created_at')
            ->limit(200)
            ->get()
            ->map(fn (OperationalRiskLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'action_label' => $log->actionLabel(),
                'message' => $log->message,
                'metadata' => $log->metadata,
                'created_at' => $log->created_at?->format('Y-m-d H:i'),
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                ] : null,
                'row' => $log->row ? [
                    'id' => $log->row->id,
                    'sub_process_name' => $log->row->sub_process_name,
                    'major_exceptions' => $log->row->major_exceptions,
                ] : null,
            ]);

        return $this->responseOk([
            'entity' => $entity,
            'title' => 'HISTORIQUE — '.$entity->name,
            'logs' => $logs,
        ]);
    }

    /**
     * Mise à jour de l'analyse des risques d'un département (super administrateur).
     */
    public function updateAnalyseRisques(Request $request, string $code)
    {
        if (! $request->user()->isSuperAdmin()) {
            return $this->responseError(['auth' => ['Action réservée au super administrateur']], 403);
        }

        $entity = $this->resolveDepartmentEntity($request, $code);

        if (! $entity) {
            return $this->responseError(['code' => ['Département introuvable']], 404);
        }

        $validator = Validator::make($request->all(), [
            'rows' => 'required|array|min:1',
            'rows.*.id' => 'nullable|exists:operational_risk_rows,id',
            'rows.*.process_number' => 'nullable|integer|min:1|max:99',
            'rows.*.process_name' => 'nullable|string|max:255',
            'rows.*.ratio' => 'nullable|numeric|min:0|max:100',
            'rows.*.sub_process_name' => 'required|string|max:255',
            'rows.*.line_date' => 'nullable|date',
            'rows.*.major_exceptions' => 'nullable|string',
            'rows.*.correlated_risks' => 'nullable|string',
            'rows.*.risk_family' => 'nullable|string|max:255',
            'rows.*.gravity' => 'nullable|integer|min:1|max:6',
            'rows.*.probability' => 'nullable|integer|min:1|max:6',
            'rows.*.control_description' => 'nullable|string',
            'rows.*.control_exists' => 'nullable|boolean',
            'rows.*.control_owner' => 'nullable|string|max:255',
            'rows.*.control_effectiveness' => 'nullable|integer|min:1|max:5',
            'rows.*.residual_gravity' => 'nullable|integer|min:1|max:6',
            'rows.*.residual_probability' => 'nullable|numeric|min:1|max:6',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $this->syncOperationalRiskRows($entity, $request->input('rows', []));

        return $this->responseOk($this->buildAnalyseRisquesPayload($entity->fresh()));
    }

    private function resolveDepartmentEntity(Request $request, string $code): ?Entity
    {
        return Entity::resolveDepartmentForUser(
            $request->user(),
            $code,
            $request->query('environment'),
            $request->integer('entity_id') ?: null
        );
    }

    private function buildAnalyseRisquesPayload(Entity $entity, bool $includeDrafts = false): array
    {
        $user = request()->user();
        $formatter = app(OperationalRiskRowController::class);

        $rowsQuery = OperationalRiskRow::query()
            ->with(['assignedEntity', 'entity'])
            ->where('entity_id', $entity->id)
            ->orderBy('sort_order');

        if (! $includeDrafts) {
            $rowsQuery->visibleInAnalyse();
        }

        if ($user->isEntityResponsable()) {
            $rowsQuery->visibleToEntityResponsable($user);
        }

        $rows = $rowsQuery
            ->get()
            ->map(fn (OperationalRiskRow $row) => $formatter->formatRow($row));

        $assignableEntities = Entity::query()
            ->where('environment_id', $entity->environment_id)
            ->whereIn('type', ['department', 'agency'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return [
            'entity' => $entity,
            'title' => 'ANALYSE DES RISQUES OPERATIONNELS — '.$entity->name,
            'rows' => $rows,
            'assignable_entities' => $assignableEntities,
            'risk_families' => RiskFamily::query()
                ->orderBy('sort_order')
                ->pluck('name')
                ->values(),
            'risk_categories' => RiskCategory::query()
                ->with(['families' => fn ($query) => $query->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get(),
            'risk_classifications' => RiskClassification::query()
                ->orderBy('sort_order')
                ->get(),
            'permissions' => [
                'can_create_row' => $user->canCreateOperationalRiskRow(),
                'can_edit_methodology' => $user->canEditMethodology(),
                'can_validate' => $user->isSuperAdmin() || $user->isControleResponsable(),
                'can_complete_entity' => $user->isSuperAdmin() || $user->isControleResponsable(),
                'is_entity_responsable' => $user->isEntityResponsable(),
                'is_super_admin' => $user->isSuperAdmin(),
                'entity_id' => $user->entity_id,
            ],
        ];
    }

    private function syncOperationalRiskRows(Entity $entity, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $index => $row) {
            $payload = [
                'entity_id' => $entity->id,
                'process_number' => $row['process_number'] ?? null,
                'process_name' => $row['process_name'] ?? null,
                'ratio' => $row['ratio'] ?? null,
                'sub_process_name' => $row['sub_process_name'],
                'line_date' => $row['line_date'] ?? null,
                'major_exceptions' => $row['major_exceptions'] ?? null,
                'correlated_risks' => $row['correlated_risks'] ?? null,
                'risk_family' => $row['risk_family'] ?? null,
                'gravity' => $row['gravity'] ?? null,
                'probability' => $row['probability'] ?? null,
                'control_description' => $row['control_description'] ?? null,
                'control_exists' => $row['control_exists'] ?? null,
                'control_owner' => $row['control_owner'] ?? null,
                'control_effectiveness' => $row['control_effectiveness'] ?? null,
                'residual_gravity' => $row['gravity'] ?? null,
                'residual_probability' => OperationalRiskRow::computeResidualProbability(
                    $row['probability'] ?? null,
                    $row['control_effectiveness'] ?? null
                ),
                'sort_order' => $index + 1,
            ];

            if (! empty($row['id'])) {
                $risk = OperationalRiskRow::query()
                    ->where('id', $row['id'])
                    ->where('entity_id', $entity->id)
                    ->first();

                if ($risk) {
                    $risk->update($payload);
                    $keptIds[] = $risk->id;
                    continue;
                }
            }

            $risk = OperationalRiskRow::query()->create($payload);
            $keptIds[] = $risk->id;
        }

        OperationalRiskRow::query()
            ->where('entity_id', $entity->id)
            ->whereNotIn('id', $keptIds)
            ->delete();
    }

    private function buildCartographieDashboardPayload(Request $request): array
    {
        $user = $request->user();
        $environmentCode = $request->query('environment');
        $formatter = app(OperationalRiskRowController::class);

        $entitiesQuery = Entity::query()
            ->with('environment:id,code,name')
            ->whereIn('type', ['department', 'agency'])
            ->where('is_active', true)
            ->visibleToUser($user)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($user->environment_id) {
            $entitiesQuery->where('environment_id', $user->environment_id);
        } elseif ($environmentCode) {
            $entitiesQuery->whereHas(
                'environment',
                fn ($query) => $query->where('code', $environmentCode)
            );
        } else {
            $environmentId = Environment::query()->orderBy('id')->value('id');

            if ($environmentId) {
                $entitiesQuery->where('environment_id', $environmentId);
            }
        }

        $entities = $entitiesQuery->get();
        $environment = $entities->first()?->environment;

        $rowsQuery = OperationalRiskRow::query()
            ->visibleInAnalyse()
            ->whereIn('entity_id', $entities->pluck('id'));

        if ($user->isEntityResponsable()) {
            $rowsQuery->visibleToEntityResponsable($user);
        }

        $rowsByEntity = $rowsQuery
            ->get()
            ->groupBy('entity_id');

        $formattedRows = $rowsByEntity
            ->flatten(1)
            ->map(fn (OperationalRiskRow $row) => $formatter->formatRow($row))
            ->values();

        $matrice = $this->buildMatricePayload();

        return [
            'title' => 'CARTOGRAPHIE DES RISQUES',
            'subtitle' => $environment
                ? 'Vue d\'ensemble des principaux risques — '.$environment->name
                : 'Vue d\'ensemble des principaux risques',
            'environment' => $environment,
            'classifications' => $matrice['classifications'],
            'matrix' => $matrice['matrix'],
            'risk_categories' => RiskCategory::query()
                ->with(['families' => fn ($query) => $query->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get(),
            'rows' => $formattedRows,
            'gross' => $this->buildCartographyModePayload($entities, $rowsByEntity, 'gross'),
            'residual' => $this->buildCartographyModePayload($entities, $rowsByEntity, 'residual'),
        ];
    }

    private function buildCartographyModePayload($entities, $rowsByEntity, string $mode): array
    {
        $entitySummaries = [];

        foreach ($entities as $entity) {
            $entityRows = $rowsByEntity->get($entity->id, collect());
            $summary = $this->computeEntityCartographySummary($entityRows, $mode);

            if ($summary === null) {
                continue;
            }

            $entitySummaries[] = array_merge($summary, [
                'id' => $entity->id,
                'name' => $entity->name,
                'code' => $entity->code,
                'type' => $entity->type,
            ]);
        }

        $gravities = array_column($entitySummaries, 'gravity');
        $probabilities = array_column($entitySummaries, 'probability');

        $averages = null;
        if ($gravities !== []) {
            $avgG = round(array_sum($gravities) / count($gravities), 1);
            $avgP = round(array_sum($probabilities) / count($probabilities), 1);
            $avgRisk = round($avgG * $avgP, 1);

            $averages = [
                'gravity' => $avgG,
                'probability' => $avgP,
                'risk_score' => $avgRisk,
                'classification' => RiskClassification::forScore((int) round($avgRisk)),
            ];
        }

        $distribution = $this->buildClassificationDistribution($entitySummaries);

        return [
            'entities' => $entitySummaries,
            'averages' => $averages,
            'distribution' => $distribution,
            'total_entities' => count($entitySummaries),
        ];
    }

    private function computeEntityCartographySummary($rows, string $mode): ?array
    {
        if ($rows->isEmpty()) {
            return null;
        }

        $gravities = [];
        $probabilities = [];

        foreach ($rows as $row) {
            if ($mode === 'residual') {
                $gravity = $row->resolvedResidualGravity();
                $probability = $row->resolvedResidualProbability();
            } else {
                $gravity = $row->gravity;
                $probability = $row->probability;
            }

            if ($gravity === null || $probability === null) {
                continue;
            }

            $gravities[] = (float) $gravity;
            $probabilities[] = (float) $probability;
        }

        if ($gravities === []) {
            return null;
        }

        $avgG = round(array_sum($gravities) / count($gravities), 1);
        $avgP = round(array_sum($probabilities) / count($probabilities), 1);
        $riskScore = round($avgG * $avgP, 1);
        $cellG = max(1, min(6, (int) round($avgG)));
        $cellP = max(1, min(6, (int) round($avgP)));

        return [
            'gravity' => $avgG,
            'probability' => $avgP,
            'risk_score' => $riskScore,
            'cell_gravity' => $cellG,
            'cell_probability' => $cellP,
            'classification' => RiskClassification::forScore((int) round($riskScore)),
        ];
    }

    private function buildClassificationDistribution(array $entitySummaries): array
    {
        $counts = [];

        foreach ($entitySummaries as $summary) {
            $code = $summary['classification']?->code ?? 'non_significatif';
            $counts[$code] = ($counts[$code] ?? 0) + 1;
        }

        $total = array_sum($counts);

        if ($total === 0) {
            return [];
        }

        return collect($counts)
            ->map(function (int $count, string $code) use ($total) {
                $classification = RiskClassification::query()->where('code', $code)->first();

                return [
                    'code' => $code,
                    'name' => $classification?->name ?? $code,
                    'color' => $classification?->color ?? '#94a3b8',
                    'count' => $count,
                    'percent' => round(($count / $total) * 100),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();
    }
}
