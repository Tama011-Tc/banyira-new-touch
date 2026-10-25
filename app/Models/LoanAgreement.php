<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LoanAgreement extends Model
{
    protected $guarded = ['id'];

    /** Mirror the column defaults so a model built in code behaves like one loaded from the database. */
    protected $attributes = ['status' => 'pending_funding', 'simulated' => true, 'unapplied' => 0, 'grace_instalments' => 0];

    protected function casts(): array
    {
        return ['fees' => 'array', 'simulated' => 'boolean', 'funded_at' => 'datetime', 'disbursed_at' => 'datetime', 'first_due_on' => 'date:Y-m-d'];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investor_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function offerVersion(): BelongsTo
    {
        return $this->belongsTo(OfferVersion::class, 'offer_version_id');
    }

    public function schedule(): HasMany
    {
        return $this->hasMany(RepaymentSchedule::class, 'agreement_id');
    }

    public function commitment(): HasOne
    {
        return $this->hasOne(InvestmentCommitment::class, 'agreement_id');
    }

    public function disbursement(): HasOne
    {
        return $this->hasOne(Disbursement::class, 'agreement_id');
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(Repayment::class, 'agreement_id');
    }
}
