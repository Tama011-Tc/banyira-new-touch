<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Message;
use App\Models\MessageModerationEvent;
use App\Models\MessageReport;
use App\Models\User;
use App\Support\MessageModerator;
use Illuminate\Validation\ValidationException;

class MessagingService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function enabledRules(): ?array
    {
        return Settings::get('moderation.rules');
    }

    public function start(Listing $listing, User $investor): Conversation
    {
        $this->assertEligibleInvestor($investor);
        if ($listing->status !== 'published') {
            throw ValidationException::withMessages(['listing' => 'This opportunity is not open for enquiries.']);
        }
        $businessId = $listing->application->business_id;

        return Conversation::firstOrCreate(['listing_id' => $listing->id, 'investor_id' => $investor->id], ['business_id' => $businessId]);
    }

    public function assertEligibleInvestor(User $investor): void
    {
        $p = $investor->investorProfile;
        if (! $p || $p->kyc_status !== 'verified') {
            throw ValidationException::withMessages(['kyc' => 'Complete identity verification before contacting businesses or investing.']);
        }
        if (! $p->risk_acknowledged_at) {
            throw ValidationException::withMessages(['risk' => 'Please read and accept the risk statement first.']);
        }
        if ($investor->status !== 'active') {
            abort(403);
        }
    }

    public function isParticipant(Conversation $c, User $user): bool
    {
        return $c->investor_id === $user->id || $c->business->owner_id === $user->id;
    }

    /** @return array{message: Message, outcome: string} */
    public function send(Conversation $c, User $sender, string $body): array
    {
        abort_unless($this->isParticipant($c, $sender), 403);
        $body = trim($body);
        if ($body === '' || mb_strlen($body) > 2000) {
            throw ValidationException::withMessages(['body' => 'Write a message of up to 2,000 characters.']);
        }
        $r = MessageModerator::inspect($body, $this->enabledRules());
        $status = match ($r['action']) {MessageModerator::BLOCK => 'blocked', MessageModerator::REVIEW => 'pending_review', default => 'delivered'};
        $m = Message::create(['conversation_id' => $c->id, 'sender_id' => $sender->id, 'body' => $body, 'status' => $status, 'flags' => $r['matches'] ?: null]);
        if ($status !== 'delivered') {
            MessageModerationEvent::create(['message_id' => $m->id, 'conversation_id' => $c->id, 'actor_id' => null, 'kind' => $status === 'blocked' ? 'auto_block' : 'auto_review', 'detail' => $r['matches'], 'created_at' => now()]);
        }

        return ['message' => $m, 'outcome' => $status];
    }

    /** Messages a given viewer may see: all delivered/released ones, plus their own held messages. */
    public function visibleTo(Conversation $c, User $viewer)
    {
        return $c->messages()->where(fn ($q) => $q->whereIn('status', ['delivered', 'released'])->orWhere('sender_id', $viewer->id))->orderBy('id')->get();
    }

    /** The sender disputes an automatic block; a human decides. */
    public function appeal(Message $m, User $sender, string $note): void
    {
        abort_unless($m->sender_id === $sender->id, 403);
        if ($m->status !== 'blocked') {
            throw ValidationException::withMessages(['message' => 'Only blocked messages can be appealed.']);
        }
        $m->forceFill(['status' => 'pending_review'])->save();
        MessageModerationEvent::create(['message_id' => $m->id, 'conversation_id' => $m->conversation_id, 'actor_id' => $sender->id, 'kind' => 'appeal', 'detail' => ['note' => mb_substr($note, 0, 500)], 'created_at' => now()]);
    }

    public function resolve(Message $m, User $moderator, string $outcome, ?string $note = null): void
    {
        abort_unless($moderator->role->can('moderation.review'), 403);
        if (! in_array($m->status, ['pending_review', 'blocked'], true) || ! in_array($outcome, ['release', 'uphold'], true)) {
            throw ValidationException::withMessages(['message' => 'Nothing to resolve.']);
        }
        $m->forceFill(['status' => $outcome === 'release' ? 'released' : 'upheld'])->save();
        MessageModerationEvent::create(['message_id' => $m->id, 'conversation_id' => $m->conversation_id, 'actor_id' => $moderator->id, 'kind' => $outcome, 'detail' => ['note' => $note], 'created_at' => now()]);
        $this->audit->record("moderation.{$outcome}", $m, [], $moderator);
    }

    public function report(Conversation $c, User $reporter, string $reason, ?string $detail = null, ?int $messageId = null): MessageReport
    {
        abort_unless($this->isParticipant($c, $reporter), 403);
        $r = MessageReport::create(['conversation_id' => $c->id, 'message_id' => $messageId, 'reporter_id' => $reporter->id, 'reason' => $reason, 'detail' => $detail]);
        MessageModerationEvent::create(['message_id' => $messageId, 'conversation_id' => $c->id, 'actor_id' => $reporter->id, 'kind' => 'report', 'detail' => ['reason' => $reason], 'created_at' => now()]);

        return $r;
    }
}
