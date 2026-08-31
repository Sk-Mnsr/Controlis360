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
                'code' => 'sometimes|string|max:50|unique:environments,code,'.$id,
                'is_active' => 'sometimes|boolean',
            ];
        };

        $this->updateValidationTextArray = $this->codeValidationMessages();
    }

    public function update(Request $request, int $id)
    {
        if ($request->filled('code')) {
            $request->merge(['code' => $this->normalizeCode($request->input('code'))]);
        }

        return parent::update($request, $id);
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

        if ($request->filled('code')) {
            $request->merge(['code' => $this->normalizeCode($request->input('code'))]);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'duplicate_from_environment_id' => 'nullable|exists:environments,id',
            'code' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
        ], $this->codeValidationMessages());

        if ($validator->fails()) {
            return $this->responseError($validator->errors()->toArray(), 422);
        }

        $code = $this->ensureUniqueCode(
            $this->normalizeCode($request->input('code')),
            (string) $request->input('name'),
        );

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

    private function codeValidationMessages(): array
    {
        return [
            'code.unique' => 'Ce code est déjà utilisé par un autre environnement. Pour le même pays, utilisez un identifiant distinct (ex. SN puis SN_CTI).',
        ];
    }

    private function ensureUniqueCode(string $requested, string $name): string
    {
        $taken = Environment::query()->pluck('code')
            ->map(fn ($code) => $this->normalizeCode((string) $code))
            ->filter()
            ->all();

        $requested = $this->normalizeCode($requested);
        if ($requested !== '' && ! in_array($requested, $taken, true)) {
            return $requested;
        }

        $iso = $this->isoFromCode($requested) ?? $this->suggestIsoCodeFromName($name, ignoreTaken: true);
        $slug = $this->slugWithoutCountry($name, $iso);
        $candidates = array_values(array_filter([
            $iso && $slug ? $iso.'_'.$slug : null,
            $slug ?: null,
            $iso,
        ]));

        foreach ($candidates as $candidate) {
            if ($candidate && ! in_array($candidate, $taken, true)) {
                return $candidate;
            }
        }

        $prefix = $iso ?: ($slug ?: 'ENV');
        $suffix = 2;
        $candidate = $prefix.'_'.$suffix;
        while (in_array($candidate, $taken, true)) {
            $suffix++;
            $candidate = $prefix.'_'.$suffix;
        }

        return $candidate;
    }

    private function isoFromCode(string $code): ?string
    {
        $isoCodes = ['BJ', 'BF', 'CI', 'CM', 'GA', 'GH', 'GN', 'GW', 'GQ', 'ML', 'MR', 'NE', 'NG', 'CG', 'CD', 'SN', 'TD', 'TG'];

        if (in_array($code, $isoCodes, true)) {
            return $code;
        }

        $prefix = explode('_', $code)[0] ?? '';

        return in_array($prefix, $isoCodes, true) ? $prefix : null;
    }

    private function slugWithoutCountry(string $name, ?string $iso): string
    {
        $slug = $this->normalizeCode(Str::upper(Str::slug($name, '_')));
        if ($slug === '') {
            return '';
        }

        if ($iso) {
            $slug = trim(preg_replace('/(^|_)'.$iso.'(_|$)/', '_', $slug) ?? $slug, '_');
            $countryNames = [
                'SN' => 'SENEGAL',
                'TG' => 'TOGO',
                'CI' => 'COTE_DIVOIRE',
                'BJ' => 'BENIN',
                'BF' => 'BURKINA_FASO',
                'ML' => 'MALI',
                'GN' => 'GUINEE',
                'GH' => 'GHANA',
                'NE' => 'NIGER',
                'NG' => 'NIGERIA',
                'CM' => 'CAMEROUN',
                'GA' => 'GABON',
                'TD' => 'TCHAD',
                'MR' => 'MAURITANIE',
                'CD' => 'RD_CONGO',
                'CG' => 'CONGO',
                'GW' => 'GUINEE_BISSAU',
                'GQ' => 'GUINEE_EQUATORIALE',
            ];
            if (isset($countryNames[$iso])) {
                $slug = trim(preg_replace('/(^|_)'.$countryNames[$iso].'(_|$)/', '_', $slug) ?? $slug, '_');
            }
        }

        return trim(preg_replace('/_+/', '_', $slug) ?? $slug, '_');
    }

    private function generateUniqueCode(string $name): string
    {
        return $this->ensureUniqueCode('', $name);
    }

    private function normalizeCode(?string $code): string
    {
        $normalized = Str::upper(trim((string) $code));
        $normalized = preg_replace('/\s+/', '_', $normalized) ?? '';
        $normalized = preg_replace('/[^A-Z0-9_]/', '', $normalized) ?? '';

        return $normalized;
    }

    private function suggestIsoCodeFromName(string $name, bool $ignoreTaken = false): ?string
    {
        $key = Str::lower(Str::ascii(trim($name)));
        $key = preg_replace("/['’]/u", '', $key) ?? $key;
        $key = preg_replace('/[^a-z0-9]+/', ' ', $key) ?? $key;
        $key = trim($key);

        $map = [
            'senegal' => 'SN',
            'togo' => 'TG',
            'cote divoire' => 'CI',
            'cote ivoire' => 'CI',
            'benin' => 'BJ',
            'burkina faso' => 'BF',
            'mali' => 'ML',
            'guinee' => 'GN',
            'ghana' => 'GH',
            'niger' => 'NE',
            'nigeria' => 'NG',
            'cameroun' => 'CM',
            'gabon' => 'GA',
            'tchad' => 'TD',
            'mauritanie' => 'MR',
        ];

        $iso = $map[$key] ?? null;

        if (! $iso) {
            return null;
        }

        if ($ignoreTaken || ! Environment::query()->where('code', $iso)->exists()) {
            return $iso;
        }

        return null;
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
