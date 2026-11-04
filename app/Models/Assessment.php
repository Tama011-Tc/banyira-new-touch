<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['checks' => 'array', 'risks' => 'array', 'total_score' => 'decimal:2', 'submitted_at' => 'datetime', 'decided_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function criteriaSet(): BelongsTo
    {
        return $this->belongsTo(AssessmentCriteriaSet::class, 'criteria_set_id');
    }

    public function ruleSet(): BelongsTo
    {
        return $this->belongsTo(RuleSet::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class);
    }
}
