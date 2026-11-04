<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/** Sends each employee to their own workspace. */
class HomeController extends Controller
{
    private const HOMES = [
        'executive.dashboard' => '/internal/executive',
        'applications.assess' => '/internal/credit',
        'finance.view' => '/internal/finance',
        'marketing.reports' => '/internal/sales',
        'system.manage' => '/internal/it',
    ];

    public function __invoke(Request $request)
    {
        foreach (self::HOMES as $perm => $path) {
            if ($request->user()->hasPermission($perm)) {
                return redirect($path);
            }
        }

        return redirect('/internal/audit');
    }
}
