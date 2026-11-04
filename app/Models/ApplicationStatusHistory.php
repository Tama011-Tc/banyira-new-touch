<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'application_status_history';

    protected $fillable = ['loan_application_id', 'from_status', 'to_status', 'user_id', 'note'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
