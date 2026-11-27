<?php

namespace App\Services\Backups;

use Pdo\Mysql;

/** MySQL, MariaDB and MySQL-compatible services, using mysqldump or mariadb-dump. */
class MysqlDumper extends ProcessDumper
{
    protected function command(string $path): array
    {
        $c = $this->connection;
        $ca = $c['options'][Mysql::ATTR_SSL_CA] ?? null;

        return array_values(array_filter([
            $this->binary,
            '--single-transaction',          // a consistent copy without locking the tables
            '--no-tablespaces',
            '--hex-blob',
            '--default-character-set=utf8mb4',
            empty($c['unix_socket']) ? '--host='.$c['host'] : '--socket='.$c['unix_socket'],
            empty($c['unix_socket']) ? '--port='.$c['port'] : null,
            '--user='.$c['username'],
            $ca ? '--ssl-ca='.$ca : null,
            '--result-file='.$path,
            $c['database'],
        ]));
    }

    protected function environment(): array
    {
        return ['MYSQL_PWD' => (string) $this->connection['password']];
    }
}
