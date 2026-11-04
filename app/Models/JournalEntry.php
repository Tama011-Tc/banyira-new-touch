<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class JournalEntry extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['entry_date' => 'date', 'is_demo' => 'boolean'];
    }

    // Posted entries are immutable. Corrections are made with a reversal entry.
    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Journal records are immutable. Post a reversal instead.'));
        static::deleting(fn () => throw new \LogicException('Journal records cannot be deleted.'));
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(JournalEntry::class, 'reverses_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
