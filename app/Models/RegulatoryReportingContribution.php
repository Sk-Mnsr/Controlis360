<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegulatoryReportingContribution extends BaseModel
{
    protected $fillable = [
        'fiche_id',
        'valeur',
        'contenu',
        'date',
        'nom',
        'attachment_path',
        'attachments',
        'created_by',
    ];

    protected $appends = [
        'attachment_items',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'date' => 'date',
        ];
    }

    public function getAttachmentItemsAttribute(): array
    {
        $items = collect($this->attachments ?? [])
            ->filter(fn ($item) => is_array($item))
            ->map(fn ($item) => [
                'nom' => trim((string) ($item['nom'] ?? '')) ?: null,
                'path' => $item['path'] ?? null,
            ])
            ->filter(fn ($item) => $item['path'] || $item['nom'])
            ->values()
            ->all();

        if ($items) {
            return $items;
        }

        if ($this->attachment_path || $this->nom) {
            return [[
                'nom' => $this->nom,
                'path' => $this->attachment_path,
            ]];
        }

        return [];
    }

    public function fiche(): BelongsTo
    {
        return $this->belongsTo(RegulatoryReportingFiche::class, 'fiche_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
