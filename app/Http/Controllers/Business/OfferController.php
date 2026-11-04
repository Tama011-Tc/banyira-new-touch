<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\InvestmentOffer;
use App\Models\Listing;
use App\Services\MarketplaceService;
use App\Services\RulesService;
use App\Support\Money;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** The business compares investor offers and accepts or rejects them. */
class OfferController extends Controller
{
    public function __construct(private MarketplaceService $market) {}

    public function index(Request $request, RulesService $rules)
    {
        $profileId = $request->user()->businessProfile->id;
        $listing = Listing::whereHas('application', fn ($q) => $q->where('business_profile_id', $profileId))
            ->with(['offers' => fn ($q) => $q->latest('id')])->latest('id')->first();
        $fee = $listing ? $rules->serviceFee($listing->amount_accepted ?: $listing->amount_requested) : null;

        return Inertia::render('Business/Offers', [
            'listing' => $listing ? [
                ...$listing->only(['id', 'reference', 'public_name', 'amount_requested', 'amount_accepted', 'risk_class', 'indicative_rate', 'term_months', 'status', 'closes_at']),
                'remaining' => $listing->remaining(),
            ] : null,
            'offers' => $listing ? $listing->offers->map(fn ($o, $i) => [
                'id' => $o->id, 'reference' => $o->reference,
                // Investors are shown by a stable alias; identities stay private until an agreement exists.
                'investor' => 'Investor '.str_pad((string) ($o->investor_id * 37 % 9000 + 1000), 4, '0'),
                'amount' => $o->amount, 'rate' => $o->annual_rate, 'term' => $o->term_months, 'conditions' => $o->conditions,
                'status' => $o->status, 'expires' => $o->expires_at, 'created' => $o->created_at,
                'monthly' => (fn ($r) => Money::add($r['principal'], $r['interest']))(Money::amortise($o->amount, $o->annual_rate, $o->term_months)[0]),
            ]) : [],
            'fee' => $fee,
        ]);
    }

    public function accept(Request $request, InvestmentOffer $offer)
    {
        $this->market->accept($offer, $request->user()->load('businessProfile'));

        return back()->with('success', 'Offer accepted.');
    }

    public function reject(Request $request, InvestmentOffer $offer)
    {
        $this->market->reject($offer, $request->user()->load('businessProfile'));

        return back()->with('success', 'Offer rejected. The investor has been told.');
    }

    public function close(Request $request, Listing $listing)
    {
        $this->market->closeFunding($listing, $request->user()->load('businessProfile'));

        return redirect('/business/financing')->with('success', 'Funding closed. Your agreement is being prepared.');
    }
}
