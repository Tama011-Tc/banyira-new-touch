<?php

namespace App\Services\Identity;

use App\Contracts\IdentityVerifier;
use App\Models\User;

/** Default: every identity check goes to a Banyira employee for manual review. */
class ManualIdentityVerifier implements IdentityVerifier
{
    public function submit(User $user, string $kind, array $data): string
    {
        $user->verifications()->updateOrCreate(['kind' => $kind], ['status' => 'pending', 'provider' => 'manual', 'reference' => $data['reference'] ?? null]);

        return 'pending';
    }
}
