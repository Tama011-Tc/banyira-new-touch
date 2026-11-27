<?php

use App\Models\RepaymentSchedule;
use App\Models\User;
use App\Notifications\Alert;
use App\Services\Backups\BackupService;
use App\Services\MarketplaceService;
use App\Services\PaymentService;
use App\Services\RulesService;
use App\Support\Money;
use App\Support\Totp;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Schedule::call(fn () => app(RulesService::class)->activateDue())->dailyAt('00:05')->name('activate-rules');
Schedule::call(fn () => app(PaymentService::class)->markOverdue())->dailyAt('01:00')->name('mark-overdue');
Schedule::call(fn () => app(MarketplaceService::class)->expireStale())->hourly()->name('expire-offers');
Schedule::call(fn () => app(BackupService::class)->runScheduled())->dailyAt('02:00')->name('database-backup')->withoutOverlapping();

// SMS reminder three days before each instalment date, one message per borrower.
Schedule::call(function () {
    RepaymentSchedule::whereDate('due_date', now()->addDays(3))->where('status', '!=', 'paid')
        ->with('investment.agreement.borrower')->get()
        ->groupBy(fn ($r) => $r->investment->agreement->borrower_id)
        ->each(fn ($rows) => $rows->first()->investment->agreement->borrower->notify(new Alert(
            'Repayment due in 3 days',
            Money::format(Money::add(...$rows->map->amountOutstanding()->all())).' is due on '.$rows->first()->due_date->format('j M').'.',
            '/business/repayments', sms: true,
        )));
})->dailyAt('07:00')->name('repayment-reminders');

// Development helper: print the current MFA code for a seeded employee.
Artisan::command('banyira:staff-code {email}', function (string $email) {
    abort_if(app()->isProduction(), 403);
    $u = User::where('email', $email)->where('type', 'employee')->firstOrFail();
    if (! $u->mfa_secret) {
        $this->warn('This employee has not set up MFA yet. Sign in once to see the setup key.');

        return;
    }
    $this->info(Totp::code($u->mfa_secret));
})->purpose('Show the current authenticator code for a staff account (local only)');

Artisan::command('banyira:seed-if-empty', function () {
    if (User::query()->exists()) {
        $this->info('Data already present; nothing to seed.');

        return 0;
    }

    return DB::transaction(fn () => $this->call('db:seed', ['--force' => true]));
})->purpose('Seed the database only when it has no users');
