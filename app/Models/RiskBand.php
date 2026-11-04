<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskBand extends Model
{
    public $timestamps = false;

    protected $fillable = ['rule_set_id', 'risk_class', 'label', 'min_score', 'max_amount', 'max_months'];

    protected function casts(): array
    {
        return ['max_amount' => 'decimal:2'];
    }
}
