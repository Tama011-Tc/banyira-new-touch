<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Listing;
use App\Services\ListingService;
use App\Support\Money;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ListingAdminController extends Controller
{
    public function __construct(private readonly ListingService $listings) {}

    public function index()
    {
        return Inertia::render('listings/Index', [
            'approved' => Application::with('business')->where('status', 'approved')->latest('id')->get()->map(fn ($a) => ['id' => $a->id, 'business' => $a->business->display_name, 'amount' => $a->amount, 'sector' => $a->business->sector]),
            'listings' => Listing::with('application.business')->latest('id')->get()->map(fn ($l) => ['id' => $l->id, 'title' => $l->title, 'business' => $l->application->business->display_name, 'status' => $l->status, 'target' => $l->funding_target, 'committed' => $this->listings->committed($l), 'risk' => $l->risk_category, 'deadline' => $l->deadline->toDateString()]),
        ]);
    }

    public function prepare(Application $application)
    {
        abort_unless($application->status === 'approved', 404);

        return Inertia::render('listings/Prepare', ['application' => ['id' => $application->id, 'business' => $application->business->display_name, 'amount' => $application->amount, 'sector' => $application->business->sector, 'district' => $application->business->district, 'purpose' => $application->purpose]]);
    }

    public function store(Request $request, Application $application)
    {
        $d = $request->validate(['title' => 'required|string|max:120', 'summary' => 'required|string|min:40|max:1500', 'risk_disclosure' => 'required|string|min:40|max:1500', 'min_investment' => ['required', Money::DECIMAL_RULE], 'max_investment' => ['required', Money::DECIMAL_RULE], 'deadline' => 'required|date|after:today']);
        $d['min_investment'] = Money::toMinor($d['min_investment']);
        $d['max_investment'] = Money::toMinor($d['max_investment']);
        $this->listings->prepare($application, $request->user(), $d);

        return to_route('listings.index')->with('success', 'Listing drafted. A second person must review it before it can be published.');
    }

    public function review(Request $request, Listing $listing)
    {
        $this->listings->review($listing, $request->user());

        return back()->with('success', 'Reviewed.');
    }

    public function publish(Request $request, Listing $listing)
    {
        $this->listings->publish($listing, $request->user());

        return back()->with('success', 'The listing is now visible to investors.');
    }
}
