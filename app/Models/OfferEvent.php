<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['investment_offer_id', 'from_status', 'to_status', 'user_id'];
}
