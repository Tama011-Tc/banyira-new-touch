<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Services\Platform;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /** role => [title, route name, icon]. Server-side authorisation still protects every route. */
    private const NAV = [
        'investor' => [['Marketplace', 'marketplace.index', 'store'], ['Watchlist', 'watchlist.index', 'bookmark'], ['Portfolio', 'portfolio.index', 'pie'], ['Messages', 'conversations.index', 'message'], ['Profile & verification', 'investor.profile', 'shield']],
        'business_owner' => [['Overview', 'dashboard', 'home'], ['My business', 'business.edit', 'building'], ['Applications', 'applications.index', 'file'], ['Messages', 'conversations.index', 'message'], ['Loans', 'agreements.index', 'wallet']],
        'sales' => [['Workspace', 'sales.index', 'home'], ['Register a business', 'sales.create', 'plus']],
        'credit_analyst' => [['Review queue', 'credit.index', 'inbox']],
        'credit_approver' => [['Decisions', 'credit.index', 'inbox'], ['Listings', 'listings.index', 'list'], ['Loans', 'agreements.index', 'wallet']],
        'executive' => [['Overview', 'executive.index', 'chart'], ['Decisions', 'credit.index', 'inbox'], ['Listings', 'listings.index', 'list'], ['Loans', 'agreements.index', 'wallet'], ['Finance', 'finance.index', 'coins'], ['Compliance', 'compliance.index', 'shield'], ['Audit log', 'audit.index', 'scroll']],
        'finance' => [['Finance', 'finance.index', 'coins'], ['Loans', 'agreements.index', 'wallet'], ['Audit log', 'audit.index', 'scroll']],
        'compliance' => [['Compliance', 'compliance.index', 'shield'], ['Moderation', 'moderation.index', 'flag'], ['Finance', 'finance.index', 'coins'], ['Loans', 'agreements.index', 'wallet'], ['Audit log', 'audit.index', 'scroll']],
        'it_admin' => [['Oversight', 'executive.index', 'chart'], ['Users & access', 'admin.users', 'users'], ['Applications', 'credit.index', 'inbox'], ['Listings', 'listings.index', 'list'], ['Loans', 'agreements.index', 'wallet'], ['Finance & ledger', 'finance.index', 'coins'], ['Moderation', 'moderation.index', 'flag'], ['Complaints', 'complaints.index', 'file'], ['Audit log', 'audit.index', 'scroll'], ['Settings', 'admin.settings', 'cog'], ['System health', 'admin.health', 'pulse']],
        'support' => [['Moderation', 'moderation.index', 'flag'], ['Complaints', 'complaints.index', 'inbox']],
    ];

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('banyira.brand'),
            'brand' => ['name' => config('banyira.brand'), 'tagline' => config('banyira.tagline')],
            'auth' => ['user' => $user ? ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role->value, 'role_label' => $user->role->label(), 'locale' => $user->locale] : null],
            'nav' => $user ? array_map(fn ($n) => ['title' => $n[0], 'route' => $n[1], 'icon' => $n[2]], self::NAV[$user->role->value] ?? []) : [],
            'can' => $user ? collect(array_keys(Role::ABILITIES))->mapWithKeys(fn ($a) => [$a => $user->role->can($a)]) : (object) [],
            'mode' => ['operating' => Platform::mode(), 'simulation' => Platform::isSimulation()],
            'flash' => ['success' => fn () => $request->session()->get('success'), 'error' => fn () => $request->session()->get('error')],
            'temp_credential' => fn () => $request->session()->get('temp_credential'),
            'unread' => fn () => $user ? $user->unreadNotifications()->count() : 0,
        ];
    }
}
