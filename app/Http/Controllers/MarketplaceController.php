<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\WatchlistItem;
use App\Services\ListingService;
use App\Services\MessagingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MarketplaceController extends Controller
{
    public function __construct(private readonly ListingService $listings) {}

    public function index(Request $request)
    {
        $q = Listing::query()->where('status', 'published')->with('application.business')
            ->when($request->sector, fn ($q, $v) => $q->whereHas('application.business', fn ($b) => $b->where('sector', $v)))
            ->when($request->district, fn ($q, $v) => $q->whereHas('application.business', fn ($b) => $b->where('district', $v)))
            ->when($request->risk, fn ($q, $v) => $q->where('risk_category', $v))
            ->when($request->min_amount, fn ($q, $v) => $q->where('funding_target', '>=', (int) $v * 100))
            ->when($request->max_amount, fn ($q, $v) => $q->where('funding_target', '<=', (int) $v * 100))
            ->when($request->max_term, fn ($q, $v) => $q->where('term_months', '<=', (int) $v))
            ->when($request->min_rate, fn ($q, $v) => $q->where('rate_bp', '>=', (int) round($v * 100)))
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('title', 'like', "%{$s}%")->orWhere('summary', 'like', "%{$s}%")));
        $q = match ($request->sort) {
            'rate' => $q->orderByDesc('rate_bp'), 'deadline' => $q->orderBy('deadline'), 'amount' => $q->orderBy('funding_target'), default => $q->latest('published_at'),
        };
        $saved = WatchlistItem::where('user_id', $request->user()->id)->pluck('listing_id')->all();

        return Inertia::render('marketplace/Index', [
            'listings' => $q->paginate(9)->withQueryString()->through(fn ($l) => $this->listings->publicView($l) + ['saved' => in_array($l->id, $saved)]),
            'filters' => $request->only('sector', 'district', 'risk', 'min_amount', 'max_amount', 'max_term', 'min_rate', 'search', 'sort'),
            'sectors' => config('banyira.sectors'), 'districts' => config('banyira.districts'),
        ]);
    }

    public function show(Request $request, Listing $listing)
    {
        abort_unless(in_array($listing->status, ['published', 'funded']), 404);
        $p = $request->user()->investorProfile;

        return Inertia::render('marketplace/Show', [
            'listing' => $this->listings->publicView($listing) + ['saved' => WatchlistItem::where('user_id', $request->user()->id)->where('listing_id', $listing->id)->exists()],
            'investor' => ['kyc_status' => $p?->kyc_status, 'risk_acknowledged' => (bool) $p?->risk_acknowledged_at],
            'conversation_id' => \App\Models\Conversation::where('listing_id', $listing->id)->where('investor_id', $request->user()->id)->value('id'),
        ]);
    }

    public function watchlist(Request $request)
    {
        $ids = WatchlistItem::where('user_id', $request->user()->id)->pluck('listing_id');

        return Inertia::render('marketplace/Watchlist', [
            'listings' => Listing::whereIn('id', $ids)->with('application.business')->get()->map(fn ($l) => $this->listings->publicView($l) + ['saved' => true]),
        ]);
    }

    public function toggleWatch(Request $request, Listing $listing)
    {
        abort_unless($listing->status === 'published', 404);
        $w = WatchlistItem::where('user_id', $request->user()->id)->where('listing_id', $listing->id);
        $w->exists() ? $w->delete() : WatchlistItem::create(['user_id' => $request->user()->id, 'listing_id' => $listing->id]);

        return back();
    }

    public function contact(Request $request, Listing $listing, MessagingService $messaging)
    {
        $c = $messaging->start($listing, $request->user());

        return to_route('conversations.show', $c);
    }
}
