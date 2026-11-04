<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\LoanApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SalesController extends Controller
{
    public function overview()
    {
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->format('Y-m'));
        $reg = User::whereIn('type', ['business', 'investor'])->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') m, type, COUNT(*) n")->groupBy('m', 'type')->get();

        return Inertia::render('Staff/Sales/Overview', [
            'stats' => [
                'businesses' => User::where('type', 'business')->count(),
                'investors' => User::where('type', 'investor')->count(),
                'verifiedInvestors' => DB::table('investor_profiles')->where('verification_status', 'verified')->count(),
                'applications' => LoanApplication::where('status', '!=', 'draft')->count(),
                'listings' => Listing::where('status', 'open')->count(),
                'openLeads' => Lead::whereNotIn('stage', ['converted', 'lost'])->count(),
                'conversion' => ($t = Lead::count()) ? round(Lead::where('stage', 'converted')->count() / $t * 100, 1) : 0,
            ],
            'registrations' => $months->map(fn ($m) => ['l' => date('M', strtotime($m.'-01')), 'v' => (int) $reg->where('m', $m)->where('type', 'business')->sum('n'), 'v2' => (int) $reg->where('m', $m)->where('type', 'investor')->sum('n')]),
            'funnel' => collect(['new', 'contacted', 'qualified', 'onboarding', 'converted', 'lost'])->map(fn ($s) => ['l' => ucfirst($s), 'v' => Lead::where('stage', $s)->count()]),
            'sources' => Lead::selectRaw('source l, COUNT(*) v')->groupBy('source')->orderByDesc('v')->get(),
        ]);
    }

    public function leads(Request $request)
    {
        $stage = $request->query('stage');

        return Inertia::render('Staff/Sales/Leads', [
            'leads' => Lead::with('owner:id,name', 'activities.user:id,name')->when($stage, fn ($q) => $q->where('stage', $stage))
                ->orderByRaw('next_follow_up IS NULL, next_follow_up')->paginate(20)->withQueryString()
                ->through(fn ($l) => [...$l->only(['id', 'name', 'type', 'email', 'phone', 'source', 'stage', 'message', 'next_follow_up', 'created_at']), 'owner' => $l->owner?->name,
                    'activities' => $l->activities->take(5)->map(fn ($a) => ['kind' => $a->kind, 'note' => $a->note, 'by' => $a->user->name, 'at' => $a->created_at])]),
            'stage' => $stage,
            'campaigns' => Campaign::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeLead(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'type' => ['required', 'in:business,investor,other'], 'phone' => ['nullable', 'string', 'max:20'], 'email' => ['nullable', 'email'], 'source' => ['required', 'string', 'max:60'], 'next_follow_up' => ['nullable', 'date'], 'campaign_id' => ['nullable', 'exists:campaigns,id']]);
        Lead::create([...$data, 'stage' => 'new', 'owner_id' => $request->user()->id]);

        return back()->with('success', 'Lead added.');
    }

    public function updateLead(Request $request, Lead $lead)
    {
        $data = $request->validate(['stage' => ['required', 'in:new,contacted,qualified,onboarding,converted,lost'], 'next_follow_up' => ['nullable', 'date'], 'note' => ['nullable', 'string', 'max:300']]);
        DB::transaction(function () use ($lead, $data, $request) {
            if ($lead->stage !== $data['stage']) {
                $lead->activities()->create(['user_id' => $request->user()->id, 'kind' => 'stage', 'note' => 'Moved to '.$data['stage']]);
            }
            if (! empty($data['note'])) {
                $lead->activities()->create(['user_id' => $request->user()->id, 'kind' => 'note', 'note' => $data['note']]);
            }
            $lead->update(['stage' => $data['stage'], 'next_follow_up' => $data['next_follow_up'] ?? null, 'owner_id' => $lead->owner_id ?? $request->user()->id]);
        });

        return back()->with('success', 'Lead updated.');
    }

    public function campaigns()
    {
        return Inertia::render('Staff/Sales/Campaigns', [
            'campaigns' => Campaign::latest('starts_on')->get()->map(fn ($c) => [...$c->only(['id', 'name', 'channel', 'audience', 'status', 'starts_on', 'ends_on', 'reach', 'budget']), 'leads' => Lead::where('campaign_id', $c->id)->count(), 'converted' => Lead::where('campaign_id', $c->id)->where('stage', 'converted')->count()]),
        ]);
    }

    public function storeCampaign(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'channel' => ['required', 'string', 'max:60'], 'audience' => ['required', 'in:businesses,investors,both'], 'starts_on' => ['required', 'date'], 'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'], 'budget' => ['required', 'numeric', 'min:0']]);
        Campaign::create([...$data, 'status' => now()->lt($data['starts_on']) ? 'scheduled' : 'running', 'created_by' => $request->user()->id]);

        return back()->with('success', 'Campaign created.');
    }

    public function updateCampaign(Request $request, Campaign $campaign)
    {
        $campaign->update($request->validate(['status' => ['required', 'in:draft,scheduled,running,complete'], 'reach' => ['required', 'integer', 'min:0']]));

        return back()->with('success', 'Campaign updated.');
    }
}
