<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Listing extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount_requested' => 'decimal:2', 'amount_accepted' => 'decimal:2', 'indicative_rate' => 'decimal:2', 'published_at' => 'datetime', 'closes_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(InvestmentOffer::class);
    }

    public function agreement(): HasOne
    {
        return $this->hasOne(Agreement::class);
    }

    public function remaining(): string
    {
        return bcsub($this->amount_requested, $this->amount_accepted, 2);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open' && $this->closes_at->isFuture();
    }
}
