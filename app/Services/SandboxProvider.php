<?php

namespace App\Services;

use App\Models\SandboxTransaction;
use Illuminate\Support\Str;

/**
 * A clearly simulated payment provider with its own books (sandbox_transactions). It stands in for a real gateway:
 * the platform never trusts a browser redirect or webhook body; it asks the provider (verify) before crediting anything.
 */
class SandboxProvider
{
    public const NAME = 'sandbox';

    public function create(int $amount): string
    {
        $ref = 'SBX-'.strtoupper(Str::random(14));
        SandboxTransaction::create(['ref' => $ref, 'amount' => $amount, 'status' => 'pending']);

        return $ref;
    }

    /** What a real provider's "retrieve payment" API would return. */
    public function verify(string $ref): ?array
    {
        $t = SandboxTransaction::where('ref', $ref)->first();

        return $t ? ['status' => $t->status, 'amount' => (int) $t->amount] : null;
    }

    /** The "customer approves on their phone" step of the sandbox. Returns the signed webhook the provider would send. */
    public function settle(string $ref, bool $succeed = true): array
    {
        $t = SandboxTransaction::where('ref', $ref)->firstOrFail();
        if ($t->status === 'pending') {
            $t->forceFill(['status' => $succeed ? 'succeeded' : 'failed'])->save();
        }
        $payload = json_encode(['event_id' => 'evt_'.Str::uuid(), 'type' => $succeed ? 'payment.succeeded' : 'payment.failed', 'ref' => $ref, 'amount' => (int) $t->amount]);

        return ['body' => $payload, 'signature' => self::sign($payload)];
    }

    public static function secret(): string
    {
        return config('banyira.sandbox_webhook_secret') ?: hash('sha256', 'banyira-sandbox|'.config('app.key'));
    }

    public static function sign(string $body): string
    {
        return hash_hmac('sha256', $body, self::secret());
    }

    public static function validSignature(string $body, ?string $signature): bool
    {
        return $signature !== null && hash_equals(self::sign($body), $signature);
    }
}
