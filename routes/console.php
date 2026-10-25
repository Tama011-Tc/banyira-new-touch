<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('banyira:close-campaigns')->dailyAt('00:10')->withoutOverlapping()->onOneServer();
Schedule::command('banyira:expire-offers')->hourly()->withoutOverlapping()->onOneServer();
