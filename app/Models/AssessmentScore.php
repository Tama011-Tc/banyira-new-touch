<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentScore extends Model
{
    public $timestamps = false;

    protected $fillable = ['assessment_id', 'criterion_id', 'score', 'note'];
}
