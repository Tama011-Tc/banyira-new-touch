<?php

namespace App\Http\Controllers;

use App\Models\SmsMessage;
use App\Models\User;
use App\Services\Sms\SmsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SmsController extends Controller
{
    /** The user's own simulated phone: what a real handset would have received. */
    public function inbox(Request $request, SmsService $sms)
    {
        return Inertia::render('sms/Inbox', [
            'phone' => $sms->phoneFor($request->user()),
            'messages' => SmsMessage::where('user_id', $request->user()->id)->latest('id')->limit(50)->get()->map(fn ($m) => ['id' => $m->id, 'body' => $m->body, 'kind' => $m->kind, 'at' => $m->created_at->format('d M H:i'), 'simulated' => $m->simulated]),
        ]);
    }

    /**
     * Demo only: type an SMS reply "from" your own phone. It is signed and delivered exactly like a gateway callback would be, so
     * the same checks (sender number, code, expiry, single use) apply.
     */
    public function simulateReply(Request $request, SmsService $sms, \App\Services\Sms\SmsInboundService $inbound)
    {
        $d = $request->validate(['text' => 'required|string|max:60']);
        $phone = $sms->phoneFor($request->user());
        abort_unless($phone, 422, 'There is no valid mobile number on your profile.');
        $outcome = $inbound->handle('sim-'.bin2hex(random_bytes(8)), $phone, $d['text']);

        return back()->with($outcome === 'accepted' || $outcome === 'declined' ? 'success' : 'error', "Simulated reply processed: {$outcome}.");
    }

    /** Staff view of what was sent. One-time codes are masked: staff never see them. */
    public function log(Request $request)
    {
        return Inertia::render('sms/Log', ['messages' => SmsMessage::latest('id')->limit(100)->get()->map(fn ($m) => [
            'id' => $m->id, 'to' => substr($m->to_phone, 0, 7).'••••'.substr($m->to_phone, -2), 'user' => User::whereKey($m->user_id)->value('name'), 'kind' => $m->kind,
            'body' => preg_replace('/\b\d{6}\b/', '••••••', $m->body), 'provider' => $m->provider, 'simulated' => $m->simulated, 'status' => $m->status, 'at' => $m->created_at->format('d M H:i'),
        ])]);
    }
}
