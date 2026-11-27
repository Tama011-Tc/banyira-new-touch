<?php

namespace App\Services\Backups;

use App\Contracts\DatabaseDumper;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Runs the database's own dump program. Arguments are passed as a list, so no
 * shell interprets them, and the password goes in the environment, where other
 * users of the machine cannot read it from the process list.
 */
abstract class ProcessDumper implements DatabaseDumper
{
    /** @param array<string, mixed> $connection the parsed database connection */
    public function __construct(protected array $connection, protected string $binary, private int $timeout) {}

    /** @return list<string> */
    abstract protected function command(string $path): array;

    /** @return array<string, string> */
    abstract protected function environment(): array;

    public function dump(string $path): void
    {
        $result = Process::timeout($this->timeout)->env($this->environment())->run($this->command($path));

        if ($result->failed()) {
            throw new RuntimeException(trim($result->errorOutput()) ?: "{$this->binary} stopped with code {$result->exitCode()}.");
        }
    }
}
