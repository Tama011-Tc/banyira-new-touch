<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeSchedule extends Model
{
    protected $guarded = ['id'];

    protected $attributes = ['status' => 'approved', 'tax_bp' => 0, 'processing_bp' => 0];

    protected function casts(): array
    {
        return ['tiers' => 'array', 'effective_from' => 'date:Y-m-d', 'effective_to' => 'date:Y-m-d', 'approved_at' => 'datetime'];
    }
}
