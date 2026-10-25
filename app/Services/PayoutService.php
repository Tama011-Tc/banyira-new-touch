<?php

namespace App\Services;

use App\Models\Payout;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Pays collected repayments out to the investor's own bank account (simulated here). A finance officer releases each payout. */
class PayoutService
{
    public function __construct(private readonly LedgerService $ledger, private readonly AuditLogger $audit) {}

    public function release(Payout $payout, User $officer): Payout
    {
        abort_unless($officer->role->can('finance.approve'), 403);
        Platform::assertMayMoveMoney();

        return DB::transaction(function () use ($payout, $officer) {
            $p = Payout::query()->whereKey($payout->id)->lockForUpdate()->firstOrFail();
            if ($p->status !== 'pending') {
                throw ValidationException::withMessages(['payout' => 'This payout is not waiting for release.']);
            }
            $profile = $p->investor->investorProfile;
            if (! $profile?->hasPayoutDetails()) {
                throw ValidationException::withMessages(['payout' => 'The investor has no bank details on file.']);
            }
            $this->ledger->post('payout', $p->id, [[LedgerService::REPAYMENTS_PAYABLE, 'debit', $p->amount, 'Payout to investor (simulated)'], [LedgerService::CLEARING, 'credit', $p->amount]], 'Payout released');
            $p->forceFill(['status' => 'released', 'released_by' => $officer->id, 'released_at' => now()])->save();
            $this->audit->record('payout.released', $p, ['amount' => $p->amount, 'bank' => $profile->payout_bank, 'account' => $profile->maskedAccount(), 'simulated' => true], $officer);
            app(Sms\SmsService::class)->send($p->investor, 'payout', config('banyira.brand').': a repayment payout has been sent to your bank account. See your portfolio for details.');

            return $p;
        });
    }
}
