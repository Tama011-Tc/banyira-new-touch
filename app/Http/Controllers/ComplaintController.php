<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $viewAll = $request->user()->role->can('complaints.view');
        $q = Complaint::query()->with('user:id,name')->latest('id');
        $viewAll || $q->where('user_id', $request->user()->id);

        return Inertia::render('complaints/Index', ['staff' => $request->user()->role->can('complaints.handle'), 'view_all' => $viewAll, 'complaints' => $q->get()->map(fn ($c) => ['id' => $c->id, 'category' => $c->category, 'subject' => $c->subject, 'body' => $c->body, 'status' => $c->status, 'resolution' => $c->resolution, 'who' => $c->user->name, 'at' => $c->created_at->toDateString()])]);
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $d = $request->validate(['category' => 'required|in:service,payment,fraud,privacy,other', 'subject' => 'required|string|max:150', 'body' => 'required|string|min:10|max:3000']);
        $c = Complaint::create($d + ['user_id' => $request->user()->id]);
        $audit->record('complaint.filed', $c, ['category' => $d['category']]);

        return back()->with('success', 'Your complaint was received. A reference is C-'.str_pad((string) $c->id, 5, '0', STR_PAD_LEFT).'.');
    }

    public function update(Request $request, Complaint $complaint, AuditLogger $audit)
    {
        $d = $request->validate(['status' => 'required|in:investigating,resolved,closed', 'resolution' => 'nullable|string|max:2000']);
        $complaint->update($d + ['handled_by' => $request->user()->id]);
        $audit->record('complaint.'.$d['status'], $complaint);

        return back()->with('success', 'Updated.');
    }
}
