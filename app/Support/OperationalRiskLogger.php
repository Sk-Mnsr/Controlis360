<?php

namespace App\Support;

use App\Models\OperationalRiskLog;
use App\Models\OperationalRiskRow;
use App\Models\User;

class OperationalRiskLogger
{
    public static function log(
        OperationalRiskRow $row,
        User $user,
        string $action,
        ?string $message = null,
        array $metadata = [],
    ): void {
        OperationalRiskLog::query()->create([
            'operational_risk_row_id' => $row->id,
            'entity_id' => $row->entity_id,
            'user_id' => $user->id,
            'action' => $action,
            'message' => $message,
            'metadata' => $metadata ?: null,
            'created_at' => now(),
        ]);
    }
}
