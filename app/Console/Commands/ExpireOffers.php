<?php

namespace App\Console\Commands;

use App\Models\OfferVersion;
use Illuminate\Console\Command;

class ExpireOffers extends Command
{
    protected $signature = 'banyira:expire-offers';

    protected $description = 'Lapse unanswered proposals past their expiry';

    public function handle(): int
    {
        $n = OfferVersion::where('status', 'proposed')->whereNotNull('expires_at')->where('expires_at', '<', now())->update(['status' => 'expired']);
        $this->info("Expired {$n} proposal(s).");

        return self::SUCCESS;
    }
}
