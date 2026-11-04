<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanProduct extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['purposes' => 'array', 'eligibility' => 'array', 'requirements' => 'array', 'sectors' => 'array', 'min_amount' => 'decimal:2', 'max_amount' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
