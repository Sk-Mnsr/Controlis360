<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationRegulatorComment extends BaseModel
{
    protected $fillable = [
        'recommendation_id',
        'comment_type',
        'user_id',
        'author_name',
        'commented_at',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'commented_at' => 'date',
        ];
    }

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(Recommendation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
