<?php

namespace App\Console\Commands;

use App\Models\RepaymentSchedule;
use App\Services\Sms\SmsService;
use App\Support\Money;
use Illuminate\Console\Command;

/** Daily: a reminder 3 days before an instalment is due, and one the day after it is missed. Idempotent per instalment. */
class SendReminders extends Command
{
    protected $signature = 'banyira:send-reminders';

    protected $description = 'SMS repayment reminders';

    public function handle(SmsService $sms): int
    {
        $sent = 0;
        $brand = config('banyira.brand');
        $rows = RepaymentSchedule::with('agreement.business.owner')->where('status', '!=', 'paid')
            ->whereHas('agreement', fn ($q) => $q->whereIn('status', ['active', 'overdue', 'restructured']))
            ->where(fn ($q) => $q->whereDate('due_on', now()->addDays(3)->toDateString())->orWhereDate('due_on', now()->subDay()->toDateString()))->get();
        foreach ($rows as $r) {
            $owner = $r->agreement->business->owner;
            $late = $r->due_on->isPast();
            $left = ($r->principal_due + $r->interest_due) - ($r->principal_paid + $r->interest_paid);
            $text = $late ? "{$brand}: your instalment of ".Money::format($left)." was due on {$r->due_on->format('d M')}. Please pay as soon as you can." : "{$brand}: reminder, ".Money::format($left)." is due on {$r->due_on->format('d M')}.";
            $owner && $sms->send($owner, 'repayment_reminder', $text, "reminder:{$r->id}:".($late ? 'late' : 'soon')) && $sent++;
        }
        $this->info("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }
}
