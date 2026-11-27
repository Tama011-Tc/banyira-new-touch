<?php

namespace App\Services\Backups;

/** Postgres, using pg_dump. Only the application's own schema is copied. */
class PostgresDumper extends ProcessDumper
{
    protected function command(string $path): array
    {
        $c = $this->connection;

        return [
            $this->binary,
            '--format=plain',
            '--no-owner',
            '--no-privileges',
            '--schema='.($c['search_path'] ?? 'public'),
            '--host='.$c['host'],
            '--port='.$c['port'],
            '--username='.$c['username'],
            '--file='.$path,
            $c['database'],
        ];
    }

    protected function environment(): array
    {
        return ['PGPASSWORD' => (string) $this->connection['password'], 'PGSSLMODE' => (string) ($this->connection['sslmode'] ?? 'prefer')];
    }
}
