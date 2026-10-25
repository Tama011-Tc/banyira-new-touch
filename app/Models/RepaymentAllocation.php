<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RepaymentAllocation extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(RepaymentSchedule::class, 'schedule_id');
    }
}
