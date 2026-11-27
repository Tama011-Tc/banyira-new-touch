<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'phone', 'type', 'status', 'password', 'email_verified_at', 'phone_verified_at', 'password_changed_at'];

    protected $hidden = ['password', 'remember_token', 'mfa_secret'];

    private ?array $permissionCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'mfa_confirmed_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'mfa_secret' => 'encrypted',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function businessProfile(): HasOne
    {
        return $this->hasOne(BusinessProfile::class);
    }

    public function investorProfile(): HasOne
    {
        return $this->hasOne(InvestorProfile::class);
    }

    public function employeeProfile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(VerificationRecord::class);
    }

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class, 'investor_id');
    }

    public function isEmployee(): bool
    {
        return $this->type === 'employee';
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    /** @return list<string> */
    public function permissionSlugs(): array
    {
        if (! $this->isEmployee()) {
            return [];
        }

        return $this->permissionCache ??= Permission::query()
            ->whereHas('roles.users', fn ($q) => $q->whereKey($this->id))
            ->pluck('slug')->all();
    }

    public function hasPermission(string $slug): bool
    {
        return in_array($slug, $this->permissionSlugs(), true);
    }

    public function flushPermissionCache(): void
    {
        $this->permissionCache = null;
    }

    /** In-app notifications, newest first, as the notification pages show them. */
    public function notificationFeed(): LengthAwarePaginator
    {
        return $this->notifications()->latest()->paginate(20)
            ->through(fn ($n) => ['id' => $n->id, ...$n->data, 'at' => $n->created_at, 'read' => (bool) $n->read_at]);
    }

    /** Marks one notification, or all of them, as read. */
    public function markNotificationsRead(?string $id = null): void
    {
        $this->unreadNotifications()->when($id, fn ($q) => $q->whereKey($id))->update(['read_at' => now()]);
    }
}
