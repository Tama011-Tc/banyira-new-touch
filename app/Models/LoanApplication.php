<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LoanApplication extends Model
{
    public const STATUSES = [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'under_review' => 'Under review',
        'information_required' => 'Additional information required',
        'assessment_in_progress' => 'Assessment in progress',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'listed' => 'Listed on marketplace',
        'fully_funded' => 'Fully funded',
        'closed' => 'Closed',
    ];

    protected $guarded = ['id', 'reference', 'status', 'risk_class', 'assigned_to', 'submitted_at', 'decided_at', 'rule_set_id'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'monthly_turnover' => 'decimal:2', 'submitted_at' => 'datetime', 'decided_at' => 'datetime'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(BusinessProfile::class, 'business_profile_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class, 'loan_product_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function financialRecords(): HasMany
    {
        return $this->hasMany(FinancialRecord::class)->orderBy('period');
    }

    public function history(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class)->latest('id');
    }

    public function informationRequests(): HasMany
    {
        return $this->hasMany(InformationRequest::class)->latest('id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class)->latest('id');
    }

    public function listing(): HasOne
    {
        return $this->hasOne(Listing::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'information_required'], true);
    }
}
