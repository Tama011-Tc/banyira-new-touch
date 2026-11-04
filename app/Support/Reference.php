<?php

namespace App\Support;

use Illuminate\Support\Str;

final class Reference
{
    /** Human-friendly unique reference, e.g. APP-7K2Q9M. */
    public static function make(string $prefix): string
    {
        return $prefix.'-'.strtoupper(Str::random(6));
    }
}
