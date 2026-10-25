<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RepaymentSchedule extends Model
{
    protected $guarded = ['id'];

    /** Mirror the column defaults so a model built in code behaves like one loaded from the database. */
    protected $attributes = ['status' => 'scheduled', 'principal_paid' => 0, 'interest_paid' => 0];

    protected function casts(): array
    {
        return ['due_on' => 'date:Y-m-d'];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(LoanAgreement::class, 'agreement_id');
    }
}
