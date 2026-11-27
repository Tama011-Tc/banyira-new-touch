<?php

namespace App\Contracts;

/** Writes the whole application database to a file of SQL that the database's own tools can restore. */
interface DatabaseDumper
{
    /** @throws \RuntimeException when the dump cannot be completed */
    public function dump(string $path): void;
}
