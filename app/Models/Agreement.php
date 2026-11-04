<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agreement extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['principal_total' => 'decimal:2', 'fee_rate' => 'decimal:2', 'service_fee' => 'decimal:2', 'disbursed_at' => 'datetime'];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'borrower_id');
    }

    public function parties(): HasMany
    {
        return $this->hasMany(AgreementParty::class);
    }

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }

    public function schedule(): HasMany
    {
        return $this->hasMany(RepaymentSchedule::class)->orderBy('due_date')->orderBy('investment_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public function ruleSet(): BelongsTo
    {
        return $this->belongsTo(RuleSet::class);
    }
}
