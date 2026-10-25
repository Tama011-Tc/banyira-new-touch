<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OfferVersion extends Model
{
    protected $guarded = ['id'];

    /** Mirror the column defaults so a model built in code behaves like one loaded from the database. */
    protected $attributes = ['status' => 'proposed', 'grace_instalments' => 0];

    protected function casts(): array
    {
        return ['fees' => 'array', 'investor_accepted_at' => 'datetime', 'business_accepted_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }
}
