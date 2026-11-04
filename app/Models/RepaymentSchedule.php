<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepaymentSchedule extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'principal_due' => 'decimal:2', 'interest_due' => 'decimal:2', 'principal_paid' => 'decimal:2', 'interest_paid' => 'decimal:2'];
    }

    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }

    public function amountDue(): string
    {
        return bcadd($this->principal_due, $this->interest_due, 2);
    }

    public function amountOutstanding(): string
    {
        return bcsub($this->amountDue(), bcadd($this->principal_paid, $this->interest_paid, 2), 2);
    }
}
