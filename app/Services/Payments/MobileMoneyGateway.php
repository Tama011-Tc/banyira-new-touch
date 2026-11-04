<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Payment;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Shared base for Malawi mobile-money providers (Airtel Money, TNM Mpamba).
 * The provider collection calls are not implemented: they need merchant
 * credentials, sandbox access and an agreement with each provider.
 * Webhook signature verification (HMAC-SHA256) is in place.
 */
class MobileMoneyGateway implements PaymentGateway
{
    public function __construct(private string $provider, private array $credentials, private ?string $webhookSecret) {}

    public function name(): string
    {
        return $this->provider;
    }

    public function requestCollection(Payment $payment): array
    {
        if (array_filter($this->credentials) === []) {
            throw new RuntimeException("{$this->provider} payments are not configured. Set the credentials in .env.");
        }

        throw new RuntimeException("The {$this->provider} collection API call is not implemented yet.");
    }

    public function parseWebhook(Request $request): ?array
    {
        $signature = (string) $request->header('X-Signature');
        if (! $this->webhookSecret || ! hash_equals(hash_hmac('sha256', $request->getContent(), $this->webhookSecret), $signature)) {
            return null;
        }

        return [
            'provider_reference' => (string) $request->input('transaction_id'),
            'idempotency_key' => (string) $request->input('reference'),
            'status' => $request->input('status') === 'SUCCESS' ? 'confirmed' : 'failed',
            'amount' => (string) $request->input('amount'),
        ];
    }
}
