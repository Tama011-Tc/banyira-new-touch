<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Page;
use App\Services\Content\BlockCatalogue;
use Database\Seeders\ContentSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ContentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('media');
    }

    private function page(string $slug): Page
    {
        return Page::where('slug', $slug)->firstOrFail();
    }

    /** The page's draft with the heading of its first section changed. */
    private function retitled(Page $page, string $title): array
    {
        $content = $page->draft;
        $content['blocks'][0]['data']['title'] = $title;

        return $content;
    }

    private function save($editor, Page $page, array $content, ?int $revision = null)
    {
        return $this->asStaff($editor)->put("/internal/content/pages/{$page->id}", ['content' => $content, 'revision' => $revision ?? $page->revision]);
    }

    private function publish($editor, Page $page)
    {
        return $this->asStaff($editor)->post("/internal/content/pages/{$page->id}/publish", ['revision' => $page->refresh()->revision]);
    }

    public function test_only_staff_with_content_permission_reach_the_website_editor(): void
    {
        $page = $this->page('about');
        $credit = $this->staff('credit_officer');

        $this->asStaff($credit)->get('/internal/content')->assertForbidden();
        $this->asStaff($credit)->get("/internal/content/pages/{$page->id}/preview")->assertForbidden();
        $this->save($credit, $page, $page->draft)->assertForbidden();
        $this->publish($credit, $page)->assertForbidden();

        $this->asStaff($this->staff('sales_marketing'))->get('/internal/content')->assertOk();
    }

    public function test_a_draft_reaches_visitors_only_after_publishing(): void
    {
        $editor = $this->staff('sales_marketing');
        $page = $this->page('about');

        $this->save($editor, $page, $this->retitled($page, 'A new About heading'))->assertSessionHasNoErrors();
        $this->get('/about')->assertOk()->assertDontSee('A new About heading');
        $this->asStaff($editor)->get("/internal/content/pages/{$page->id}/preview")->assertOk()->assertSee('A new About heading');
        $this->assertSame('changes', $page->refresh()->state());

        $this->publish($editor, $page)->assertSessionHasNoErrors();
        $this->get('/about')->assertSee('A new About heading');
        $this->assertSame('published', $page->refresh()->state());
        $this->assertDatabaseHas('audit_logs', ['area' => 'content', 'action' => 'Published /about']);
    }

    public function test_hidden_sections_are_never_sent_to_visitors(): void
    {
        $editor = $this->staff('sales_marketing');
        $page = $this->page('about');
        $content = $page->draft;
        $count = count($content['blocks']);
        $content['blocks'][1]['visible'] = false;
        $content['blocks'][1]['data']['heading'] = 'Secret unfinished section';

        $this->save($editor, $page, $content);
        $this->publish($editor, $page);

        $this->get('/about')->assertDontSee('Secret unfinished section')
            ->assertInertia(fn (AssertableInertia $p) => $p->has('page.blocks', $count - 1));
    }

    public function test_an_editor_cannot_overwrite_a_change_they_have_not_seen(): void
    {
        $editor = $this->staff('sales_marketing');
        $page = $this->page('about');
        $opened = $page->revision;

        $this->save($editor, $page, $this->retitled($page, 'First'), $opened);
        $this->save($editor, $page, $this->retitled($page, 'Second'), $opened)->assertSessionHasErrors('page');

        $this->assertSame('First', $page->refresh()->draft['blocks'][0]['data']['title']);
    }

    public function test_unsafe_links_and_unknown_sections_are_refused(): void
    {
        $editor = $this->staff('sales_marketing');
        $page = $this->page('about');

        foreach (['javascript:alert(1)', '//evil.example', '/\\evil.example', 'http://insecure.example', '/internal-access', '/internal/finance'] as $href) {
            $content = $page->draft;
            $content['blocks'][0]['data']['buttons'] = [['label' => 'Go', 'href' => $href]];
            $this->save($editor, $page, $content)->assertSessionHasErrors('blocks.0.data.buttons.0.href');
        }

        $content = $page->draft;
        $content['blocks'][] = ['type' => 'raw_html', 'data' => ['html' => '<script></script>']];
        $this->save($editor, $page, $content)->assertSessionHasErrors();
        $this->assertSame(1, $page->refresh()->revision);
    }

    public function test_a_draft_may_be_unfinished_but_cannot_be_published_that_way(): void
    {
        $editor = $this->staff('sales_marketing');
        $page = $this->page('about');

        $this->save($editor, $page, $this->retitled($page, ''))->assertSessionHasNoErrors();
        $this->publish($editor, $page)->assertSessionHasErrors('blocks.0.data.title');
        $this->get('/about')->assertSee('Making business finance in Malawi');
    }

    public function test_an_earlier_version_can_be_restored(): void
    {
        $editor = $this->staff('sales_marketing');
        $page = $this->page('about');
        $original = $page->versions()->firstOrFail();
        $this->save($editor, $page, $this->retitled($page, 'Changed heading'));
        $this->publish($editor, $page);

        $this->asStaff($editor)->post("/internal/content/pages/{$page->id}/versions/{$original->id}/restore")->assertSessionHasNoErrors();
        $this->get('/about')->assertSee('Changed heading');
        $this->publish($editor, $page);
        $this->get('/about')->assertSee('Making business finance in Malawi')->assertDontSee('Changed heading');

        $other = $this->page('contact')->versions()->firstOrFail();
        $this->asStaff($editor)->post("/internal/content/pages/{$page->id}/versions/{$other->id}/restore")->assertNotFound();
    }

    public function test_a_new_page_can_be_published_listed_in_the_menu_and_removed(): void
    {
        $editor = $this->staff('sales_marketing');

        $this->asStaff($editor)->post('/internal/content/pages', ['title' => 'Our partners', 'slug' => 'Our Partners'])->assertSessionHasErrors('slug');
        $this->asStaff($editor)->post('/internal/content/pages', ['title' => 'Our partners', 'slug' => 'partners'])->assertSessionHasNoErrors();
        $page = $this->page('partners');
        $this->get('/pages/partners')->assertNotFound();

        $content = $page->draft;
        $content['nav'] = ['header' => true, 'header_label' => 'Partners', 'footer' => 'company', 'footer_label' => '', 'order' => 5];
        $this->save($editor, $page, $content);
        $this->publish($editor, $page)->assertSessionHasNoErrors();

        $this->get('/pages/partners')->assertOk()->assertInertia(fn (AssertableInertia $p) => $p
            ->where('site.nav.header.0', ['href' => '/pages/partners', 'label' => 'Partners'])
            ->where('site.nav.footer.company.0.label', 'Our partners'));

        $this->asStaff($editor)->delete("/internal/content/pages/{$page->id}")->assertSessionHasErrors('page');
        $this->asStaff($editor)->post("/internal/content/pages/{$page->id}/unpublish")->assertSessionHasNoErrors();
        $this->get('/pages/partners')->assertNotFound();
        $this->get('/')->assertInertia(fn (AssertableInertia $p) => $p->where('site.nav.header.0.href', '/how-to-get-a-loan'));
        $this->asStaff($editor)->delete("/internal/content/pages/{$page->id}")->assertSessionHasNoErrors();
        $this->assertModelMissing($page);
    }

    public function test_built_in_pages_cannot_be_removed(): void
    {
        $editor = $this->staff('sales_marketing');
        $page = $this->page('legal');

        $this->asStaff($editor)->post("/internal/content/pages/{$page->id}/unpublish")->assertSessionHasErrors('page');
        $this->asStaff($editor)->delete("/internal/content/pages/{$page->id}")->assertSessionHasErrors('page');
        $this->get('/legal')->assertOk();
        $this->get('/pages/legal')->assertNotFound();
    }

    public function test_every_section_type_can_be_added_blank_and_saved(): void
    {
        $editor = $this->staff('sales_marketing');
        $page = $this->page('about');
        $content = $page->draft;
        $content['blocks'] = array_map(fn ($type) => ['type' => $type, 'data' => []], BlockCatalogue::types());

        $this->save($editor, $page, $content)->assertSessionHasNoErrors();
        $this->asStaff($editor)->get("/internal/content/pages/{$page->id}")->assertOk();
    }

    public function test_images_are_checked_and_kept_while_a_page_uses_them(): void
    {
        $editor = $this->staff('sales_marketing');

        $this->asStaff($editor)->post('/internal/content/media', ['file' => UploadedFile::fake()->create('x.svg', 4, 'image/svg+xml'), 'alt' => 'Vector'])->assertSessionHasErrors('file');
        $this->asStaff($editor)->post('/internal/content/media', ['file' => UploadedFile::fake()->image('shop.jpg', 800, 600), 'alt' => ''])->assertSessionHasErrors('alt');
        $this->asStaff($editor)->post('/internal/content/media', ['file' => UploadedFile::fake()->image('shop.jpg', 800, 600), 'alt' => 'A shop front'])->assertSessionHasNoErrors();
        $media = Media::firstOrFail();
        $this->get("/media/{$media->id}")->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        $page = $this->page('about');
        $content = $page->draft;
        $content['blocks'][] = ['type' => 'image', 'data' => ['media_id' => $media->id, 'caption' => 'Our first shop']];
        $this->save($editor, $page, $content)->assertSessionHasNoErrors();
        $this->asStaff($editor)->delete("/internal/content/media/{$media->id}")->assertSessionHasErrors('media');

        $unused = $this->asStaff($editor)->post('/internal/content/media', ['file' => UploadedFile::fake()->image('b.png'), 'alt' => 'Spare']);
        $unused->assertSessionHasNoErrors();
        $spare = Media::where('alt', 'Spare')->firstOrFail();
        $this->asStaff($editor)->delete("/internal/content/media/{$spare->id}")->assertSessionHasNoErrors();
        Storage::disk('media')->assertMissing($spare->path);
    }

    public function test_site_wording_changes_and_the_risk_statement_cannot_be_removed(): void
    {
        $editor = $this->staff('sales_marketing');
        $settings = [
            'announcement' => ['enabled' => true, 'text' => 'Offices closed on Monday', 'link' => '/contact'],
            'footer_about' => 'About us.', 'footer_location' => 'Blantyre, Malawi', 'footer_notice' => 'No guarantee.',
            'risk_statement' => '',
        ];

        $this->asStaff($editor)->put('/internal/content/settings', $settings)->assertSessionHasErrors('risk_statement');
        $this->asStaff($editor)->put('/internal/content/settings', [...$settings, 'risk_statement' => str_repeat('Investors can lose money. ', 5)])->assertSessionHasNoErrors();

        $this->get('/')->assertInertia(fn (AssertableInertia $p) => $p
            ->where('site.settings.footer_location', 'Blantyre, Malawi')
            ->where('site.settings.announcement.text', 'Offices closed on Monday'));
    }

    public function test_reseeding_keeps_edited_pages(): void
    {
        $editor = $this->staff('sales_marketing');
        $page = $this->page('about');
        $this->save($editor, $page, $this->retitled($page, 'Kept heading'));
        $this->publish($editor, $page);

        $this->seed(ContentSeeder::class);

        $this->get('/about')->assertSee('Kept heading');
    }
}
