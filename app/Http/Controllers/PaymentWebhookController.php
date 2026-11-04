<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Services\AuditLogger;
use App\Services\PaymentService;
use Illuminate\Http\Request;

/** Provider callbacks. Only signed callbacks are processed; repeats are harmless. */
class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGateway $gateway, PaymentService $payments, AuditLogger $audit)
    {
        $data = $gateway->parseWebhook($request);
        if (! $data) {
            $audit->log('payments', 'Rejected unsigned or invalid payment callback', null, ['ip' => $request->ip()]);

            return response()->json(['ok' => false], 400);
        }
        $payment = $payments->handleWebhook($data);

        return response()->json(['ok' => (bool) $payment]);
    }
}
