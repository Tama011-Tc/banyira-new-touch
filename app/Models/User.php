<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /** Role and status are never mass-assignable: they change only through explicit, audited actions. */
    protected $fillable = ['name', 'email', 'password', 'locale'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'mfa_confirmed_at' => 'datetime', 'role' => Role::class, 'must_change_password' => 'boolean'];
    }

    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function investorProfile(): HasOne
    {
        return $this->hasOne(InvestorProfile::class);
    }

    public function business(): HasOne
    {
        return $this->hasOne(Business::class, 'owner_id');
    }
}
