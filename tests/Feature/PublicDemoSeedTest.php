<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class PublicDemoSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_demo_refuses_missing_or_published_passwords(): void
    {
        config(['banyira.demo.public' => true, 'banyira.demo.staff_password' => DemoSeeder::STAFF_PASSWORD, 'banyira.demo.customer_password' => null]);
        $this->seed(ReferenceDataSeeder::class);

        $this->expectException(RuntimeException::class);
        $this->seed(DemoSeeder::class);
    }

    public function test_public_demo_uses_the_supplied_passwords(): void
    {
        Storage::fake(config('banyira.documents_disk'));
        config(['banyira.demo.public' => true, 'banyira.demo.staff_password' => 'Private-Staff-9911', 'banyira.demo.customer_password' => 'Private-Customer-22']);
        $this->seed(ReferenceDataSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->assertTrue(Hash::check('Private-Customer-22', User::where('email', 'investor@example.com')->value('password')));
        $this->assertTrue(Hash::check('Private-Staff-9911', User::where('email', 'tadala.phiri@banyira.internal')->value('password')));
    }
}
