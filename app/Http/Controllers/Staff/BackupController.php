<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\Backups\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/** Database backups: take one now, schedule them, download and delete. */
class BackupController extends Controller
{
    private const LINK_SECONDS = 60;

    public function __construct(private BackupService $backups) {}

    public function index(Request $request)
    {
        $this->backups->closeInterrupted();

        return Inertia::render('Staff/IT/Backups', [
            'backups' => Backup::with('creator:id,name')->latest('id')->paginate(25)->through(fn (Backup $b) => [
                ...$b->only(['id', 'filename', 'size', 'checksum', 'status', 'origin', 'error', 'created_at']),
                'by' => $b->creator?->name,
            ]),
            'settings' => $this->backups->settings(),
            'frequencies' => BackupService::FREQUENCIES,
            'downloadLink' => $request->session()->get('download'),
        ]);
    }

    public function store(Request $request)
    {
        $backup = $this->backups->create('manual', $request->user());

        return $backup->status === 'completed'
            ? back()->with('success', 'Backup completed.')
            : back()->with('error', 'The backup failed. The reason is shown in the list.');
    }

    public function settings(Request $request)
    {
        $data = $request->validate([
            'frequency' => ['required', Rule::in(BackupService::FREQUENCIES)],
            'keep' => ['required', 'integer', 'min:1', 'max:90'],
        ]);
        $this->backups->saveSettings($data['frequency'], (int) $data['keep'], $request->user());

        return back()->with('success', 'Backup schedule saved.');
    }

    /** Asks for the password again, then gives the page a link that works for one minute. */
    public function requestDownload(Request $request, Backup $backup)
    {
        $request->validate(['password' => ['required', 'current_password:staff']]);
        abort_unless($backup->status === 'completed', 404);

        return back()->with('download', URL::temporarySignedRoute('backups.download', now()->addSeconds(self::LINK_SECONDS), ['backup' => $backup->id, 'employee' => $request->user()->id]));
    }

    public function download(Request $request, Backup $backup)
    {
        abort_unless((int) $request->query('employee') === $request->user()->id, 403);
        abort_unless($backup->status === 'completed' && Storage::disk($backup->disk)->exists($backup->filename), 404);
        $this->backups->recordDownload($backup, $request->user());

        return Storage::disk($backup->disk)->download($backup->filename);
    }

    public function destroy(Request $request, Backup $backup)
    {
        abort_if($backup->status === 'running', 409);
        $this->backups->delete($backup, $request->user());

        return back()->with('success', 'Backup deleted.');
    }
}
