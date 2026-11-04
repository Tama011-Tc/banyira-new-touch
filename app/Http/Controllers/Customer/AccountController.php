<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\AgreementParty;
use App\Models\ApplicationDocument;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\MessageAttachment;
use App\Services\MessagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/** Pages shared by business and investor accounts. The area comes from the user type. */
class AccountController extends Controller
{
    public function messages(Request $request, ?Conversation $conversation = null)
    {
        $user = $request->user();
        $list = Conversation::whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->with(['participants.user:id,name,type', 'messages' => fn ($q) => $q->latest('id')->limit(1)])
            ->orderByDesc('last_message_at')->get();

        if ($conversation) {
            $this->authorize('view', $conversation);
            $conversation->participants()->where('user_id', $user->id)->update(['last_read_at' => now()]);
            $conversation->load('messages.attachments', 'messages.user:id,name,type');
        }
        $mine = fn ($c) => $c->participants->firstWhere('user_id', $user->id);

        return Inertia::render('Customer/Messages', [
            'area' => $user->type,
            'conversations' => $list->map(fn ($c) => [
                'id' => $c->id, 'subject' => $c->subject,
                'with' => $this->counterparty($c->participants->firstWhere('user_id', '!=', $user->id)?->user, $user->type),
                'last' => $c->messages->first()?->body, 'at' => $c->last_message_at,
                'unread' => $c->last_message_at && (! $mine($c)?->last_read_at || $c->last_message_at > $mine($c)->last_read_at),
            ]),
            'active' => $conversation ? [
                'id' => $conversation->id, 'subject' => $conversation->subject,
                'messages' => $conversation->messages->map(fn ($m) => [
                    'id' => $m->id, 'mine' => $m->user_id === $user->id, 'from' => $m->user_id === $user->id ? 'You' : $this->counterparty($m->user, $user->type),
                    'body' => $m->body, 'redacted' => $m->redacted, 'at' => $m->created_at,
                    'attachments' => $m->attachments->map->only(['id', 'original_name', 'size']),
                ]),
            ] : null,
        ]);
    }

    public function send(Request $request, Conversation $conversation, MessagingService $messaging)
    {
        $this->authorize('view', $conversation);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000'], 'file' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png']]);
        $m = $messaging->send($conversation, $request->user(), $data['body'], $request->file('file'));

        return back()->with($m->redacted ? 'error' : 'success', $m->redacted ? 'Sent. Contact details were removed: please keep conversations on Banyira.' : 'Message sent.');
    }

    public function attachment(Request $request, MessageAttachment $attachment)
    {
        $this->authorize('view', $attachment->message->conversation()->first());

        return Storage::disk(config('banyira.documents_disk'))->download($attachment->path, $attachment->original_name);
    }

    public function notifications(Request $request)
    {
        return Inertia::render('Customer/Notifications', [
            'area' => $request->user()->type,
            'items' => $request->user()->notifications()->latest()->paginate(20)->through(fn ($n) => ['id' => $n->id, ...$n->data, 'at' => $n->created_at, 'read' => (bool) $n->read_at]),
        ]);
    }

    public function readNotifications(Request $request)
    {
        $request->user()->unreadNotifications()->when($request->input('id'), fn ($q, $id) => $q->whereKey($id))->update(['read_at' => now()]);

        return back();
    }

    public function documents(Request $request)
    {
        $user = $request->user();
        $docs = $user->type === 'business'
            ? ApplicationDocument::whereHas('application', fn ($q) => $q->where('business_profile_id', $user->businessProfile->id))->with('application:id,reference')->latest('id')->get()
                ->map(fn ($d) => ['id' => $d->id, 'name' => $d->original_name, 'type' => $d->type, 'ref' => $d->application->reference, 'status' => $d->status, 'date' => $d->created_at, 'download' => "/{$user->type}/documents/{$d->id}"])
            : collect();
        $agreements = AgreementParty::where('user_id', $user->id)->with('agreement:id,reference,status')->get()
            ->map(fn ($p) => ['id' => 'a'.$p->agreement->id, 'name' => "Agreement {$p->agreement->reference}", 'type' => 'Agreement', 'ref' => $p->agreement->reference, 'status' => $p->signed_at ? 'signed' : 'awaiting signature', 'date' => $p->signed_at, 'download' => null]);

        return Inertia::render('Customer/Documents', ['area' => $user->type, 'documents' => $docs->concat($agreements)->values()]);
    }

    public function download(Request $request, ApplicationDocument $document)
    {
        abort_unless($document->application->business_profile_id === $request->user()->businessProfile?->id, 403);

        return Storage::disk(config('banyira.documents_disk'))->download($document->path, $document->original_name);
    }

    public function settings(Request $request)
    {
        return Inertia::render('Customer/Settings', [
            'area' => $request->user()->type,
            'account' => $request->user()->only(['name', 'email', 'phone', 'email_verified_at', 'phone_verified_at', 'last_login_at']),
            'sessions' => DB::table('sessions')->where('user_id', $request->user()->id)->count(),
        ]);
    }

    public function password(Request $request)
    {
        $data = $request->validate(['current_password' => ['required'], 'password' => ['required', 'confirmed', Password::defaults()]]);
        if (! Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => 'Your current password is not correct.']);
        }
        $request->user()->forceFill(['password' => $data['password'], 'password_changed_at' => now()])->save();
        Auth::guard('web')->logoutOtherDevices($data['password']);

        return back()->with('success', 'Password changed. Other devices have been signed out.');
    }

    public function help(Request $request)
    {
        return Inertia::render('Customer/Help', ['area' => $request->user()->type]);
    }

    public function support(Request $request)
    {
        $data = $request->validate(['topic' => ['required', 'string', 'max:80'], 'message' => ['required', 'string', 'max:2000']]);
        $u = $request->user();
        Lead::create(['name' => $u->name, 'type' => $u->type, 'email' => $u->email, 'phone' => $u->phone, 'source' => 'Support request', 'stage' => 'new', 'message' => "[{$data['topic']}] {$data['message']}"]);

        return back()->with('success', 'Support request sent. We reply within one working day.');
    }

    private function counterparty($other, string $viewerType): string
    {
        if (! $other) {
            return 'Banyira';
        }

        // Investors see the business's listed name; businesses see an investor alias.
        return $other->type === 'investor' && $viewerType === 'business' ? 'Investor '.str_pad((string) ($other->id * 37 % 9000 + 1000), 4, '0') : $other->name;
    }
}
