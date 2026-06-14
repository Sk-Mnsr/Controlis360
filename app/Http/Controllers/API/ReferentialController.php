<?php

namespace App\Http\Controllers\API;

use App\Models\Country;
use App\Models\Department;
use App\Models\RiskCategory;
use App\Models\RiskClassification;
use App\Models\ScaleLevel;
use App\Models\Subsidiary;
use Illuminate\Http\Request;
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
        $classifications = RiskClassification::query()->orderBy('sort_order')->get();

        $matrix = [];
        for ($probability = 6; $probability >= 1; $probability--) {
            $row = [];
            for ($gravity = 1; $gravity <= 6; $gravity++) {
                $score = $gravity * $probability;
                $row[] = [
                    'gravity' => $gravity,
                    'probability' => $probability,
                    'score' => $score,
                    'classification' => RiskClassification::forScore($score),
                ];
            }
            $matrix[] = $row;
        }

        return $this->responseOk([
            'classifications' => $classifications,
            'matrix' => $matrix,
        ]);
    }

    /**
     * Familles de risques avec catégories.
     */
    public function riskFamilies()
    {
        return $this->responseOk(
            RiskCategory::query()
                ->with(['families' => fn ($q) => $q->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get()
        );
    }
}
