<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageReport;
use App\Services\AuditLogger;
use App\Services\MessagingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ModerationController extends Controller
{
    public function index()
    {
        return Inertia::render('moderation/Index', [
            'held' => Message::with('sender:id,name', 'conversation.listing')->whereIn('status', ['pending_review', 'blocked'])->latest('id')->limit(50)->get()->map(fn ($m) => ['id' => $m->id, 'status' => $m->status, 'body' => $m->body, 'sender' => $m->sender->name, 'listing' => $m->conversation->listing->title, 'flags' => collect($m->flags)->pluck('rule'), 'at' => $m->created_at->toDateTimeString()]),
            'reports' => MessageReport::with('reporter:id,name')->where('status', 'open')->latest('id')->get()->map(fn ($r) => ['id' => $r->id, 'conversation_id' => $r->conversation_id, 'reason' => $r->reason, 'detail' => $r->detail, 'reporter' => $r->reporter->name, 'at' => $r->created_at->toDateTimeString()]),
        ]);
    }

    public function resolve(Request $request, Message $message, MessagingService $messaging)
    {
        $d = $request->validate(['outcome' => 'required|in:release,uphold', 'note' => 'nullable|string|max:500']);
        $messaging->resolve($message, $request->user(), $d['outcome'], $d['note'] ?? null);

        return back()->with('success', 'Decision recorded.');
    }

    public function handleReport(Request $request, MessageReport $report, AuditLogger $audit)
    {
        $d = $request->validate(['status' => 'required|in:actioned,dismissed']);
        $report->forceFill(['status' => $d['status'], 'handled_by' => $request->user()->id])->save();
        $audit->record('moderation.report_'.$d['status'], $report);

        return back()->with('success', 'Recorded.');
    }
}
