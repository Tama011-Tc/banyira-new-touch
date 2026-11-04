<?php

namespace App\Policies;

use App\Models\Agreement;
use App\Models\User;

class AgreementPolicy
{
    public function view(User $user, Agreement $agreement): bool
    {
        return $agreement->borrower_id === $user->id
            || $agreement->parties()->where('user_id', $user->id)->exists()
            || $user->hasPermission('finance.view');
    }

    public function pay(User $user, Agreement $agreement): bool
    {
        return $agreement->borrower_id === $user->id;
    }
}
