<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * The few SQL expressions that MySQL and Postgres spell differently.
 * Arguments are column names written in code, never user input.
 */
final class Sql
{
    /** "YYYY-MM" of a date or timestamp column. */
    public static function month(string $column): string
    {
        return self::isPostgres()
            ? "TO_CHAR({$column}, 'YYYY-MM')"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    /** Whole hours from one timestamp column to another. */
    public static function hoursBetween(string $from, string $to): string
    {
        return self::isPostgres()
            ? "TRUNC(EXTRACT(EPOCH FROM ({$to} - {$from})) / 3600)"
            : "TIMESTAMPDIFF(HOUR, {$from}, {$to})";
    }

    private static function isPostgres(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }
}
