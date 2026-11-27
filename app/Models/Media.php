<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An image uploaded for use on public pages. */
class Media extends Model
{
    protected $table = 'media';

    protected $guarded = ['id'];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** What a page needs to show the image. */
    public function present(): array
    {
        return ['id' => $this->id, 'url' => '/media/'.$this->id, 'alt' => $this->alt, 'width' => $this->width, 'height' => $this->height];
    }
}
