<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvestmentOffer extends Model
{
    protected $guarded = ['id', 'status', 'decided_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'annual_rate' => 'decimal:2', 'expires_at' => 'datetime', 'decided_at' => 'datetime'];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investor_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OfferEvent::class)->latest('id');
    }
}
