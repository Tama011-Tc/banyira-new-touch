<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public function log(string $area, string $action, ?Model $subject = null, array $meta = [], ?User $user = null): AuditLog
    {
        $request = request();

        return AuditLog::create([
            'user_id' => $user?->id ?? auth('staff')->id() ?? auth('web')->id(),
            'area' => $area,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'meta' => $meta ?: null,
            'ip' => $request?->ip(),
            'user_agent' => substr((string) $request?->userAgent(), 0, 255) ?: null,
        ]);
    }
}
