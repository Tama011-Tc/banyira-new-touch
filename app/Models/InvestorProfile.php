<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InvestorProfile extends Model
{
    protected $guarded = ['id'];

    /** Mirror the column defaults so a model built in code behaves like one loaded from the database. */
    protected $attributes = ['kyc_status' => 'pending'];

    protected function casts(): array
    {
        return ['national_id' => 'encrypted', 'payout_account_number' => 'encrypted', 'payout_details_changed_at' => 'datetime', 'risk_acknowledged_at' => 'datetime', 'verified_at' => 'datetime'];
    }

    public function hasPayoutDetails(): bool
    {
        return $this->payout_bank && $this->payout_account_name && $this->payout_account_number;
    }

    /** "••••5678": the full number is never sent to a browser, not even to finance. */
    public function maskedAccount(): ?string
    {
        return $this->payout_account_number ? '••••'.substr($this->payout_account_number, -4) : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
