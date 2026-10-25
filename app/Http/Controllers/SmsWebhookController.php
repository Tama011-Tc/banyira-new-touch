<?php

namespace App\Http\Controllers;

use App\Services\Sms\SmsInboundService;
use App\Services\Sms\SmsService;
use Illuminate\Http\Request;

class SmsWebhookController extends Controller
{
    /** Signed callback from the SMS gateway: {"event_id","from","text"}. 401 on a bad signature; otherwise 200 whatever the outcome. */
    public function inbound(Request $request, SmsInboundService $inbound)
    {
        if (! SmsService::validSignature($request->getContent(), $request->header('X-Signature'))) {
            return response()->json(['outcome' => 'rejected'], 401);
        }
        $d = json_decode($request->getContent(), true) ?: [];
        if (! is_string($d['event_id'] ?? null) || ! is_string($d['from'] ?? null) || ! is_string($d['text'] ?? null)) {
            return response()->json(['outcome' => 'rejected'], 422);
        }

        return response()->json(['outcome' => $inbound->handle($d['event_id'], $d['from'], $d['text'])]);
    }
}
