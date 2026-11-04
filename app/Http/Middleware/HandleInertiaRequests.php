<?php

namespace App\Http\Middleware;

use App\Models\AgreementParty;
use App\Models\ApprovalRequest;
use App\Models\Assessment;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\Payment;
use App\Models\User;
use App\Models\VerificationRecord;
use App\Services\MessagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $staffArea = $request->is('internal', 'internal/*', 'internal-access', 'internal-access/*');
        /** @var User|null $user */
        $user = $staffArea ? $request->user('staff') : $request->user('web');

        return [
            ...parent::share($request),
            'auth' => ['user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => $user->type,
                'permissions' => $user->permissionSlugs(),
                'role' => $user->isEmployee() ? $user->roles()->value('name') : null,
            ] : null],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'counts' => fn () => $user && ! $user->isEmployee() ? [
                'messages' => app(MessagingService::class)->unreadCount($user),
                'notifications' => $user->unreadNotifications()->count(),
            ] : ['messages' => 0, 'notifications' => 0],
            'demoPayments' => config('banyira.payments.driver') === 'demo',
            'navProducts' => fn () => $staffArea ? [] : Cache::remember('nav-products', 300, fn () => LoanProduct::active()->get(['slug', 'name', 'summary'])->toArray()),
            'staffCounts' => fn () => $user?->isEmployee() ? $this->staffCounts($user) : null,
        ];
    }

    private function staffCounts(User $u): array
    {
        return array_filter([
            'queue' => $u->hasPermission('applications.assess') ? LoanApplication::whereIn('status', ['submitted', 'under_review', 'assessment_in_progress'])->count() : 0,
            'approvals' => $u->hasPermission('applications.approve') ? Assessment::where('status', 'submitted')->count() + LoanApplication::where('status', 'approved')->count() : 0,
            'verifications' => $u->hasPermission('customers.verify') ? VerificationRecord::where('status', 'pending')->distinct('user_id')->count('user_id') : 0,
            'reconcile' => $u->hasPermission('payments.reconcile') ? Payment::where('status', 'pending')->count() + AgreementParty::whereNull('funds_confirmed_at')->where('role', 'investor')->whereHas('agreement', fn ($q) => $q->where('status', 'awaiting_funding'))->count() : 0,
            'checker' => $u->hasPermission('finance.approve') ? ApprovalRequest::where('status', 'pending')->where('requested_by', '!=', $u->id)->count() : 0,
        ]);
    }
}
