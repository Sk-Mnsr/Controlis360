<?php

namespace App\Http\Controllers\API;

use App\Models\ScaleLevel;
use Illuminate\Validation\Rule;
use Maravel\Http\Controllers\APIController;

/**
 * @group Échelles
 */
class ScaleLevelController extends APIController
{
    protected string $modelClass = ScaleLevel::class;

    protected array $indexSearchFieldList = ['qualification', 'label', 'description'];

    public function __construct()
    {
        parent::__construct();

        $this->storeValidationArray = [
            'type' => 'required|in:gravity,probability,control',
            'level' => [
                'required',
                'integer',
                'min:1',
                'max:99',
                Rule::unique('scale_levels')->where(fn ($query) => $query->where('type', request()->input('type'))),
            ],
            'qualification' => 'required|string|max:255',
            'description' => 'required|string',
            'label' => 'nullable|string|max:255',
            'maturity_label' => 'nullable|string|max:255',
        ];

        $this->updateGetValidationArrayFunction = function (int $id) {
            $level = ScaleLevel::query()->find($id);

            return [
                'type' => 'sometimes|in:gravity,probability,control',
                'level' => [
                    'sometimes',
                    'integer',
                    'min:1',
                    'max:99',
                    Rule::unique('scale_levels')
                        ->ignore($id)
                        ->where(fn ($query) => $query->where('type', $level?->type ?? request()->input('type'))),
                ],
                'qualification' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'label' => 'nullable|string|max:255',
                'maturity_label' => 'nullable|string|max:255',
            ];
        };

        $this->storeBeforeCreateFunction = function (array $requestData) {
            $requestData['label'] = $requestData['label'] ?? $requestData['qualification'];

            return $requestData;
        };

        $this->updateBeforeUpdateFunction = function ($model, array $requestData) {
            if (isset($requestData['qualification']) && ! isset($requestData['label'])) {
                $requestData['label'] = $requestData['qualification'];
            }

            return $requestData;
        };
    }
}
