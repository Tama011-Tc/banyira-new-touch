<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InformationRequest extends Model
{
    protected $fillable = ['loan_application_id', 'requested_by', 'message', 'response', 'responded_at', 'status'];

    protected function casts(): array
    {
        return ['responded_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
