<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LoginAttempt;
use App\Models\RuleSet;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\TableQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/** IT administration: health, security, audit, jobs and integrations. */
class AdminController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function health()
    {
        $t = microtime(true);
        DB::select('select 1');
        $dbMs = round((microtime(true) - $t) * 1000, 1);
        $disk = config('banyira.documents_disk');

        return Inertia::render('Staff/IT/Health', [
            'checks' => [
                ['name' => 'Database', 'status' => 'healthy', 'note' => "{$dbMs} ms"],
                ['name' => 'Queue', 'status' => DB::table('jobs')->count() > 100 ? 'degraded' : 'healthy', 'note' => DB::table('jobs')->count().' waiting · '.DB::table('failed_jobs')->count().' failed'],
                ['name' => 'Document storage', 'status' => $this->writable($disk) ? 'healthy' : 'failing', 'note' => 'Disk: '.$disk],
                ['name' => 'SMS', 'status' => SmsMessage::where('status', 'failed')->where('created_at', '>', now()->subDay())->exists() ? 'degraded' : 'healthy', 'note' => 'Driver: '.config('banyira.sms.driver')],
                ['name' => 'Payments', 'status' => 'healthy', 'note' => 'Driver: '.config('banyira.payments.driver').(config('banyira.payments.driver') === 'demo' ? ' (no real money)' : '')],
                ['name' => 'Financing rules', 'status' => RuleSet::where('status', 'active')->count() === 1 ? 'healthy' : 'failing', 'note' => RuleSet::where('status', 'active')->value('version') ?? 'none active'],
            ],
            'stats' => [
                'users' => User::count(),
                'sessions' => DB::table('sessions')->where('last_activity', '>', now()->subMinutes(30)->timestamp)->count(),
                'failedLogins' => LoginAttempt::where('success', false)->where('created_at', '>', now()->subDay())->count(),
                'flagged' => LoginAttempt::where('flagged', true)->where('created_at', '>', now()->subWeek())->count(),
            ],
            'env' => ['php' => PHP_VERSION, 'laravel' => app()->version(), 'env' => app()->environment(), 'debug' => config('app.debug')],
        ]);
    }

    public function security(Request $request)
    {
        $table = TableQuery::for(LoginAttempt::query(), $request)
            ->search(['email', 'ip'])
            ->filter('result', fn ($q, $v) => $v === 'flagged' ? $q->where('flagged', true) : $q->where('success', $v === 'success'), ['success', 'failed', 'flagged'])
            ->sort(['when' => 'id'], 'when');

        return Inertia::render('Staff/IT/Security', [
            'attempts' => $table->paginate()->through(fn ($a) => $a->only(['id', 'guard', 'email', 'ip', 'success', 'reason', 'flagged', 'created_at'])),
            'query' => $table->state(),
            'locked' => User::whereNotNull('locked_until')->where('locked_until', '>', now())->get(['id', 'name', 'email', 'type', 'locked_until']),
        ]);
    }

    public function jobs()
    {
        return Inertia::render('Staff/IT/Jobs', [
            'queued' => DB::table('jobs')->count(),
            'failed' => DB::table('failed_jobs')->latest('id')->limit(20)->get(['id', 'queue', 'exception', 'failed_at'])->map(fn ($j) => ['id' => $j->id, 'queue' => $j->queue, 'error' => strtok($j->exception, "\n"), 'at' => $j->failed_at]),
            'sms' => SmsMessage::selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status'),
            'schedule' => [
                ['task' => 'Activate approved financing rules', 'when' => 'Daily 00:05'],
                ['task' => 'Flag overdue instalments', 'when' => 'Daily 01:00'],
                ['task' => 'Database backup (when scheduled backups are on)', 'when' => 'Daily 02:00'],
                ['task' => 'Expire stale investor offers', 'when' => 'Hourly'],
                ['task' => 'Repayment reminders (SMS)', 'when' => 'Daily 07:00'],
            ],
            'integrations' => [
                ['name' => 'SMS', 'driver' => config('banyira.sms.driver'), 'configured' => config('banyira.sms.driver') === 'log' || filled(config('banyira.sms.africastalking.api_key')), 'env' => 'SMS_DRIVER, AFRICASTALKING_USERNAME, AFRICASTALKING_API_KEY'],
                ['name' => 'Payments', 'driver' => config('banyira.payments.driver'), 'configured' => config('banyira.payments.driver') === 'demo', 'env' => 'PAYMENTS_DRIVER, AIRTEL_MONEY_*, TNM_MPAMBA_API_KEY, PAYMENTS_WEBHOOK_SECRET'],
                ['name' => 'Email', 'driver' => config('mail.default'), 'configured' => config('mail.default') !== 'log', 'env' => 'MAIL_MAILER, MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD'],
                ['name' => 'Identity verification', 'driver' => config('banyira.identity.driver'), 'configured' => true, 'env' => 'IDENTITY_DRIVER'],
                ['name' => 'Document storage', 'driver' => config('banyira.documents_disk'), 'configured' => true, 'env' => 'DOCUMENTS_DISK, AWS_*'],
            ],
        ]);
    }

    public function retryJobs(Request $request)
    {
        Artisan::call('queue:retry', ['id' => ['all']]);
        $this->audit->log('system', 'Retried failed jobs', null, [], $request->user());

        return back()->with('success', 'Failed jobs queued for retry.');
    }

    public function audit(Request $request)
    {
        $areas = AuditLog::distinct()->orderBy('area')->pluck('area')->all();
        $table = TableQuery::for(AuditLog::with('user:id,name'), $request)
            ->search(['action'])
            ->filter('area', fn ($q, $area) => $q->where('area', $area), $areas)
            ->sort(['when' => 'id'], 'when');

        return Inertia::render('Staff/Audit', [
            'logs' => $table->paginate()
                ->through(fn ($l) => [...$l->only(['id', 'area', 'action', 'ip', 'created_at', 'meta']), 'user' => $l->user?->name ?? 'System']),
            'query' => $table->state(),
            'areas' => $areas,
        ]);
    }

    private function writable(string $disk): bool
    {
        try {
            Storage::disk($disk)->put('.health', (string) time());

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
