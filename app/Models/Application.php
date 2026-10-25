<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Application extends Model
{
    protected $guarded = ['id'];

    /** Mirror the column defaults so a model built in code behaves like one loaded from the database. */
    protected $attributes = ['status' => 'draft', 'terms_version' => 1, 'frequency' => 'monthly', 'interest_method' => 'reducing'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(CreditAssessment::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(CreditDecision::class);
    }

    public function listing(): HasOne
    {
        return $this->hasOne(Listing::class);
    }
}
