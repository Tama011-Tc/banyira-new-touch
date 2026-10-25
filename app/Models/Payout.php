<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $attributes = ['status' => 'pending', 'simulated' => true];

    protected function casts(): array
    {
        return ['simulated' => 'boolean', 'released_at' => 'datetime', 'created_at' => 'datetime'];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(LoanAgreement::class, 'agreement_id');
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investor_id');
    }
}
