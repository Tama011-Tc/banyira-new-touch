<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\SalesActivity;
use App\Models\User;
use App\Notifications\OwnerInvitation;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * Sales brings businesses in and helps them apply. A rep sees only the leads they created, can prepare and upload an
 * application on the owner's behalf (with recorded consent), and gives the owner a secure link to set their OWN password
 * so the owner can log in and follow progress. A rep can never assess, approve or promise funding.
 */
class SalesController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    private function lead(Request $r, int $id): User
    {
        return User::where('invited_by', $r->user()->id)->where('role', Role::BusinessOwner->value)->findOrFail($id);
    }

    private function newOwner(Request $r, string $name, string $email): User
    {
        // The password is random and nobody knows it. The owner chooses their own through the invitation link.
        $owner = new User(['name' => $name, 'email' => strtolower($email), 'password' => Str::random(40)]);
        $owner->forceFill(['role' => Role::BusinessOwner, 'status' => 'active', 'invited_by' => $r->user()->id])->save();

        return $owner;
    }

    /** A single-use, expiring link that lets the owner set a password. Also emailed (log driver in development). */
    private function invite(User $owner, User $rep): string
    {
        $link = route('password.reset', ['token' => Password::broker()->createToken($owner), 'email' => $owner->email]);
        try {
            $owner->notify(new OwnerInvitation($link, $rep->name));
        } catch (\Throwable $e) {
            report($e);   // mail not configured: the rep still gets the link to pass on
        }
        $this->audit->record('sales.owner_invited', $owner, [], $rep);

        return $link;
    }

    private function status(User $lead): string
    {
        $b = $lead->business;
        if (! $b) {
            return AuditLog::where('action', 'auth.login')->where('actor_id', $lead->id)->exists() ? 'owner signed in' : 'invited';
        }

        return $b->applications()->latest('id')->value('status') ?? 'profile added';
    }

    public function index(Request $request)
    {
        $leads = User::where('invited_by', $request->user()->id)->where('role', Role::BusinessOwner->value)->with('business')->latest('id')->get();
        $me = $request->user()->id;

        return Inertia::render('sales/Index', [
            'leads' => $leads->map(fn ($l) => [
                'id' => $l->id, 'owner' => $l->name, 'email' => $l->email, 'business' => $l->business?->display_name, 'sector' => $l->business?->sector, 'district' => $l->business?->district,
                'applications' => $l->business ? $l->business->applications()->count() : 0, 'status' => $this->status($l),
                'signed_in' => AuditLog::where('action', 'auth.login')->where('actor_id', $l->id)->exists(),
                'next_follow_up' => SalesActivity::where('lead_id', $l->id)->where('staff_id', $me)->whereNull('done_at')->whereNotNull('due_on')->orderBy('due_on')->value('due_on')?->toDateString(),
            ]),
            'follow_ups' => SalesActivity::where('staff_id', $me)->whereNull('done_at')->whereNotNull('due_on')->orderBy('due_on')->limit(8)->get()->map(fn ($a) => ['id' => $a->id, 'lead_id' => $a->lead_id, 'lead' => User::whereKey($a->lead_id)->value('name'), 'kind' => $a->kind, 'body' => $a->body, 'due_on' => $a->due_on->toDateString(), 'overdue' => $a->due_on->isPast() && ! $a->due_on->isToday()]),
        ]);
    }

    public function create(Request $request)
    {
        $lead = $request->integer('lead') ? $this->lead($request, $request->integer('lead')) : null;

        return Inertia::render('sales/Create', ['sectors' => config('banyira.sectors'), 'districts' => config('banyira.districts'), 'lead' => $lead ? ['id' => $lead->id, 'name' => $lead->name, 'email' => $lead->email] : null]);
    }

    /** Register a business (and its owner's account) on the owner's behalf, with their consent. */
    public function store(Request $request)
    {
        $d = $request->validate(BusinessController::rules() + ['consent' => 'accepted', 'owner_name' => 'required|string|max:120', 'owner_email' => 'required|email|max:255|unique:users,email']);
        $owner = $this->newOwner($request, $d['owner_name'], $d['owner_email']);
        $b = Business::create(collect($d)->except(['consent', 'owner_name', 'owner_email'])->all() + ['owner_id' => $owner->id, 'registered_by_staff_id' => $request->user()->id, 'owner_consent_at' => now()]);
        $this->audit->record('business.registered_by_staff', $b, ['owner_consent' => true]);
        $link = $this->invite($owner, $request->user());

        return to_route('applications.create', ['business' => $b->id])->with('success', 'Business registered with the owner’s consent. Now prepare the application.')->with('invite_link', $link);
    }

    /** Just invite an owner to do it themselves. */
    public function inviteOnly(Request $request)
    {
        $d = $request->validate(['owner_name' => 'required|string|max:120', 'owner_email' => 'required|email|max:255|unique:users,email']);
        $owner = $this->newOwner($request, $d['owner_name'], $d['owner_email']);

        return to_route('sales.lead', $owner->id)->with('success', 'Invitation created.')->with('invite_link', $this->invite($owner, $request->user()));
    }

    public function reinvite(Request $request, int $lead)
    {
        $owner = $this->lead($request, $lead);
        if (AuditLog::where('action', 'auth.login')->where('actor_id', $owner->id)->exists()) {
            return back()->with('error', 'This owner has already signed in. They can use “Forgot password” if they need a reset.');
        }

        return back()->with('success', 'New invitation link created. The old one stops working.')->with('invite_link', $this->invite($owner, $request->user()));
    }

    public function show(Request $request, int $lead)
    {
        $owner = $this->lead($request, $lead)->load('business');
        $b = $owner->business;

        return Inertia::render('sales/Lead', [
            'lead' => ['id' => $owner->id, 'name' => $owner->name, 'email' => $owner->email, 'status' => $this->status($owner), 'signed_in' => AuditLog::where('action', 'auth.login')->where('actor_id', $owner->id)->exists()],
            'business' => $b ? ['id' => $b->id, 'display_name' => $b->display_name, 'sector' => $b->sector, 'district' => $b->district, 'registered_by_me' => $b->registered_by_staff_id === $request->user()->id] : null,
            'applications' => $b ? $b->applications()->latest('id')->get()->map(fn ($a) => ['id' => $a->id, 'status' => $a->status, 'amount' => $a->amount, 'term_months' => $a->term_months, 'submitted_at' => $a->submitted_at?->toDateString(), 'assisted' => $a->assisted_by === $request->user()->id])->all() : [],
            'activities' => SalesActivity::where('lead_id', $owner->id)->where('staff_id', $request->user()->id)->orderByRaw('done_at is null desc')->orderBy('due_on')->latest('id')->get()->map(fn ($a) => ['id' => $a->id, 'kind' => $a->kind, 'body' => $a->body, 'due_on' => $a->due_on?->toDateString(), 'done' => (bool) $a->done_at, 'at' => $a->created_at->format('d M Y')]),
        ]);
    }

    /** Add the business profile to a lead who was only invited. Needs the owner's consent. */
    public function addBusiness(Request $request, int $lead)
    {
        $owner = $this->lead($request, $lead);
        abort_if($owner->business()->exists(), 422, 'This owner already has a business profile.');
        $d = $request->validate(BusinessController::rules() + ['consent' => 'accepted']);
        $b = Business::create(collect($d)->except('consent')->all() + ['owner_id' => $owner->id, 'registered_by_staff_id' => $request->user()->id, 'owner_consent_at' => now()]);
        $this->audit->record('business.registered_by_staff', $b, ['owner_consent' => true, 'lead' => $owner->id]);

        return to_route('applications.create', ['business' => $b->id])->with('success', 'Business profile added. Now prepare the application.');
    }

    public function activity(Request $request, int $lead)
    {
        $owner = $this->lead($request, $lead);
        $d = $request->validate(['kind' => 'required|in:note,call,meeting,onboarding_session,follow_up', 'body' => 'required|string|min:3|max:1000', 'due_on' => 'nullable|date|after_or_equal:today']);
        SalesActivity::create($d + ['lead_id' => $owner->id, 'staff_id' => $request->user()->id]);

        return back()->with('success', 'Recorded.');
    }

    public function completeActivity(Request $request, SalesActivity $activity)
    {
        abort_unless($activity->staff_id === $request->user()->id, 404);
        $activity->forceFill(['done_at' => now()])->save();

        return back();
    }

    /** Referral = hand a complete submitted application to the credit team. No status power beyond that. */
    public function refer(Request $request, Application $application)
    {
        abort_unless(Business::where('id', $application->business_id)->where('registered_by_staff_id', $request->user()->id)->exists(), 404);
        abort_unless($application->status === 'submitted', 422);
        $application->forceFill(['referred_by' => $request->user()->id])->save();
        $this->audit->record('application.referred', $application);

        return back()->with('success', 'Referred to the credit team.');
    }
}
