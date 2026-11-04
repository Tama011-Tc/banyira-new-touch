<?php

namespace App\Contracts;

use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGateway
{
    public function name(): string;

    /**
     * Ask the provider to collect a payment (e.g. a mobile-money push prompt).
     * This never confirms the payment; confirmation arrives by webhook or
     * through reconciliation by the finance team.
     *
     * @return array{instructions:string, provider_reference:?string}
     */
    public function requestCollection(Payment $payment): array;

    /**
     * Validate a provider callback. Returns null when the signature is invalid.
     *
     * @return array{provider_reference:string, idempotency_key:string, status:string, amount:string}|null
     */
    public function parseWebhook(Request $request): ?array;
}
