<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\AgreementParty;
use App\Models\Investment;
use App\Models\InvestmentOffer;
use App\Models\RepaymentAllocation;
use App\Services\AgreementService;
use App\Services\MarketplaceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PortfolioController extends Controller
{
    public function investments(Request $request)
    {
        $rows = Investment::where('investor_id', $request->user()->id)->with(['agreement.listing:id,public_name,risk_class', 'schedule'])->latest('id')->get();

        return Inertia::render('Investor/Investments', [
            'investments' => $rows->map(fn ($i) => [
                'id' => $i->id, 'reference' => $i->reference, 'business' => $i->agreement->listing->public_name, 'risk' => $i->agreement->listing->risk_class,
                'principal' => $i->principal, 'rate' => $i->annual_rate, 'term' => $i->term_months, 'started' => $i->started_on,
                'repaid' => $i->principal_repaid, 'interest' => $i->interest_received, 'outstanding' => $i->outstanding(), 'status' => $i->status,
                'schedule' => $i->schedule->map->only(['installment_no', 'due_date', 'principal_due', 'interest_due', 'principal_paid', 'interest_paid', 'status']),
            ]),
        ]);
    }

    public function offers(Request $request)
    {
        $rows = InvestmentOffer::where('investor_id', $request->user()->id)->with('listing:id,public_name,risk_class')->latest('id')->get();

        return Inertia::render('Investor/Offers', [
            'offers' => $rows->map(fn ($o) => [
                'id' => $o->id, 'reference' => $o->reference, 'listingId' => $o->listing_id, 'business' => $o->listing->public_name, 'risk' => $o->listing->risk_class,
                'amount' => $o->amount, 'rate' => $o->annual_rate, 'term' => $o->term_months, 'conditions' => $o->conditions,
                'status' => $o->status, 'created' => $o->created_at, 'expires' => $o->expires_at,
            ]),
            'tab' => $request->query('tab', 'all'),
        ]);
    }

    public function withdraw(Request $request, InvestmentOffer $offer, MarketplaceService $market)
    {
        $market->withdraw($offer, $request->user());

        return back()->with('success', 'Offer withdrawn.');
    }

    public function agreements(Request $request)
    {
        $parties = AgreementParty::where('user_id', $request->user()->id)->where('role', 'investor')->with('agreement.listing:id,public_name')->latest('id')->get();

        return Inertia::render('Investor/Agreements', [
            'agreements' => $parties->map(fn ($p) => [
                'id' => $p->agreement->id, 'reference' => $p->agreement->reference, 'business' => $p->agreement->listing->public_name,
                'status' => $p->agreement->status, 'principal' => $p->principal, 'rate' => $p->annual_rate, 'term' => $p->term_months,
                'signed' => $p->signed_at, 'fundsConfirmed' => $p->funds_confirmed_at,
            ]),
        ]);
    }

    public function sign(Request $request, Agreement $agreement, AgreementService $service)
    {
        $this->authorize('view', $agreement);
        $service->sign($agreement, $request->user());

        return back()->with('success', 'Agreement signed.');
    }

    public function repayments(Request $request)
    {
        $rows = RepaymentAllocation::query()
            ->join('repayment_schedules as s', 's.id', '=', 'repayment_allocations.repayment_schedule_id')
            ->join('investments as i', 'i.id', '=', 's.investment_id')
            ->join('agreements as a', 'a.id', '=', 'i.agreement_id')
            ->join('listings as l', 'l.id', '=', 'a.listing_id')
            ->where('i.investor_id', $request->user()->id)
            ->orderByDesc('repayment_allocations.id')
            ->select('repayment_allocations.id', 'repayment_allocations.principal', 'repayment_allocations.interest', 'repayment_allocations.created_at', 's.due_date', 's.installment_no', 'l.public_name as business', 'i.reference as investment')
            ->paginate(20);

        return Inertia::render('Investor/Repayments', ['rows' => $rows]);
    }

    public function transactions(Request $request)
    {
        $uid = $request->user()->id;
        $funding = AgreementParty::where('user_id', $uid)->whereNotNull('funds_confirmed_at')->with('agreement:id,reference')->get()
            ->map(fn ($p) => ['date' => $p->funds_confirmed_at, 'type' => 'Investment funded', 'reference' => $p->agreement->reference, 'amount' => '-'.$p->principal]);
        $repaid = RepaymentAllocation::query()->join('repayment_schedules as s', 's.id', '=', 'repayment_allocations.repayment_schedule_id')
            ->join('investments as i', 'i.id', '=', 's.investment_id')->where('i.investor_id', $uid)
            ->selectRaw('DATE(repayment_allocations.created_at) d, i.reference, SUM(repayment_allocations.principal) p, SUM(repayment_allocations.interest) n')
            ->groupBy('d', 'i.reference')->get()
            ->flatMap(fn ($r) => [
                ['date' => $r->d, 'type' => 'Principal repaid', 'reference' => $r->reference, 'amount' => $r->p],
                ['date' => $r->d, 'type' => 'Interest received', 'reference' => $r->reference, 'amount' => $r->n],
            ]);

        return Inertia::render('Investor/Transactions', ['rows' => $funding->concat($repaid)->sortByDesc('date')->values()]);
    }
}
