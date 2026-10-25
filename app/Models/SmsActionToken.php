<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsActionToken extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $attributes = ['attempts' => 0];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'used_at' => 'datetime', 'created_at' => 'datetime'];
    }
}
