<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentCriterion extends Model
{
    public $timestamps = false;

    protected $table = 'assessment_criteria';

    protected $fillable = ['criteria_set_id', 'key', 'label', 'weight', 'guidance', 'sort_order'];
}
