<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationAnswer extends BaseModel
{
    protected $fillable = [
        'question_id',
        'application_type_id',
        'application_id',
        'value',
        'details',
        'answered_by_id',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(ApplicationQuestion::class, 'question_id');
    }

    public function applicationType(): BelongsTo
    {
        return $this->belongsTo(ApplicationType::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function answeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by_id');
    }

    public function isFilled(): bool
    {
        return filled(trim((string) $this->value)) || filled(trim((string) $this->details));
    }
}
