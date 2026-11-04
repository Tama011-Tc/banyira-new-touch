<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LedgerAccount extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_client_money' => 'boolean'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}
