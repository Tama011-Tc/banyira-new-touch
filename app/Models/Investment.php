<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Investment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['principal' => 'decimal:2', 'annual_rate' => 'decimal:2', 'principal_repaid' => 'decimal:2', 'interest_received' => 'decimal:2', 'started_on' => 'date'];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investor_id');
    }

    public function schedule(): HasMany
    {
        return $this->hasMany(RepaymentSchedule::class)->orderBy('installment_no');
    }

    public function outstanding(): string
    {
        return bcsub($this->principal, $this->principal_repaid, 2);
    }
}
