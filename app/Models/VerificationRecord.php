<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationRecord extends Model
{
    protected $fillable = ['user_id', 'kind', 'status', 'provider', 'reference', 'reviewed_by', 'reviewed_at', 'notes'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
