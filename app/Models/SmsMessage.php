<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsMessage extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $attributes = ['simulated' => true, 'status' => 'sent'];

    protected function casts(): array
    {
        return ['simulated' => 'boolean', 'created_at' => 'datetime'];
    }
}
