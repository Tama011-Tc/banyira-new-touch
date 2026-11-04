<?php

namespace App\Policies;

use App\Models\LoanApplication;
use App\Models\User;

class LoanApplicationPolicy
{
    public function view(User $user, LoanApplication $app): bool
    {
        return $this->owns($user, $app) || $user->hasPermission('applications.view');
    }

    public function update(User $user, LoanApplication $app): bool
    {
        return $this->owns($user, $app) && $app->isEditable();
    }

    public function delete(User $user, LoanApplication $app): bool
    {
        return $this->owns($user, $app) && $app->status === 'draft';
    }

    private function owns(User $user, LoanApplication $app): bool
    {
        return $user->type === 'business' && $user->businessProfile?->id === $app->business_profile_id;
    }
}
