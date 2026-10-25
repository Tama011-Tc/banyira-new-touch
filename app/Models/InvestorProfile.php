<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InvestorProfile extends Model
{
    protected $guarded = ['id'];

    /** Mirror the column defaults so a model built in code behaves like one loaded from the database. */
    protected $attributes = ['kyc_status' => 'pending'];

    protected function casts(): array
    {
        return ['national_id' => 'encrypted', 'risk_acknowledged_at' => 'datetime', 'verified_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
