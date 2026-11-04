<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Message;
use App\Models\User;
use App\Notifications\Alert;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * In-platform messaging. Phone numbers, email addresses and links are removed
 * from messages between businesses and investors so dealings stay on the
 * platform and personal contact details stay private.
 */
class MessagingService
{
    private const PATTERNS = [
        '/[\w.+-]+@[\w-]+\.[\w.]+/u',
        '/(?:\+?265|0)\s?[1-9](?:[\s-]?\d){7,8}/',
        '/(?:\+?\d[\s-]?){9,}/',
        '/https?:\/\/\S+|www\.\S+/i',
    ];

    public function conversationFor(User $investor, Listing $listing): Conversation
    {
        $owner = $listing->application->business->user;
        $existing = Conversation::where('listing_id', $listing->id)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $investor->id))
            ->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($investor, $listing, $owner) {
            $c = Conversation::create(['subject' => $listing->public_name, 'listing_id' => $listing->id]);
            $c->participants()->createMany([['user_id' => $investor->id], ['user_id' => $owner->id]]);

            return $c;
        });
    }

    public function send(Conversation $c, User $from, string $body, ?UploadedFile $file = null): Message
    {
        if (! $c->participants()->where('user_id', $from->id)->exists()) {
            throw ValidationException::withMessages(['body' => 'You are not part of this conversation.']);
        }
        $clean = preg_replace(self::PATTERNS, '[contact details removed]', $body);

        $message = DB::transaction(function () use ($c, $from, $clean, $body, $file) {
            $m = $c->messages()->create(['user_id' => $from->id, 'body' => $clean, 'redacted' => $clean !== $body]);
            if ($file) {
                $m->attachments()->create([
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $file->store('messages/'.$c->id, config('banyira.documents_disk')),
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
            $c->update(['last_message_at' => now()]);
            $c->participants()->where('user_id', $from->id)->update(['last_read_at' => now()]);

            return $m;
        });

        $c->participants()->where('user_id', '!=', $from->id)->with('user')->get()->each(fn ($p) => $p->user->notify(
            new Alert('New message', "New message about {$c->subject}.", $p->user->type === 'investor' ? '/investor/messages/'.$c->id : '/business/messages/'.$c->id),
        ));

        return $message;
    }

    public function unreadCount(User $user): int
    {
        return Conversation::query()
            ->join('conversation_participants as p', 'p.conversation_id', '=', 'conversations.id')
            ->where('p.user_id', $user->id)
            ->whereNotNull('conversations.last_message_at')
            ->where(fn ($q) => $q->whereNull('p.last_read_at')->orWhereColumn('conversations.last_message_at', '>', 'p.last_read_at'))
            ->count();
    }
}
