<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('notifications/Index', ['items' => $request->user()->notifications()->latest()->limit(50)->get()->map(fn ($n) => [
            'id' => $n->id, 'title' => $n->data['title'] ?? '', 'body' => $n->data['body'] ?? '', 'url' => $n->data['url'] ?? '/dashboard', 'read' => $n->read_at !== null, 'at' => $n->created_at->diffForHumans(),
        ])]);
    }

    public function markRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
