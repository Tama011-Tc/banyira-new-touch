<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * Demonstration driver. It never moves money: payments stay pending until a
 * finance officer confirms them through reconciliation.
 */
class DemoGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'demo';
    }

    public function requestCollection(Payment $payment): array
    {
        return [
            'instructions' => "Demonstration only. No money will move. Reference {$payment->reference}. A finance officer confirms payments during reconciliation.",
            'provider_reference' => null,
        ];
    }

    public function parseWebhook(Request $request): ?array
    {
        return null;
    }
}
