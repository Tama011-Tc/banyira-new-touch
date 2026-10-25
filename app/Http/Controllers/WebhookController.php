<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    /** Signature-checked, idempotent. Responds 200 to replays so the provider stops retrying; 401 on a bad signature. */
    public function sandbox(Request $request, PaymentService $payments)
    {
        $outcome = $payments->handleWebhook($request->getContent(), $request->header('X-Signature'));

        return response()->json(['outcome' => $outcome], $outcome === 'rejected' ? 401 : 200);
    }
}
