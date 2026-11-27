<?php

namespace Tests\Feature;

use App\Contracts\DatabaseDumper;
use App\Models\Backup;
use App\Models\Permission;
use App\Notifications\Alert;
use App\Services\Backups\BackupService;
use App\Services\Backups\MysqlDumper;
use App\Services\Backups\PostgresDumper;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class BackupTest extends TestCase
{
    private const SQL = "CREATE TABLE example (id int);\n";

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('backups');
        $this->dumperWrites(self::SQL);
    }

    /** Stands in for the database's dump program. Passing null makes it fail. */
    private function dumperWrites(?string $sql): void
    {
        $this->app->instance(DatabaseDumper::class, new class($sql) implements DatabaseDumper
        {
            public function __construct(private ?string $sql) {}

            public function dump(string $path): void
            {
                if ($this->sql === null) {
                    throw new RuntimeException('mysqldump: command not found');
                }
                file_put_contents($path, $this->sql);
            }
        });
    }

    public function test_only_backup_managers_reach_backups(): void
    {
        foreach (['credit_officer', 'finance_officer', 'executive', 'sales_marketing'] as $role) {
            $u = $this->staff($role);
            $this->asStaff($u)->get('/internal/it/backups')->assertForbidden();
            $this->asStaff($u)->post('/internal/it/backups')->assertForbidden();
        }
    }

    public function test_a_backup_is_compressed_stored_and_recorded(): void
    {
        $this->asStaff($this->staff('it_admin'))->post('/internal/it/backups')->assertSessionHas('success');

        $backup = Backup::firstOrFail();
        $stored = Storage::disk('backups')->get($backup->filename);
        $this->assertSame(['completed', 'manual'], [$backup->status, $backup->origin]);
        $this->assertSame(self::SQL, gzdecode($stored));
        $this->assertSame(hash('sha256', $stored), $backup->checksum);
        $this->assertDatabaseHas('audit_logs', ['area' => 'backups', 'subject_id' => $backup->id]);
    }

    public function test_a_failed_backup_is_reported_and_leaves_no_file(): void
    {
        $admin = $this->staff('it_admin');
        $this->dumperWrites(null);

        $this->asStaff($admin)->post('/internal/it/backups')->assertSessionHas('error');

        $this->assertSame('failed', Backup::firstOrFail()->status);
        $this->assertStringContainsString('command not found', Backup::firstOrFail()->error);
        $this->assertSame([], Storage::disk('backups')->allFiles());
        Notification::assertSentTo($admin, Alert::class);
    }

    public function test_an_empty_dump_is_not_accepted(): void
    {
        $this->dumperWrites('');

        $this->assertSame('failed', app(BackupService::class)->create('scheduled')->status);
    }

    public function test_downloading_needs_the_password_and_a_short_lived_link_of_ones_own(): void
    {
        $admin = $this->staff('it_admin');
        $other = $this->staff('it_admin');
        $backup = app(BackupService::class)->create('manual', $admin);
        $url = "/internal/it/backups/{$backup->id}";

        $this->asStaff($admin)->get("{$url}/file")->assertForbidden();
        $this->asStaff($admin)->post("{$url}/download", ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->asStaff($admin)->post("{$url}/download", ['password' => 'Staff#Pass2026'])->assertSessionHas('download');
        $link = session('download');

        $this->asStaff($other)->get($link)->assertForbidden();
        $this->asStaff($admin)->get($link)->assertOk()->assertDownload($backup->filename);
        $this->assertDatabaseHas('audit_logs', ['area' => 'backups', 'action' => "Downloaded backup {$backup->filename}"]);
        Notification::assertSentTo($other, Alert::class);

        $this->travel(2)->minutes();
        $this->asStaff($admin)->get($link)->assertForbidden();
    }

    public function test_only_the_newest_backups_are_kept(): void
    {
        $admin = $this->staff('it_admin');
        $this->asStaff($admin)->put('/internal/it/backups/settings', ['frequency' => 'daily', 'keep' => 2])->assertSessionHasNoErrors();
        $service = app(BackupService::class);

        $files = collect(range(1, 4))->map(fn () => $service->create('manual', $admin)->filename);

        $this->assertSame($files->slice(2)->values()->all(), Backup::orderBy('id')->pluck('filename')->all());
        $this->assertEqualsCanonicalizing($files->slice(2)->values()->all(), Storage::disk('backups')->allFiles());
    }

    public function test_the_schedule_is_followed(): void
    {
        $admin = $this->staff('it_admin');
        $service = app(BackupService::class);

        $this->assertNull($service->runScheduled());

        $service->saveSettings('weekly', 10, $admin);
        $this->assertNotNull($service->runScheduled());
        $this->travel(3)->days();
        $this->assertNull($service->runScheduled());
        $this->travel(4)->days();
        $this->assertNotNull($service->runScheduled());

        $service->saveSettings('daily', 10, $admin);
        $this->travel(1)->days();
        $this->assertNotNull($service->runScheduled());
    }

    public function test_a_role_holder_can_be_given_backups_only_without_approval_powers(): void
    {
        $this->asStaff($this->staff('it_admin'))->post('/internal/it/roles', [
            'name' => 'Backup Operator', 'permissions' => Permission::whereIn('slug', ['backups.manage', 'payroll.view'])->pluck('id')->all(),
        ])->assertSessionHasErrors('permissions');
    }

    public function test_database_passwords_are_kept_off_the_command_line(): void
    {
        Process::fake();
        $connection = ['host' => 'db.example', 'port' => 5432, 'username' => 'app', 'password' => 's3cret', 'database' => 'banyira', 'unix_socket' => '', 'search_path' => 'banyira'];

        foreach ([new MysqlDumper($connection, 'mysqldump', 60), new PostgresDumper($connection, 'pg_dump', 60)] as $dumper) {
            $dumper->dump('/tmp/out.sql');
        }

        Process::assertRanTimes(fn ($process) => ! str_contains(implode(' ', $process->command), 's3cret') && in_array('s3cret', $process->environment, true), 2);
    }

    public function test_a_failing_dump_program_raises_its_message(): void
    {
        Process::fake(['*' => Process::result(errorOutput: 'Access denied', exitCode: 2)]);

        $this->expectExceptionMessage('Access denied');
        (new MysqlDumper(['host' => 'h', 'port' => 1, 'username' => 'u', 'password' => 'p', 'database' => 'd'], 'mysqldump', 60))->dump('/tmp/out.sql');
    }
}
