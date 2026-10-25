<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/** Four-eyes control: sensitive changes are requested by one person and approved by another. */
class ApprovalService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function request(string $type, array $payload, string $reason, User $requester): ApprovalRequest
    {
        if (mb_strlen(trim($reason)) < 10) {
            throw ValidationException::withMessages(['reason' => 'Explain why this change is needed.']);
        }
        $r = ApprovalRequest::create(['type' => $type, 'payload' => $payload, 'reason' => $reason, 'requested_by' => $requester->id]);
        $this->audit->record('approval.requested', $r, ['type' => $type], $requester);

        return $r;
    }

    public function decide(ApprovalRequest $r, User $approver, bool $approve): ApprovalRequest
    {
        abort_unless($approver->role->can('finance.approve'), 403);
        if ($r->status !== 'pending') {
            throw ValidationException::withMessages(['approval' => 'Already decided.']);
        }
        if ($r->requested_by === $approver->id) {
            throw ValidationException::withMessages(['approval' => 'You cannot approve your own request.']);
        }
        $r->forceFill(['status' => $approve ? 'approved' : 'rejected', 'decided_by' => $approver->id, 'decided_at' => now()])->save();
        if ($approve && in_array($r->type, ['fee_change', 'policy_change'], true)) {   // applied only now, with a trail
            foreach ($r->payload['settings'] as $k => $v) {
                Settings::set($k, $v);
            }
        }
        $this->audit->record($approve ? 'approval.approved' : 'approval.rejected', $r, ['type' => $r->type], $approver);

        return $r;
    }
}
