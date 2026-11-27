<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Page;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** The first screen after sign-in: what is waiting for this employee and their recent actions. */
class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Staff/Home', [
            // Work queues come from the shared "staffCounts"; unpublished pages matter only here.
            'unpublished' => $user->hasPermission('content.publish')
                ? Page::where(fn ($q) => $q->whereNull('published_revision')->orWhereColumn('published_revision', '!=', 'revision'))->count()
                : 0,
            'unread' => $user->unreadNotifications()->latest()->limit(5)->get()->map(fn ($n) => ['id' => $n->id, ...$n->data, 'at' => $n->created_at]),
            'activity' => AuditLog::where('user_id', $user->id)->latest('id')->limit(8)->get(['id', 'area', 'action', 'created_at']),
        ]);
    }
}
