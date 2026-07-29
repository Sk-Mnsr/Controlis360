<?php

namespace App\Services;

use App\Models\Mission;
use App\Models\MissionType;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MissionTypeService
{
    public function allActive(): Collection
    {
        return MissionType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function allForManagement(): Collection
    {
        return MissionType::query()
            ->withCount('missions')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function allowedForUser(?User $user, bool $includeInactiveCurrent = false, ?string $currentCode = null): Collection
    {
        $query = MissionType::query()->orderBy('sort_order')->orderBy('name');

        if (! $includeInactiveCurrent) {
            $query->where('is_active', true);
        }

        $types = $query->get();

        if (! $user || $user->isPlatformAdministrator()) {
            return $types;
        }

        $filtered = $types->filter(fn (MissionType $type) => $this->typeAllowedForUser($type, $user));

        if ($currentCode && ! $filtered->contains(fn (MissionType $type) => $type->code === $currentCode)) {
            $current = $types->firstWhere('code', $currentCode);

            if ($current) {
                $filtered = $filtered->push($current);
            }
        }

        return $filtered->values();
    }

    public function allowedCodesForUser(?User $user, ?string $currentCode = null): array
    {
        return $this->allowedForUser($user, currentCode: $currentCode)
            ->pluck('code')
            ->all();
    }

    public function typeAllowedForUser(MissionType $type, ?User $user): bool
    {
        if (! $user || $user->isPlatformAdministrator()) {
            return true;
        }

        $profiles = $type->profiles ?? [];

        return in_array($user->profile, $profiles, true);
    }

    public function labelForCode(?string $code): ?string
    {
        if (! $code) {
            return null;
        }

        return MissionType::query()->where('code', $code)->value('name') ?? $code;
    }

    public function formatForSelect(MissionType $type): array
    {
        return [
            'id' => $type->id,
            'value' => $type->code,
            'label' => $type->name,
            'profiles' => $type->profiles ?? [],
            'description' => $type->description,
            'accent_color' => $type->accent_color,
            'sort_order' => $type->sort_order,
            'is_active' => (bool) $type->is_active,
            'missions_count' => $type->missions_count ?? null,
        ];
    }

    public function generateUniqueCode(string $name, ?int $ignoreId = null): string
    {
        $base = $this->normalizeCode($name) ?: 'mission_type';
        $code = $base;
        $suffix = 1;

        while ($this->codeExists($code, $ignoreId)) {
            $code = $base.'_'.$suffix;
            $suffix++;
        }

        return $code;
    }

    public function normalizeCode(?string $code): string
    {
        $normalized = Str::lower(trim((string) $code));
        $normalized = str_replace(['-', ' ', '.'], '_', $normalized);
        $normalized = preg_replace('/[^a-z0-9_]+/', '_', $normalized) ?? '';
        $normalized = preg_replace('/_+/', '_', $normalized) ?? '';

        return trim($normalized, '_');
    }

    public function codeExists(string $code, ?int $ignoreId = null): bool
    {
        return MissionType::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('code', $code)
            ->exists();
    }

    public function canDelete(MissionType $type): bool
    {
        return ! Mission::query()->where('mission_type', $type->code)->exists();
    }
}
