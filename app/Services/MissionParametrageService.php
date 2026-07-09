<?php

namespace App\Services;

use App\Models\MissionParametrage;
use App\Models\User;

class MissionParametrageService
{
    public const SETTINGS_KEY = 'settings';

    public function getSettings(): array
    {
        $stored = MissionParametrage::query()
            ->where('key', self::SETTINGS_KEY)
            ->value('value');

        return $this->mergeSettings(is_array($stored) ? $stored : []);
    }

    public function updateSettings(array $payload, ?User $user = null): array
    {
        $current = $this->getSettings();
        $merged = $this->mergeSettings(array_replace_recursive($current, $payload));

        MissionParametrage::query()->updateOrCreate(
            ['key' => self::SETTINGS_KEY],
            ['value' => $merged],
        );

        return $merged;
    }

    public function mergeSettings(array $stored): array
    {
        $defaults = $this->defaultSettings();

        return [
            'statuses' => $this->mergeStatusList($defaults['statuses'], $stored['statuses'] ?? []),
            'recommendation_statuses' => $this->mergeStatusList(
                $defaults['recommendation_statuses'],
                $stored['recommendation_statuses'] ?? [],
            ),
            'deadline_rules' => $this->mergeDeadlineRules(
                $defaults['deadline_rules'],
                $stored['deadline_rules'] ?? [],
            ),
        ];
    }

    private function defaultSettings(): array
    {
        $config = config('mission-parametrage', []);

        return [
            'statuses' => $this->normalizeStatusList(
                $config['statuses'] ?? [],
                [
                    'ouvert' => '#2563eb',
                    'ferme' => '#64748b',
                ],
            ),
            'recommendation_statuses' => $this->normalizeStatusList(
                $config['recommendation_statuses'] ?? [],
                [
                    'emise' => '#64748b',
                    'en_cours' => '#d97706',
                    'traitee' => '#047857',
                    'transmis' => '#2563eb',
                    'cloturee' => '#64748b',
                ],
            ),
            'deadline_rules' => [
                'near_threshold_days' => 10,
                'items' => [
                    ['key' => 'late', 'label' => 'En retard', 'text_color' => '#dc2626', 'badge_color' => '#dc2626'],
                    ['key' => 'warning', 'label' => 'Proche échéance', 'text_color' => '#ca8a04', 'badge_color' => '#ea580c'],
                    ['key' => 'ok', 'label' => 'Dans les délais', 'text_color' => '#334155', 'badge_color' => '#047857'],
                    ['key' => 'neutral', 'label' => '—', 'text_color' => '#64748b', 'badge_color' => '#64748b'],
                ],
            ],
        ];
    }

    private function normalizeColor(?string $color, string $fallback = '#64748b'): string
    {
        $map = [
            'blue' => '#2563eb',
            'amber' => '#d97706',
            'orange' => '#ea580c',
            'yellow' => '#ca8a04',
            'red' => '#dc2626',
            'emerald' => '#047857',
            'slate' => '#64748b',
            'violet' => '#7c3aed',
        ];

        if (! $color) {
            return $fallback;
        }

        if (str_starts_with($color, '#')) {
            return strtolower($color);
        }

        return $map[$color] ?? $fallback;
    }

    private function normalizeStatusList(array $items, array $defaultColors): array
    {
        return collect($items)->map(function (array $item) use ($defaultColors) {
            $value = $item['value'] ?? null;

            return [
                'value' => $value,
                'label' => $item['label'] ?? $value,
                'color' => $this->normalizeColor($item['color'] ?? ($defaultColors[$value] ?? 'slate')),
            ];
        })->values()->all();
    }

    private function mergeStatusList(array $defaults, array $stored): array
    {
        $storedByValue = collect($stored)->keyBy('value');

        $merged = collect($defaults)->map(function (array $item) use ($storedByValue) {
            $override = $storedByValue->get($item['value'], []);

            return [
                'value' => $item['value'],
                'label' => $override['label'] ?? $item['label'],
                'color' => $this->normalizeColor($override['color'] ?? $item['color']),
            ];
        });

        foreach ($stored as $item) {
            if (! $item['value'] || $merged->contains(fn (array $row) => $row['value'] === $item['value'])) {
                continue;
            }

            $merged->push([
                'value' => $item['value'],
                'label' => $item['label'] ?? $item['value'],
                'color' => $this->normalizeColor($item['color'] ?? 'slate'),
            ]);
        }

        return $merged->values()->all();
    }

    private function mergeDeadlineRules(array $defaults, array $stored): array
    {
        $storedItems = collect($stored['items'] ?? [])->keyBy('key');
        $defaultItems = collect($defaults['items'])->keyBy('key');

        $items = $defaultItems->map(function (array $item, string $key) use ($storedItems) {
            $override = $storedItems->get($key, []);

            return [
                'key' => $key,
                'label' => $override['label'] ?? $item['label'],
                'text_color' => $this->normalizeColor($override['text_color'] ?? $item['text_color']),
                'badge_color' => $this->normalizeColor($override['badge_color'] ?? $item['badge_color']),
            ];
        })->values();

        foreach ($storedItems as $key => $item) {
            if ($defaultItems->has($key)) {
                continue;
            }

            $items->push([
                'key' => $key,
                'label' => $item['label'] ?? $key,
                'text_color' => $this->normalizeColor($item['text_color'] ?? 'slate'),
                'badge_color' => $this->normalizeColor($item['badge_color'] ?? 'slate'),
            ]);
        }

        return [
            'near_threshold_days' => (int) ($stored['near_threshold_days'] ?? $defaults['near_threshold_days']),
            'items' => $items->values()->all(),
        ];
    }
}
