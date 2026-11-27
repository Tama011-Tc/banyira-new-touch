<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A public website page. Content is {title, description, nav, blocks}.
 * Only ContentService writes it.
 */
class Page extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean', 'draft' => 'array', 'published' => 'array', 'published_at' => 'datetime', 'revision' => 'integer', 'published_revision' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(fn (Page $page) => $page->title = $page->draft['title']);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PageVersion::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_revision');
    }

    /** The public address of a page. Built-in pages keep their original URLs. */
    public function path(): string
    {
        if (! $this->is_system) {
            return '/pages/'.$this->slug;
        }

        return $this->slug === 'home' ? '/' : '/'.$this->slug;
    }

    /** "draft" (never published), "published", or "changes" (published, with newer edits). */
    public function state(): string
    {
        if ($this->published_revision === null) {
            return 'draft';
        }

        return $this->published_revision === $this->revision ? 'published' : 'changes';
    }
}
