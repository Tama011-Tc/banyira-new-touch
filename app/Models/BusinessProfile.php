<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessProfile extends Model
{
    protected $guarded = ['id', 'user_id', 'verification_status', 'verified_at'];

    protected function casts(): array
    {
        return ['monthly_turnover' => 'decimal:2', 'payout_account' => 'encrypted', 'verified_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function displayName(): string
    {
        return $this->trading_name ?: $this->legal_name;
    }
}
