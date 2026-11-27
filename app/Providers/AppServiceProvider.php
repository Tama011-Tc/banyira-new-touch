<?php

namespace App\Providers;

use App\Contracts\DatabaseDumper;
use App\Contracts\IdentityVerifier;
use App\Contracts\PaymentGateway;
use App\Contracts\SmsGateway;
use App\Models;
use App\Models\User;
use App\Services\Backups\MysqlDumper;
use App\Services\Backups\PostgresDumper;
use App\Services\Identity\ManualIdentityVerifier;
use App\Services\Payments\DemoGateway;
use App\Services\Payments\MobileMoneyGateway;
use App\Services\Sms\AfricasTalkingSmsGateway;
use App\Services\Sms\LogSmsGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsGateway::class, fn () => match (config('banyira.sms.driver')) {
            'africastalking' => new AfricasTalkingSmsGateway(
                (string) config('banyira.sms.africastalking.username'),
                (string) config('banyira.sms.africastalking.api_key'),
                (string) config('banyira.sms.sender_id'),
            ),
            default => new LogSmsGateway,
        });

        $this->app->singleton(PaymentGateway::class, fn () => match ($driver = config('banyira.payments.driver')) {
            'airtel', 'tnm' => new MobileMoneyGateway($driver, (array) config("banyira.payments.{$driver}"), config('banyira.payments.webhook_secret')),
            default => new DemoGateway,
        });

        $this->app->singleton(IdentityVerifier::class, ManualIdentityVerifier::class);

        $this->app->bind(DatabaseDumper::class, function () {
            $connection = DB::connection()->getConfig();
            [$class, $binary] = $connection['driver'] === 'pgsql'
                ? [PostgresDumper::class, config('banyira.backups.pg_dump')]
                : [MysqlDumper::class, config('banyira.backups.mysqldump')];

            return new $class($connection, $binary, config('banyira.backups.timeout_seconds'));
        });
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::handleLazyLoadingViolationUsing(fn ($model, $relation) => logger()->warning('Lazy loaded '.$model::class.'::'.$relation));

        Relation::enforceMorphMap([
            'user' => User::class,
            'application' => Models\LoanApplication::class,
            'assessment' => Models\Assessment::class,
            'listing' => Models\Listing::class,
            'offer' => Models\InvestmentOffer::class,
            'agreement' => Models\Agreement::class,
            'agreement_party' => Models\AgreementParty::class,
            'payment' => Models\Payment::class,
            'expense' => Models\Expense::class,
            'payroll_run' => Models\PayrollRun::class,
            'rule_set' => Models\RuleSet::class,
            'loan_product' => Models\LoanProduct::class,
            'journal_entry' => Models\JournalEntry::class,
            'role' => Models\Role::class,
            'lead' => Models\Lead::class,
            'approval' => Models\ApprovalRequest::class,
            'page' => Models\Page::class,
            'media' => Models\Media::class,
            'backup' => Models\Backup::class,
        ]);

        // Records are bound by integer id. Postgres raises an error for other text, so such URLs do not match.
        // {product} is not listed: public product pages are addressed by slug.
        Route::patterns(array_fill_keys([
            'agreement', 'application', 'approval', 'assessment', 'attachment', 'backup', 'campaign', 'conversation', 'document', 'entry', 'id',
            'informationRequest', 'lead', 'listing', 'media', 'offer', 'page', 'party', 'payment', 'record', 'role', 'ruleSet', 'user', 'version',
        ], '[0-9]+'));

        // Employee permissions map straight onto Gate abilities, e.g. can:applications.assess.
        // "a|b" means any of the listed permissions.
        Gate::before(function (User $user, string $ability) {
            if (str_contains($ability, '.') && $user->isEmployee()) {
                foreach (explode('|', $ability) as $slug) {
                    if ($user->hasPermission($slug)) {
                        return true;
                    }
                }
            }

            return null;
        });

        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        RateLimiter::for('customer-login', fn (Request $r) => Limit::perMinute(10)->by(strtolower((string) $r->input('email')).'|'.$r->ip()));
        RateLimiter::for('staff-login', fn (Request $r) => [Limit::perMinute(5)->by(strtolower((string) $r->input('email')).'|'.$r->ip()), Limit::perMinute(20)->by($r->ip())]);
        RateLimiter::for('sms-code', fn (Request $r) => Limit::perHour(5)->by((string) $r->user()?->id));
    }
}
