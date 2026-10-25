<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

/** Walks the seeded demo as each role and renders every screen, so a missing page or broken query fails loudly. */
class PagesRenderTest extends TestCase
{
    public function test_every_role_can_open_every_screen_it_is_offered(): void
    {
        $this->seed(DatabaseSeeder::class);
        $inv = User::where('email', 'investor@banyira.test')->first();
        $agreement = \App\Models\LoanAgreement::where('investor_id', $inv->id)->first();
        $conversation = \App\Models\Conversation::where('investor_id', $inv->id)->first();
        $listing = \App\Models\Listing::where('status', 'published')->first();
        $app = \App\Models\Application::where('status', 'assessment')->first();
        $approved = \App\Models\Application::where('status', 'approved')->first();

        $screens = [
            'investor@banyira.test' => ['/marketplace', "/marketplace/{$listing->id}", '/watchlist', '/portfolio', '/investor/profile', '/sms-inbox', '/messages', "/messages/{$conversation->id}", '/agreements', "/agreements/{$agreement->id}", '/complaints', '/how-it-works'],
            'business@banyira.test' => ['/dashboard', '/business', '/applications', '/applications/create', '/messages', '/agreements', '/notifications', '/sms-inbox'],
            'sales@banyira.test' => ['/sales', '/sales/register', '/applications', '/notifications', '/sales/leads/'.User::where('email', 'lead@banyira.test')->value('id'), '/sales/register?lead='.User::where('email', 'lead@banyira.test')->value('id')],
            'analyst@banyira.test' => ['/credit', '/credit-policy', '/credit?mine=1', "/credit/{$app->id}", '/notifications'],
            'approver@banyira.test' => ['/credit', '/credit-policy', "/credit/{$app->id}", '/listings', "/listings/prepare/{$approved->id}", '/agreements'],
            'executive@banyira.test' => ['/executive', '/credit', '/listings', '/agreements', '/finance', '/finance/ledger', '/compliance', '/audit'],
            'finance@banyira.test' => ['/finance', '/finance/ledger', '/agreements', "/agreements/{$agreement->id}", '/audit'],
            'compliance@banyira.test' => ['/compliance', '/moderation', '/finance', '/audit'],
            'admin@banyira.test' => ['/admin/users', '/admin/settings', '/admin/health', '/audit', '/executive', '/credit', "/credit/{$app->id}", '/listings', '/agreements', "/agreements/{$agreement->id}", '/finance', '/finance/ledger', '/moderation', '/complaints', '/marketplace', '/sms-log'],
            'support@banyira.test' => ['/moderation', '/complaints'],
        ];
        foreach ($screens as $email => $urls) {
            $user = User::where('email', $email)->firstOrFail();
            foreach ($urls as $url) {
                $this->actingAs($user)->get($url)->assertOk("$email $url")->assertInertia(fn ($p) => $p->has('nav'));
            }
        }
        $this->get('/')->assertOk();
    }

    public function test_the_seeded_demo_tells_the_whole_story(): void
    {
        $this->seed(DatabaseSeeder::class);
        $statuses = \App\Models\Application::pluck('status')->all();
        foreach (['submitted', 'assessment', 'approved', 'published', 'repayment_active', 'overdue', 'rejected'] as $s) {
            $this->assertContains($s, $statuses, "demo should include an application in $s");
        }
        $this->assertSame(0, \App\Models\LedgerEntry::where('simulated', false)->count());
        $this->assertSame(0, array_sum(app(\App\Services\LedgerService::class)->trialBalance()));
        $this->assertTrue(app(\App\Services\AuditLogger::class)->verifyChain());
        $this->assertTrue(\App\Models\LoanAgreement::where('simulated', false)->doesntExist());
        $this->assertSame('simulation', \App\Services\Platform::mode());
    }
}
