<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateAdmin extends Command
{
    protected $signature = 'banyira:create-admin {email} {--name=Administrator} {--password= : Omit to generate one}';

    protected $description = 'Create an IT administrator account. This is how the very first admin is made; the admin then creates every other staff account in the app.';

    public function handle(AuditLogger $audit): int
    {
        $email = strtolower($this->argument('email'));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || User::where('email', $email)->exists()) {
            $this->error('That email is invalid or already in use.');

            return self::FAILURE;
        }
        $generated = ! $this->option('password');
        $password = $this->option('password') ?: Str::password(16, symbols: false);
        if (strlen($password) < 12) {
            $this->error('Use a password of at least 12 characters.');

            return self::FAILURE;
        }
        $u = new User(['name' => $this->option('name'), 'email' => $email, 'password' => $password]);
        $u->forceFill(['role' => Role::ItAdmin, 'status' => 'active', 'email_verified_at' => now(), 'must_change_password' => true])->save();
        $audit->record('admin.bootstrap_created', $u, ['via' => 'console']);
        $this->info("IT administrator created: {$email}");
        if ($generated) {
            $this->line("Temporary password (shown once): {$password}");
        }
        $this->line('They must change the password at first sign-in.');

        return self::SUCCESS;
    }
}
