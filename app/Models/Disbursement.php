<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Disbursement extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    /** Mirror the column defaults so a model built in code behaves like one loaded from the database. */
    protected $attributes = ['status' => 'pending_approval'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(LoanAgreement::class, 'agreement_id');
    }
}
