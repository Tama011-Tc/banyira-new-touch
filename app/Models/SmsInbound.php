<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsInbound extends Model
{
    protected $table = 'sms_inbound';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
