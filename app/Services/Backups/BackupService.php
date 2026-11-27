<?php

namespace App\Services\Backups;

use App\Contracts\DatabaseDumper;
use App\Models\Backup;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\StaffNotifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/** Takes compressed database backups, keeps the newest ones and removes the rest. */
class BackupService
{
    public const FREQUENCIES = ['off', 'daily', 'weekly'];

    private const SETTINGS_KEY = 'backups';

    private const DEFAULTS = ['frequency' => 'off', 'keep' => 14];

    public function __construct(private DatabaseDumper $dumper, private AuditLogger $audit, private StaffNotifier $notifier) {}

    public function create(string $origin, ?User $by = null): Backup
    {
        $lock = Cache::lock('database-backup', $this->timeout() + 60);
        if (! $lock->get()) {
            throw ValidationException::withMessages(['backup' => 'A backup is already running.']);
        }

        try {
            return $this->run($origin, $by);
        } finally {
            $lock->release();
        }
    }

    /** Called daily by the scheduler. */
    public function runScheduled(): ?Backup
    {
        $frequency = $this->settings()['frequency'];
        if ($frequency === 'off') {
            return null;
        }
        $recent = $frequency === 'weekly' && Backup::where('origin', 'scheduled')->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())->exists();

        return $recent ? null : $this->create('scheduled');
    }

    public function delete(Backup $backup, User $by): void
    {
        $this->audit->log('backups', "Deleted backup {$backup->filename}", $backup, [], $by);
        $this->remove($backup);
    }

    /** Every download is recorded and announced: the file holds the whole database. */
    public function recordDownload(Backup $backup, User $by): void
    {
        $this->audit->log('backups', "Downloaded backup {$backup->filename}", $backup, [], $by);
        $this->notifier->holders('backups.manage', 'Backup downloaded', "{$by->name} downloaded {$backup->filename}.", '/internal/it/backups', except: $by);
    }

    /** @return array{frequency: string, keep: int} */
    public function settings(): array
    {
        return [...self::DEFAULTS, ...(SystemSetting::find(self::SETTINGS_KEY)?->value ?? [])];
    }

    public function saveSettings(string $frequency, int $keep, User $by): void
    {
        $before = $this->settings();
        $after = ['frequency' => $frequency, 'keep' => $keep];
        SystemSetting::updateOrCreate(['key' => self::SETTINGS_KEY], ['value' => $after, 'updated_by' => $by->id]);
        $this->audit->log('backups', 'Changed backup schedule', null, ['before' => $before, 'after' => $after], $by);
    }

    /** A backup still "running" after the time limit was interrupted and will never finish. */
    public function closeInterrupted(): void
    {
        Backup::where('status', 'running')->where('created_at', '<', now()->subSeconds($this->timeout() + 60))
            ->update(['status' => 'failed', 'error' => 'The backup was interrupted before it finished.', 'finished_at' => now()]);
    }

    private function run(string $origin, ?User $by): Backup
    {
        $this->closeInterrupted();
        $backup = Backup::create([
            'filename' => 'banyira-'.now()->format('Y-m-d-His').'-'.Str::lower(Str::random(6)).'.sql.gz',
            'disk' => config('banyira.backups.disk'),
            'status' => 'running', 'origin' => $origin, 'created_by' => $by?->id,
        ]);

        $directory = storage_path('app/private/tmp');
        File::ensureDirectoryExists($directory, 0700);
        $sql = $directory.DIRECTORY_SEPARATOR.Str::uuid().'.sql';
        $archive = $sql.'.gz';

        try {
            set_time_limit($this->timeout() + 60);
            $this->dumper->dump($sql);
            $this->compress($sql, $archive);
            $checksum = hash_file('sha256', $archive);
            $size = filesize($archive);
            $stream = fopen($archive, 'rb');
            try {
                Storage::disk($backup->disk)->writeStream($backup->filename, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
            $backup->update(['status' => 'completed', 'size' => $size, 'checksum' => $checksum, 'finished_at' => now()]);
            $this->audit->log('backups', "Created {$origin} backup {$backup->filename}", $backup, ['size' => $size], $by);
            $this->prune();
        } catch (Throwable $e) {
            report($e);
            $backup->update(['status' => 'failed', 'error' => Str::limit($e->getMessage(), 480), 'finished_at' => now()]);
            $this->audit->log('backups', "Backup failed: {$backup->filename}", $backup, [], $by);
            $this->notifier->holders('backups.manage', 'Backup failed', "The {$origin} backup did not finish. Open Backups to see why.", '/internal/it/backups');
        } finally {
            File::delete([$sql, $archive]);
        }

        return $backup;
    }

    private function compress(string $source, string $target): void
    {
        $in = is_file($source) ? fopen($source, 'rb') : false;
        if (! $in || ! filesize($source)) {
            throw new RuntimeException('The database produced an empty backup.');
        }
        $out = gzopen($target, 'wb6') ?: throw new RuntimeException('The compressed backup could not be written.');
        try {
            while (! feof($in)) {
                if (gzwrite($out, fread($in, 1 << 20)) === false) {
                    throw new RuntimeException('The compressed backup could not be written.');
                }
            }
        } finally {
            fclose($in);
            gzclose($out);
        }
    }

    private function prune(): void
    {
        Backup::where('status', 'completed')->latest('id')->get()->slice($this->settings()['keep'])->each(function (Backup $old) {
            $this->audit->log('backups', "Removed old backup {$old->filename}", $old);
            $this->remove($old);
        });
    }

    private function remove(Backup $backup): void
    {
        Storage::disk($backup->disk)->delete($backup->filename);
        $backup->delete();
    }

    private function timeout(): int
    {
        return config('banyira.backups.timeout_seconds');
    }
}
