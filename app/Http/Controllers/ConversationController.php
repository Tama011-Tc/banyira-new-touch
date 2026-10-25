<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\MessagingService;
use App\Services\OfferService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConversationController extends Controller
{
    public function __construct(private readonly MessagingService $messaging, private readonly OfferService $offers) {}

    private function mine(Request $r)
    {
        $u = $r->user();

        return Conversation::query()->where(fn ($q) => $q->where('investor_id', $u->id)->orWhereIn('business_id', \App\Models\Business::where('owner_id', $u->id)->select('id')));
    }

    public function index(Request $request)
    {
        $list = $this->mine($request)->with('listing.application.business', 'investor')->latest('updated_at')->get();

        return Inertia::render('messages/Index', ['conversations' => $list->map(fn ($c) => [
            'id' => $c->id, 'title' => $c->listing->title, 'with' => $request->user()->id === $c->investor_id ? $c->listing->application->business->display_name : $c->investor->name,
            'last' => optional($this->messaging->visibleTo($c, $request->user())->last())->body,
        ])]);
    }

    public function show(Request $request, int $conversation)
    {
        $c = $this->mine($request)->with('listing.application.business', 'offer.versions', 'investor')->findOrFail($conversation);
        $me = $request->user();
        $isInvestor = $me->id === $c->investor_id;
        $msgs = $this->messaging->visibleTo($c, $me);
        $msgs->where('sender_id', '!=', $me->id)->whereNull('read_at')->each(fn ($m) => $m->forceFill(['read_at' => now()])->save());
        $versions = $c->offer?->versions ?? collect();
        $latest = $versions->sortByDesc('version')->first();

        return Inertia::render('messages/Show', [
            'conversation' => ['id' => $c->id, 'title' => $c->listing->title, 'listing_id' => $c->listing_id, 'with' => $isInvestor ? $c->listing->application->business->display_name : $c->investor->name, 'role' => $isInvestor ? 'investor' : 'business'],
            'messages' => $msgs->map(fn ($m) => ['id' => $m->id, 'mine' => $m->sender_id === $me->id, 'body' => $m->sender_id === $me->id || in_array($m->status, ['delivered', 'released']) ? $m->body : '', 'status' => $m->status, 'at' => $m->created_at->format('d M H:i'), 'flags' => $m->sender_id === $me->id ? collect($m->flags)->pluck('rule') : []]),
            'offer' => [
                'versions' => $versions->sortBy('version')->values()->map(fn ($v) => ['id' => $v->id, 'version' => $v->version, 'status' => $v->status, 'principal' => $v->principal, 'rate_bp' => $v->rate_bp, 'term_months' => $v->term_months, 'frequency' => $v->frequency, 'interest_method' => $v->interest_method, 'grace' => $v->grace_instalments, 'fees' => $v->fees, 'by_me' => $v->proposed_by === $me->id, 'expires_at' => $v->expires_at?->format('d M H:i'), 'investor_accepted' => (bool) $v->investor_accepted_at, 'business_accepted' => (bool) $v->business_accepted_at, 'note' => $v->note, 'hash' => substr($v->content_hash, 0, 10)]),
                'latest_id' => $latest?->id, 'status' => $c->offer?->status, 'agreement_id' => \App\Models\LoanAgreement::where('offer_version_id', $latest?->id)->value('id'),
            ],
            'limits' => ['min' => $c->listing->min_investment, 'max' => $c->listing->max_investment, 'max_rate_bp' => $c->listing->max_rate_bp, 'term_months' => $c->listing->term_months, 'rate_bp' => $c->listing->rate_bp, 'frequency' => $c->listing->frequency, 'method' => $c->listing->interest_method],
            'rules' => 'Keep the conversation on the platform. Phone numbers, emails, links, social handles and payment instructions are blocked, and requests to move outside the platform are reviewed.',
        ]);
    }

    public function send(Request $request, int $conversation)
    {
        $c = $this->mine($request)->findOrFail($conversation);
        $r = $this->messaging->send($c, $request->user(), (string) $request->input('body'));
        $c->touch();
        $msg = match ($r['outcome']) {
            'blocked' => 'Message not sent: it appears to contain contact details or an off-platform payment request. If this is a mistake, appeal it.',
            'pending_review' => 'Your message is being reviewed by our team before it is delivered.',
            default => null,
        };

        return back()->with($msg ? 'error' : 'success', $msg ?? 'Sent.');
    }

    public function appeal(Request $request, Message $message)
    {
        $this->messaging->appeal($message, $request->user(), (string) $request->input('note'));

        return back()->with('success', 'Appeal sent to moderators.');
    }

    public function report(Request $request, int $conversation)
    {
        $c = $this->mine($request)->findOrFail($conversation);
        $d = $request->validate(['reason' => 'required|in:abuse,off_platform,suspicious,fraud,other', 'detail' => 'nullable|string|max:1000', 'message_id' => 'nullable|integer']);
        $this->messaging->report($c, $request->user(), $d['reason'], $d['detail'] ?? null, $d['message_id'] ?? null);

        return back()->with('success', 'Thank you. Our team will look into this.');
    }

    public function attach(Request $request, int $conversation)
    {
        $c = $this->mine($request)->findOrFail($conversation);
        $request->validate(['file' => 'required|file|max:2048|mimes:pdf,jpg,jpeg,png']);
        $path = $request->file('file')->storeAs("attachments/{$c->id}", bin2hex(random_bytes(12)).'.'.$request->file('file')->getClientOriginalExtension(), 'local');
        $m = Message::create(['conversation_id' => $c->id, 'sender_id' => $request->user()->id, 'body' => 'Attachment: '.basename($request->file('file')->getClientOriginalName()), 'status' => 'delivered', 'attachment_path' => $path]);

        return back()->with('success', 'Attachment sent.');
    }

    public function attachment(Request $request, Message $message)
    {
        $c = $this->mine($request)->findOrFail($message->conversation_id);
        abort_unless($message->attachment_path && in_array($message->status, ['delivered', 'released']), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->download($message->attachment_path);
    }

    public function propose(Request $request, int $conversation)
    {
        $c = $this->mine($request)->findOrFail($conversation);
        $d = $request->validate(['principal' => ['required', \App\Support\Money::DECIMAL_RULE], 'rate' => 'required|numeric|min:0|max:100', 'term_months' => 'required|integer|min:1|max:120', 'frequency' => 'required|in:weekly,monthly', 'interest_method' => 'required|in:flat,reducing', 'grace_instalments' => 'nullable|integer|min:0|max:12', 'note' => 'nullable|string|max:300']);
        if (\App\Support\MessageModerator::inspect((string) ($d['note'] ?? ''))['action'] === 'block') {
            return back()->withErrors(['note' => 'The note cannot contain contact details, links or payment instructions.']);
        }
        $this->offers->propose($c, $request->user(), ['principal' => \App\Support\Money::toMinor($d['principal']), 'rate_bp' => (int) round($d['rate'] * 100), 'term_months' => (int) $d['term_months'], 'frequency' => $d['frequency'], 'interest_method' => $d['interest_method'], 'grace_instalments' => (int) ($d['grace_instalments'] ?? 0), 'note' => $d['note'] ?? null]);

        return back()->with('success', 'Proposal sent. The other party must review and accept it.');
    }

    public function accept(Request $request, int $conversation, \App\Models\OfferVersion $version)
    {
        $c = $this->mine($request)->findOrFail($conversation);
        abort_unless($version->offer->conversation_id === $c->id, 404);
        $a = $this->offers->accept($version, $request->user());

        return $a ? to_route('agreements.show', $a)->with('success', 'Both parties accepted. A simulated agreement and repayment schedule have been created.') : back()->with('success', 'Your acceptance is recorded. Waiting for the other party.');
    }

    public function withdraw(Request $request, int $conversation, \App\Models\OfferVersion $version)
    {
        $c = $this->mine($request)->findOrFail($conversation);
        abort_unless($version->offer->conversation_id === $c->id, 404);
        $this->offers->withdraw($version, $request->user());

        return back()->with('success', 'Proposal withdrawn.');
    }

    public function decline(Request $request, int $conversation, \App\Models\OfferVersion $version)
    {
        $c = $this->mine($request)->findOrFail($conversation);
        abort_unless($version->offer->conversation_id === $c->id, 404);
        $this->offers->decline($version, $request->user());

        return back()->with('success', 'Proposal declined.');
    }
}
