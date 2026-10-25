<?php

namespace App\Console\Commands;

use App\Models\Disbursement;
use App\Models\InvestmentCommitment;
use App\Models\Listing;
use App\Models\LoanAgreement;
use App\Models\PaymentAttempt;
use App\Models\ApprovalRequest;
use App\Services\ApplicationWorkflow;
use App\Services\AuditLogger;
use App\Services\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Daily: close campaigns whose deadline has passed.
 *  - reached the minimum: stop taking offers; the funded parts proceed.
 *  - missed the minimum: nobody is paid. Unpaid agreements are cancelled; money already paid in by investors is queued
 *    for a refund as a reversal that a second finance person must approve (nothing moves automatically).
 */
class CloseCampaigns extends Command
{
    protected $signature = 'banyira:close-campaigns';

    protected $description = 'Close listings past their funding deadline';

    public function handle(PaymentService $payments, ApplicationWorkflow $workflow, AuditLogger $audit): int
    {
        $closed = 0;
        $listings = Listing::withoutGlobalScopes()->where('status', 'published')->whereDate('deadline', '<', now()->toDateString())->get();
        foreach ($listings as $listing) {
            DB::transaction(function () use ($listing, $payments, $workflow, $audit) {
                $listing = Listing::query()->whereKey($listing->id)->lockForUpdate()->first();
                if ($listing->status !== 'published') {
                    return;
                }
                $c = $payments->campaignStatus($listing);
                $app = $listing->application;
                if ($c['ready'] && $c['funded_pct'] > 0) {
                    $listing->forceFill(['status' => 'funded'])->save();
                    $app->status === 'funding' && $workflow->transition($app, 'funded', null, 'Deadline reached with the minimum funded');
                } else {
                    $listing->forceFill(['status' => 'closed'])->save();
                    foreach (LoanAgreement::where('listing_id', $listing->id)->whereIn('status', ['pending_funding', 'funded'])->get() as $a) {
                        if ($a->status === 'pending_funding') {
                            $a->forceFill(['status' => 'cancelled'])->save();
                            InvestmentCommitment::where('agreement_id', $a->id)->update(['status' => 'cancelled']);

                            continue;
                        }
                        // funded but never released: queue the refund for a second person to approve
                        $attempt = PaymentAttempt::where('agreement_id', $a->id)->where('purpose', 'funding')->where('status', 'succeeded')->first();
                        Disbursement::where('agreement_id', $a->id)->where('status', 'pending_approval')->update(['status' => 'cancelled']);
                        $attempt && ApprovalRequest::create(['type' => 'reversal', 'payload' => ['attempt_id' => $attempt->id], 'reason' => "Campaign closed at {$c['funded_pct']}% (minimum {$c['min_pct']}%): refund the investor.", 'requested_by' => 0]);
                    }
                    $app->status === 'funding' || $app->status === 'published' ? $workflow->transition($app, 'expired', null, "Campaign closed at {$c['funded_pct']}%, below the {$c['min_pct']}% minimum") : null;
                }
                $audit->record('campaign.closed', $listing, ['funded_pct' => $c['funded_pct'], 'min_pct' => $c['min_pct']], null);
            });
            $closed++;
        }
        $this->info("Closed {$closed} campaign(s).");

        return self::SUCCESS;
    }
}
