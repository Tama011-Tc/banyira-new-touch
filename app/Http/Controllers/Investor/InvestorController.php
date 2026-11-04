<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use App\Models\InvestmentOffer;
use App\Models\RepaymentAllocation;
use App\Models\RepaymentSchedule;
use App\Support\Money;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvestorController extends Controller
{
    public function overview(Request $request)
    {
        $user = $request->user()->load('investorProfile');
        $investments = $user->investments()->with('agreement.listing:id,public_name,industry,risk_class')->get();
        $active = $investments->whereIn('status', ['active', 'in_arrears']);
        $ids = $investments->pluck('id')->all();

        $expected = RepaymentSchedule::whereIn('investment_id', $ids)->where('status', '!=', 'paid')
            ->selectRaw('COALESCE(SUM(principal_due - principal_paid),0) p, COALESCE(SUM(interest_due - interest_paid),0) i')->first();
        $next = RepaymentSchedule::whereIn('investment_id', $ids)->where('status', '!=', 'paid')->orderBy('due_date')->first();

        return Inertia::render('Investor/Overview', [
            'verification' => $user->investorProfile->verification_status,
            'emailVerified' => (bool) $user->email_verified_at,
            'stats' => [
                'invested' => Money::add('0', ...$investments->pluck('principal')->all()),
                'active' => $active->count(),
                'outstanding' => Money::add('0', ...$active->map->outstanding()->all()),
                'principalReceived' => Money::add('0', ...$investments->pluck('principal_repaid')->all()),
                'interestReceived' => Money::add('0', ...$investments->pluck('interest_received')->all()),
                'expectedPrincipal' => Money::of($expected->p),
                'expectedInterest' => Money::of($expected->i),
                'committed' => Money::add('0', ...InvestmentOffer::where('investor_id', $user->id)->where('status', 'accepted')->whereDoesntHave('listing.agreement', fn ($q) => $q->where('status', 'active'))->pluck('amount')->all()),
                'pendingOffers' => InvestmentOffer::where('investor_id', $user->id)->where('status', 'pending')->count(),
                'next' => $next ? ['due' => $next->due_date->toDateString(), 'amount' => $next->amountOutstanding()] : null,
            ],
            'byRisk' => $active->groupBy(fn ($i) => $i->agreement->listing->risk_class)->map(fn ($g, $k) => ['l' => 'Class '.$k, 'v' => (float) Money::add('0', ...$g->map->outstanding()->all())])->values(),
            'byIndustry' => $active->groupBy(fn ($i) => $i->agreement->listing->industry)->map(fn ($g, $k) => ['l' => $k, 'v' => (float) Money::add('0', ...$g->map->outstanding()->all())])->values(),
            'activity' => RepaymentAllocation::whereIn('repayment_schedule_id', RepaymentSchedule::whereIn('investment_id', $ids)->select('id'))
                ->latest('id')->limit(6)->get()->map->only(['principal', 'interest', 'created_at']),
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user()->load('investorProfile', 'verifications');

        return Inertia::render('Investor/Profile', [
            'profile' => $user->investorProfile->only(['investor_type', 'source_of_funds', 'payout_method', 'verification_status', 'risk_acknowledged_at']),
            'contact' => $user->only(['name', 'email', 'phone']),
            'verifications' => $user->verifications->map->only(['kind', 'status', 'reviewed_at']),
        ]);
    }
}
