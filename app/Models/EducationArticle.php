<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationArticle extends Model
{
    protected $guarded = ['id'];

    protected $attributes = ['published' => false, 'position' => 0];

    protected function casts(): array
    {
        return ['published' => 'boolean'];
    }

    /** Title and body in the reader's language, falling back to English until a translation exists. */
    public function localised(string $locale): array
    {
        $ny = $locale === 'ny' && $this->title_ny && $this->body_ny;

        return ['title' => $ny ? $this->title_ny : $this->title_en, 'body' => $ny ? $this->body_ny : $this->body_en, 'translated' => $locale !== 'ny' || $ny, 'language' => $ny ? 'ny' : 'en'];
    }
}
