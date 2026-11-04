<?php

namespace App\Contracts;

use App\Models\User;

interface IdentityVerifier
{
    /** Start a verification. Returns 'pending' for manual review or the provider's decision. */
    public function submit(User $user, string $kind, array $data): string;
}
