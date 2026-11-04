<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepaymentAllocation extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['principal' => 'decimal:2', 'interest' => 'decimal:2'];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(RepaymentSchedule::class, 'repayment_schedule_id');
    }
}
