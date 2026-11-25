<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ReferenceDataSeeder::class);

        if (app()->environment('local') || config('banyira.demo.public')) {
            $this->call(DemoSeeder::class);
        }
    }
}
