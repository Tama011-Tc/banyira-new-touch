<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LoanProduct;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    public function test_public_pages_load(): void
    {
        foreach (['/', '/products', '/products/equipment', '/how-to-get-a-loan', '/how-to-invest', '/about', '/contact', '/legal', '/login', '/register', '/register/business', '/register/investor'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_products_come_from_the_database(): void
    {
        LoanProduct::where('slug', 'equipment')->update(['name' => 'Renamed Equipment Product']);
        $this->get('/products/equipment')->assertOk()->assertSee('Renamed Equipment Product');

        LoanProduct::where('slug', 'equipment')->update(['is_active' => false]);
        $this->get('/products/equipment')->assertNotFound();
    }

    public function test_public_site_never_links_to_the_employee_portal(): void
    {
        foreach (['/', '/about', '/contact', '/how-to-invest'] as $url) {
            $this->get($url)->assertDontSee('internal-access');
        }
    }

    public function test_contact_form_creates_a_lead(): void
    {
        $this->post('/contact', ['name' => 'Ada', 'email' => 'ada@example.com', 'type' => 'investor', 'message' => 'Hello'])->assertRedirect();
        $this->assertDatabaseHas('leads', ['email' => 'ada@example.com', 'source' => 'Website contact form']);
        $this->assertSame(1, Lead::count());
    }
}
