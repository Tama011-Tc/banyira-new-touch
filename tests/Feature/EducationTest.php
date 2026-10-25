<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\EducationArticle;
use Database\Seeders\EducationSeeder;
use Tests\TestCase;

class EducationTest extends TestCase
{
    private function article(array $o = []): EducationArticle
    {
        return EducationArticle::create($o + ['slug' => 'a-'.uniqid(), 'category' => 'cash_flow', 'title_en' => 'Cash', 'body_en' => str_repeat('Write down money in and out. ', 3), 'published' => true]);
    }

    public function test_seeded_lessons_cover_the_required_topics_with_a_disclaimer(): void
    {
        $this->seed(EducationSeeder::class);
        $this->assertSame(8, EducationArticle::where('published', true)->count());
        $all = EducationArticle::get()->map(fn ($a) => $a->title_en.' '.$a->body_en)->implode(' ');
        foreach (['cash', 'bookkeeping', 'personal', 'interest', 'repay', 'expenses', 'records'] as $topic) {
            $this->assertStringContainsStringIgnoringCase($topic, $all, $topic);
        }
        $this->assertStringContainsString('not legal, tax or investment advice', EducationArticle::where('slug', 'interest-explained')->value('body_en'));
        $this->assertNull(EducationArticle::whereNotNull('title_ny')->first(), 'no machine-written Chichewa is shipped');
    }

    public function test_signed_in_users_read_published_lessons_and_guests_cannot(): void
    {
        $a = $this->article();
        $draft = $this->article(['published' => false, 'slug' => 'secret-draft']);
        $this->get('/learn')->assertRedirect('/login');
        foreach ([Role::BusinessOwner, Role::Investor, Role::Sales] as $r) {
            $this->actingAs($this->user($r))->get('/learn')->assertOk()->assertInertia(fn ($p) => $p->has('articles', 1));
            $this->actingAs($this->user($r))->get("/learn/{$a->slug}")->assertOk();
            $this->actingAs($this->user($r))->get("/learn/{$draft->slug}")->assertNotFound();
        }
    }

    public function test_chichewa_falls_back_to_english_until_translated(): void
    {
        $en = $this->article(['slug' => 'only-english']);
        $both = $this->article(['slug' => 'both', 'title_ny' => 'Ndalama', 'body_ny' => str_repeat('Lembani ndalama. ', 3)]);
        $u = $this->user(Role::BusinessOwner);
        $this->actingAs($u)->post('/locale', ['locale' => 'ny'])->assertSessionHasNoErrors();
        $this->assertSame('ny', $u->fresh()->locale);
        $this->actingAs($u->fresh())->get("/learn/{$both->slug}")->assertInertia(fn ($p) => $p->where('article.title', 'Ndalama')->where('article.translated', true));
        $this->actingAs($u->fresh())->get("/learn/{$en->slug}")->assertInertia(fn ($p) => $p->where('article.title', 'Cash')->where('article.translated', false));
        $this->actingAs($u)->post('/locale', ['locale' => 'fr'])->assertSessionHasErrors('locale');
    }

    public function test_only_it_and_support_manage_content_and_nobody_else(): void
    {
        $body = ['category' => 'cash_flow', 'title_en' => 'New lesson', 'body_en' => str_repeat('Some helpful, plain advice. ', 3), 'published' => true];
        foreach ([Role::Investor, Role::BusinessOwner, Role::Sales, Role::CreditAnalyst, Role::CreditApprover, Role::Finance, Role::Executive, Role::Compliance] as $r) {
            $this->actingAs($this->user($r))->get('/learn-manage')->assertForbidden();
            $this->actingAs($this->user($r))->post('/learn-manage', $body)->assertForbidden();
        }
        foreach ([Role::ItAdmin, Role::Support] as $r) {
            $this->actingAs($this->user($r))->get('/learn-manage')->assertOk();
        }
        $admin = $this->user(Role::ItAdmin);
        $this->actingAs($admin)->post('/learn-manage', $body)->assertSessionHasNoErrors();
        $a = EducationArticle::first();
        $this->assertSame('new-lesson', $a->slug);
        $this->assertDatabaseHas('audit_logs', ['action' => 'education.created', 'actor_id' => $admin->id]);
        $this->actingAs($admin)->post('/learn-manage', $body);
        $this->assertSame('new-lesson-2', EducationArticle::latest('id')->first()->slug);          // slugs never collide
        $this->actingAs($admin)->put("/learn-manage/{$a->id}", ['title_en' => 'Renamed'] + $body)->assertSessionHasNoErrors();
        $this->assertSame('Renamed', $a->fresh()->title_en);
        $this->actingAs($admin)->delete("/learn-manage/{$a->id}")->assertSessionHasNoErrors();
        $this->assertNull(EducationArticle::find($a->id));
    }

    public function test_content_is_validated_and_markup_is_stripped(): void
    {
        $admin = $this->actingAs($this->user(Role::ItAdmin));
        $admin->post('/learn-manage', ['category' => 'nope', 'title_en' => '', 'body_en' => 'short'])->assertSessionHasErrors(['category', 'title_en', 'body_en']);
        $admin->post('/learn-manage', ['category' => 'cash_flow', 'title_en' => '<b>Bold</b> title', 'body_en' => '<script>alert(1)</script>Plain advice that is long enough to be accepted here.', 'published' => true])->assertSessionHasNoErrors();
        $a = EducationArticle::first();
        $this->assertSame('Bold title', $a->title_en);
        $this->assertStringNotContainsString('<script>', $a->body_en);
    }

    public function test_it_admin_still_cannot_reach_money_through_the_content_role(): void
    {
        $this->assertFalse(Role::Support->can('finance.approve'));
        $this->assertFalse(Role::Support->can('credit.approve'));
        $this->actingAs($this->user(Role::Support))->get('/finance')->assertForbidden();
    }
}
