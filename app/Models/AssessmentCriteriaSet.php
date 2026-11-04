<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentCriteriaSet extends Model
{
    protected $fillable = ['version', 'status'];

    public function criteria(): HasMany
    {
        return $this->hasMany(AssessmentCriterion::class, 'criteria_set_id')->orderBy('sort_order');
    }
}
