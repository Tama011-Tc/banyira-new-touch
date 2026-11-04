<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestorProfile extends Model
{
    protected $guarded = ['id', 'user_id', 'verification_status', 'verified_at'];

    protected function casts(): array
    {
        return ['national_id' => 'encrypted', 'payout_account' => 'encrypted', 'preferences' => 'array', 'risk_acknowledged_at' => 'datetime', 'verified_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
