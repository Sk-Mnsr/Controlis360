<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationActionPlanComment extends BaseModel
{
    protected $fillable = [
        'recommendation_action_plan_id',
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

    public function actionPlan(): BelongsTo
    {
        return $this->belongsTo(RecommendationActionPlan::class, 'recommendation_action_plan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
