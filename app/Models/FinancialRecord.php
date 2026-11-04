<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialRecord extends Model
{
    public $timestamps = false;

    protected $fillable = ['loan_application_id', 'period', 'inflow', 'outflow', 'source'];

    protected function casts(): array
    {
        return ['period' => 'date', 'inflow' => 'decimal:2', 'outflow' => 'decimal:2'];
    }
}
