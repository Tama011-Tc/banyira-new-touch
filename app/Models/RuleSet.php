<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RuleSet extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['effective_from' => 'date', 'interest_ceiling' => 'decimal:2', 'decided_at' => 'datetime'];
    }

    public function feeTiers(): HasMany
    {
        return $this->hasMany(FeeTier::class)->orderBy('sort_order');
    }

    public function riskBands(): HasMany
    {
        return $this->hasMany(RiskBand::class)->orderByDesc('min_score');
    }

    public function band(string $class): ?RiskBand
    {
        return $this->riskBands->firstWhere('risk_class', $class);
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
