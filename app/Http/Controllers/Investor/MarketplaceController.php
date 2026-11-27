<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use App\Http\Requests\OfferRequest;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Services\MarketplaceService;
use App\Services\MessagingService;
use App\Services\RulesService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'industry' => ['nullable', 'string', 'max:60'],
            'region' => ['nullable', 'string', 'max:60'],
            'risk' => ['nullable', 'in:A+,A,B,C'],
            'amount' => ['nullable', 'in:under1m,1to5m,over5m'],
            'term' => ['nullable', 'in:upto12,13to24,over24'],
            'status' => ['nullable', 'in:open,all'],
            'sort' => ['nullable', 'in:newest,remaining,rate,term'],
        ]);

        $q = Listing::query()
            ->when(($f['status'] ?? 'open') === 'open', fn ($q) => $q->where('status', 'open')->where('closes_at', '>', now()))
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->whereLike('public_name', "%{$v}%")->orWhereLike('purpose', "%{$v}%")->orWhereLike('summary', "%{$v}%")))
            ->when($f['industry'] ?? null, fn ($q, $v) => $q->where('industry', $v))
            ->when($f['region'] ?? null, fn ($q, $v) => $q->where('region', $v))
            ->when($f['risk'] ?? null, fn ($q, $v) => $q->where('risk_class', $v))
            ->when($f['amount'] ?? null, fn ($q, $v) => match ($v) {
                'under1m' => $q->where('amount_requested', '<', 1_000_000),
                '1to5m' => $q->whereBetween('amount_requested', [1_000_000, 5_000_000]),
                default => $q->where('amount_requested', '>', 5_000_000),
            })
            ->when($f['term'] ?? null, fn ($q, $v) => match ($v) {
                'upto12' => $q->where('term_months', '<=', 12),
                '13to24' => $q->whereBetween('term_months', [13, 24]),
                default => $q->where('term_months', '>', 24),
            });

        match ($f['sort'] ?? 'newest') {
            'remaining' => $q->orderByRaw('amount_requested - amount_accepted desc'),
            'rate' => $q->orderByDesc('indicative_rate'),
            'term' => $q->orderBy('term_months'),
            default => $q->latest('published_at'),
        };

        return Inertia::render('Investor/Marketplace', [
            'listings' => ListingResource::collection($q->paginate(10)->withQueryString()),
            'filters' => $f,
            'options' => [
                'industries' => Listing::distinct()->orderBy('industry')->pluck('industry'),
                'regions' => Listing::distinct()->orderBy('region')->pluck('region'),
            ],
        ]);
    }

    public function show(Request $request, Listing $listing, RulesService $rules)
    {
        $user = $request->user();
        $r = $rules->active();

        return Inertia::render('Investor/Listing', [
            'listing' => (new ListingResource($listing))->resolve(),
            'myOffers' => $listing->offers()->where('investor_id', $user->id)->latest('id')->get(['id', 'reference', 'amount', 'annual_rate', 'term_months', 'status', 'created_at']),
            'offerCount' => $listing->offers()->whereIn('status', ['pending', 'accepted'])->count(),
            'limits' => [
                'ceiling' => $r->interest_ceiling,
                'maxMonths' => $r->band($listing->risk_class)?->max_months,
                'minOffer' => config('banyira.marketplace.min_offer'),
            ],
            'canOffer' => $user->investorProfile?->verification_status === 'verified',
        ]);
    }

    public function offer(OfferRequest $request, Listing $listing, MarketplaceService $market)
    {
        $offer = $market->makeOffer($listing, $request->user()->load('investorProfile'), $request->validated());

        return redirect('/investor/offers')->with('success', "Offer {$offer->reference} sent. The business will compare it with other offers.");
    }

    public function message(Request $request, Listing $listing, MessagingService $messaging)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $listing->load('application.business.user');
        $c = $messaging->conversationFor($request->user(), $listing);
        $messaging->send($c, $request->user(), $data['body']);

        return redirect('/investor/messages/'.$c->id);
    }
}
