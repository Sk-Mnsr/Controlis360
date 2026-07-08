<?php

namespace App\Http\Controllers\API;

use App\Models\Entity;
use App\Models\Environment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maravel\Http\Controllers\APIController;

/**
 * @group Environnements
 */
class EnvironmentController extends APIController
{
    protected string $modelClass = Environment::class;

    protected array $indexSearchFieldList = ['name', 'code'];

    public function __construct()
    {
        parent::__construct();

        $this->indexManualFilter = function ($query, $user) {
            $query->withCount('entities');

            if ($user->isEnvironmentAdmin()) {
                $environmentIds = $user->environment_ids;
                if (! empty($environmentIds)) {
                    $query->whereIn('id', $environmentIds);
                }
            }

            return $query;
        };

        $this->updateGetValidationArrayFunction = function (int $id) {
            return [
                'name' => 'sometimes|string|max:255',
                'code' => 'sometimes|string|max:50|unique:environments,code,' . $id,
                'is_active' => 'sometimes|boolean',
            ];
        };
    }

    /**
     * Crée un environnement et duplique éventuellement les entités
     * depuis un environnement de base (paramètre de formulaire, non persisté).
     */
    public function store(Request $request)
    {
        if (! Gate::inspect('create', Environment::class)->allowed()) {
            return $this->responseError(['auth' => ['Action non autorisée']], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'duplicate_from_environment_id' => 'nullable|exists:environments,id',
            'code' => 'nullable|string|max:50|unique:environments,code',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $code = $request->input('code') ?: $this->generateUniqueCode($request->input('name'));

        $environment = DB::transaction(function () use ($request, $code) {
            $environment = Environment::query()->create([
                'name' => $request->input('name'),
                'code' => $code,
                'is_active' => $request->boolean('is_active', true),
            ]);

            if ($request->filled('duplicate_from_environment_id')) {
                $this->duplicateEntities(
                    (int) $request->input('duplicate_from_environment_id'),
                    $environment
                );
            }

            return $environment->load('entities');
        });

        return $this->responseOk(['environment' => $environment], status: 201);
    }

    /**
     * Liste simplifiée pour les sélecteurs (duplication).
     */
    public function options(Request $request)
    {
        if (! $request->user()->isSuperAdmin()) {
            return $this->responseError(['auth' => ['Action réservée au super administrateur']], 403);
        }

        return $this->responseOk(
            Environment::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
        );
    }

    private function generateUniqueCode(string $name): string
    {
        $base = Str::upper(Str::slug($name, '_'));
        $code = $base;
        $suffix = 1;

        while (Environment::query()->where('code', $code)->exists()) {
            $code = $base . '_' . $suffix;
            $suffix++;
        }

        return $code;
    }

    private function duplicateEntities(int $sourceEnvironmentId, Environment $target): void
    {
        $entities = Entity::query()
            ->where('environment_id', $sourceEnvironmentId)
            ->orderBy('sort_order')
            ->get();

        foreach ($entities as $entity) {
            Entity::query()->create([
                'environment_id' => $target->id,
                'type' => $entity->type,
                'name' => $entity->name,
                'code' => $entity->code,
                'is_active' => $entity->is_active,
                'sort_order' => $entity->sort_order,
            ]);
        }
    }
}
