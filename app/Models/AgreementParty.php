<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AgreementParty extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['principal' => 'decimal:2', 'annual_rate' => 'decimal:2', 'signed_at' => 'datetime', 'funds_confirmed_at' => 'datetime'];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(InvestmentOffer::class, 'investment_offer_id');
    }

    public function investment(): HasOne
    {
        return $this->hasOne(Investment::class);
    }
}
