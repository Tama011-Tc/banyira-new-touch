<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Append-only, hash-chained audit trail. Each row's hash covers the previous row's hash, so removing or editing
 * history is detectable (verifyChain), and database triggers refuse UPDATE/DELETE outright.
 * Never put passwords, tokens, document contents or national IDs in $meta.
 */
class AuditLogger
{
    public function record(string $action, ?Model $subject = null, array $meta = [], ?User $actor = null): AuditLog
    {
        $actor ??= auth()->user();

        return DB::transaction(function () use ($action, $subject, $meta, $actor) {
            $prev = AuditLog::query()->orderByDesc('id')->lockForUpdate()->value('hash');
            $at = now();
            $payload = [
                'actor' => $actor?->getKey(), 'role' => $actor?->role?->value, 'action' => $action,
                'subject' => $subject ? class_basename($subject).'#'.$subject->getKey() : null, 'meta' => $meta, 'at' => $at->toIso8601String(),
            ];

            return AuditLog::create([
                'actor_id' => $actor?->getKey(), 'actor_role' => $actor?->role?->value, 'action' => $action,
                'subject_type' => $subject ? class_basename($subject) : null, 'subject_id' => $subject?->getKey(),
                'meta' => $meta ?: null, 'ip' => request()?->ip(), 'prev_hash' => $prev,
                'hash' => hash('sha256', ($prev ?? '').json_encode($payload)), 'created_at' => $at,
            ]);
        });
    }

    /** @return bool true when every row links to its predecessor. */
    public function verifyChain(): bool
    {
        $prev = null;
        foreach (AuditLog::query()->orderBy('id')->cursor() as $row) {
            if ($row->prev_hash !== $prev) {
                return false;
            }
            $prev = $row->hash;
        }

        return true;
    }
}
