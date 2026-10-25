<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Business extends Model
{
    protected $guarded = ['id'];

    /** Mirror the column defaults so a model built in code behaves like one loaded from the database. */
    protected $attributes = ['verification_status' => 'pending', 'years_operating' => 0];

    protected function casts(): array
    {
        return ['registration_no' => 'encrypted', 'owner_consent_at' => 'datetime'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
