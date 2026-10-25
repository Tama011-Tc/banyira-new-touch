<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Business;
use App\Models\Complaint;
use App\Models\Disbursement;
use App\Models\LoanAgreement;
use App\Models\MessageReport;
use App\Models\PlatformFee;
use App\Models\RepaymentSchedule;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    public function executive(): array
    {
        $by = Application::query()->selectRaw('status, COUNT(*) n, SUM(amount) amt')->groupBy('status')->get()->keyBy('status');
        $count = fn (array $s) => (int) $by->only($s)->sum('n');
        $live = ['active', 'overdue', 'restructured', 'completed', 'defaulted'];
        $agreements = LoanAgreement::query()->where('status', '!=', 'cancelled');
        $due = RepaymentSchedule::query()->whereIn('agreement_id', LoanAgreement::query()->whereIn('status', $live)->select('id'))->where('due_on', '<=', now()->toDateString());
        $scheduledDue = (int) (clone $due)->selectRaw('SUM(principal_due + interest_due) s')->value('s');
        $paidOnDue = (int) (clone $due)->selectRaw('SUM(principal_paid + interest_paid) s')->value('s');
        $turn = DB::table('credit_decisions as d')->join('applications as a', 'a.id', '=', 'd.application_id')->whereNotNull('a.submitted_at')->selectRaw('AVG(TIMESTAMPDIFF(HOUR, a.submitted_at, d.created_at)) h')->value('h');

        return [
            'applications' => ['total' => $count(array_diff(ApplicationWorkflow::STATES, ['draft'])), 'awaiting' => $count(['submitted', 'screening', 'assessment', 'info_requested']), 'approved' => $count(['approved', 'listing_prepared', 'published', 'funding', 'funded', 'repayment_active', 'completed', 'overdue', 'restructured', 'defaulted']), 'rejected' => $count(['rejected'])],
            'campaigns' => ['active' => $count(['published', 'funding']), 'completed' => $count(['funded', 'repayment_active', 'completed'])],
            'money' => [
                'requested' => (int) Application::query()->where('status', '!=', 'draft')->sum('amount'),
                'committed' => (int) (clone $agreements)->sum('principal'),
                'funded' => (int) LoanAgreement::query()->whereNotIn('status', ['pending_funding', 'cancelled'])->sum('principal'),
                'disbursed' => (int) Disbursement::query()->where('status', 'simulated')->sum('gross'),
            ],
            'people' => ['investors' => (int) LoanAgreement::query()->distinct('investor_id')->count('investor_id'), 'businesses' => (int) LoanAgreement::query()->distinct('business_id')->count('business_id')],
            'repayment' => [
                'scheduled_due' => $scheduledDue, 'paid' => $paidOnDue, 'rate' => $scheduledDue > 0 ? round($paidOnDue * 100 / $scheduledDue, 1) : null,
                'overdue_loans' => LoanAgreement::where('status', 'overdue')->count(), 'defaults' => LoanAgreement::where('status', 'defaulted')->count(),
            ],
            'revenue' => PlatformFee::query()->where('payer', '!=', 'platform')->whereIn('status', ['collected'])->selectRaw('fee_type, SUM(amount) total')->groupBy('fee_type')->pluck('total', 'fee_type')->map(fn ($v) => (int) $v)->all(),
            'provider_costs' => (int) PlatformFee::query()->where('fee_type', 'processing')->where('status', 'collected')->sum('amount'),
            'by_sector' => DB::table('loan_agreements as l')->join('businesses as b', 'b.id', '=', 'l.business_id')->where('l.status', '!=', 'cancelled')->selectRaw('b.sector name, SUM(l.principal) total')->groupBy('b.sector')->orderByDesc('total')->get()->map(fn ($r) => ['name' => $r->name, 'total' => (int) $r->total])->all(),
            'by_district' => DB::table('loan_agreements as l')->join('businesses as b', 'b.id', '=', 'l.business_id')->where('l.status', '!=', 'cancelled')->selectRaw('b.district name, SUM(l.principal) total')->groupBy('b.district')->orderByDesc('total')->get()->map(fn ($r) => ['name' => $r->name, 'total' => (int) $r->total])->all(),
            'turnaround_hours' => $turn !== null ? round((float) $turn, 1) : null,
            'risk' => ['complaints_open' => Complaint::where('status', '!=', 'closed')->where('status', '!=', 'resolved')->count(), 'fraud_flags' => MessageReport::where('status', 'open')->count(), 'rejected_webhooks' => WebhookEvent::where('outcome', 'rejected')->count()],
        ];
    }

    public function investorPortfolio(User $investor): array
    {
        $ag = LoanAgreement::query()->where('investor_id', $investor->id)->with(['business', 'listing', 'schedule'])->get();
        $live = $ag->whereNotIn('status', ['cancelled']);
        $deployed = $live->whereNotIn('status', ['pending_funding']);
        $interestReceived = 0;
        $principalReceived = 0;
        $overdue = 0;
        foreach ($deployed as $a) {
            $interestReceived += $a->schedule->sum('interest_paid');
            $principalReceived += $a->schedule->sum('principal_paid');
            if ($a->status === 'overdue') {
                $overdue += $a->schedule->sum(fn ($r) => $r->principal_due - $r->principal_paid);
            }
        }
        $fees = (int) PlatformFee::query()->whereIn('agreement_id', $ag->pluck('id'))->where('payer', 'investor')->where('status', 'collected')->sum('amount');
        $projectedInterest = $deployed->sum('total_interest');

        return [
            'committed' => (int) $live->sum('principal'), 'deployed' => (int) $deployed->sum('principal'),
            'principal_outstanding' => (int) ($deployed->sum('principal') - $principalReceived), 'principal_received' => (int) $principalReceived,
            'interest_received' => (int) $interestReceived, 'projected_interest' => (int) $projectedInterest, 'fees_paid' => $fees,
            'net_realised' => (int) ($interestReceived - $fees), 'overdue_exposure' => (int) $overdue,
            'by_sector' => $deployed->groupBy(fn ($a) => $a->business->sector)->map(fn ($g, $k) => ['name' => $k, 'total' => (int) $g->sum('principal')])->values()->all(),
            'by_risk' => $deployed->groupBy(fn ($a) => $a->listing->risk_category)->map(fn ($g, $k) => ['name' => $k, 'total' => (int) $g->sum('principal')])->values()->all(),
            'by_district' => $deployed->groupBy(fn ($a) => $a->business->district)->map(fn ($g, $k) => ['name' => $k, 'total' => (int) $g->sum('principal')])->values()->all(),
        ];
    }
}
