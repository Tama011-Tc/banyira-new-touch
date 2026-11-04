<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeTier extends Model
{
    public $timestamps = false;

    protected $fillable = ['rule_set_id', 'upper_bound', 'rate', 'sort_order'];

    protected function casts(): array
    {
        return ['upper_bound' => 'decimal:2', 'rate' => 'decimal:2'];
    }
}
