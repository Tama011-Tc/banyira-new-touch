<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditLogger::class);
    }

    public function boot(): void
    {
        // One Gate per ability; the role comes from the authenticated user, never from the request.
        foreach (array_keys(Role::ABILITIES) as $ability) {
            Gate::define($ability, fn (User $u) => $u->isActive() && $u->role->can($ability));
        }

        RateLimiter::for('sensitive', fn (Request $r) => Limit::perMinute(20)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('messages', fn (Request $r) => Limit::perMinute(6)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('uploads', fn (Request $r) => Limit::perMinute(15)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('webhooks', fn (Request $r) => Limit::perMinute(120)->by($r->ip()));

        Event::listen(Login::class, fn (Login $e) => app(AuditLogger::class)->record('auth.login', null, [], $e->user));
        Event::listen(Logout::class, fn (Logout $e) => $e->user ? app(AuditLogger::class)->record('auth.logout', null, [], $e->user) : null);
        Event::listen(Failed::class, fn (Failed $e) => app(AuditLogger::class)->record('auth.failed', null, ['email_hash' => hash('sha256', strtolower((string) ($e->credentials['email'] ?? '')))]));
    }
}
