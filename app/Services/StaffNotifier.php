<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\Alert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/** Tells employees about work waiting for them and about changes to their access. */
class StaffNotifier
{
    /** Notifies every active employee who holds the permission. */
    public function holders(string $permission, string $title, string $body, string $url, ?User $except = null): void
    {
        $staff = User::where('type', 'employee')->where('status', 'active')
            ->whereHas('roles.permissions', fn ($q) => $q->where('slug', $permission))
            ->when($except, fn ($q) => $q->whereKeyNot($except->id))
            ->get();

        $this->send($staff, new Alert($title, $body, $url));
    }

    public function employee(User $employee, string $title, string $body, string $url = '/internal/profile'): void
    {
        $this->send([$employee], new Alert($title, $body, $url));
    }

    private function send(iterable $recipients, Alert $alert): void
    {
        // Nothing is announced if the surrounding change is rolled back.
        DB::afterCommit(fn () => Notification::send($recipients, $alert));
    }
}
