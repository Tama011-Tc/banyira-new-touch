<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageVersion;
use App\Rules\SafeLink;
use App\Services\Content\BlockCatalogue;
use App\Services\Content\ContentService;
use App\Services\Content\ContentValidator;
use App\Services\Content\PageRenderer;
use App\Support\TableQuery;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/** Website content: pages, their drafts and publishing, and site-wide wording. */
class ContentController extends Controller
{
    public function __construct(private ContentService $content) {}

    public function index(Request $request)
    {
        $table = TableQuery::for(Page::with('editor:id,name'), $request)
            ->search(['title', 'slug'])
            ->filter('type', fn ($q, $v) => $q->where('is_system', $v === 'builtin'), ['builtin', 'custom'])
            ->sort(['address' => 'slug', 'updated' => 'updated_at'], 'address', 'asc');

        return Inertia::render('Staff/Content/Pages', [
            'pages' => $table->paginate()->through(fn (Page $p) => [
                'id' => $p->id, 'title' => $p->title, 'path' => $p->path(), 'isSystem' => $p->is_system, 'state' => $p->state(),
                'updatedAt' => $p->updated_at, 'updatedBy' => $p->editor?->name,
            ]),
            'query' => $table->state(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', Rule::unique(Page::class, 'slug')],
        ], ['slug.regex' => 'Use lower-case letters, numbers and single hyphens, for example "our-partners".']);
        $page = $this->content->create($data['slug'], $data['title'], $request->user());

        return redirect("/internal/content/pages/{$page->id}")->with('success', 'Page created. Publish it when it is ready.');
    }

    public function edit(Page $page)
    {
        return Inertia::render('Staff/Content/Editor', [
            'page' => [
                'id' => $page->id, 'slug' => $page->slug, 'path' => $page->path(), 'isSystem' => $page->is_system, 'state' => $page->state(),
                'revision' => $page->revision, 'content' => $page->draft, 'publishedAt' => $page->published_at,
            ],
            'versions' => $page->versions()->with('publisher:id,name')->latest('revision')->limit(20)->get()
                ->map(fn (PageVersion $v) => ['id' => $v->id, 'title' => $v->content['title'], 'by' => $v->publisher?->name, 'at' => $v->created_at]),
            'catalogue' => BlockCatalogue::all(),
            'footerColumns' => ContentValidator::NAV_COLUMNS,
            'media' => Media::latest('id')->get()->map->present(),
        ]);
    }

    public function update(Request $request, Page $page)
    {
        $data = $request->validate(['content' => ['required', 'array'], 'revision' => ['required', 'integer']]);
        $this->content->saveDraft($page, $data['content'], (int) $data['revision'], $request->user());

        return back()->with('success', 'Draft saved. Visitors still see the published page.');
    }

    public function preview(Page $page, PageRenderer $renderer)
    {
        return Inertia::render('Public/Page', [...$renderer->props($page->draft), 'preview' => "/internal/content/pages/{$page->id}"]);
    }

    public function publish(Request $request, Page $page)
    {
        $data = $request->validate(['revision' => ['required', 'integer']]);
        $this->content->publish($page, (int) $data['revision'], $request->user());

        return back()->with('success', 'Published. The website now shows this version.');
    }

    public function discard(Request $request, Page $page)
    {
        $this->content->discardDraft($page, $request->user());

        return back()->with('success', 'Draft discarded.');
    }

    public function restore(Request $request, Page $page, PageVersion $version)
    {
        abort_unless($version->page_id === $page->id, 404);
        $this->content->restore($page, $version, $request->user());

        return back()->with('success', 'That version is now the draft. Publish it to put it live.');
    }

    public function unpublish(Request $request, Page $page)
    {
        $this->content->unpublish($page, $request->user());

        return back()->with('success', 'The page is no longer on the website.');
    }

    public function destroy(Request $request, Page $page)
    {
        $this->content->delete($page, $request->user());

        return redirect('/internal/content')->with('success', 'Page deleted.');
    }

    public function settings()
    {
        return Inertia::render('Staff/Content/Settings', ['settings' => $this->content->siteSettings()]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'announcement.enabled' => ['required', 'boolean'],
            'announcement.text' => ['nullable', 'required_if:announcement.enabled,true', 'string', 'max:200'],
            'announcement.link' => ['nullable', 'string', 'max:255', new SafeLink],
            'footer_about' => ['required', 'string', 'max:400'],
            'footer_location' => ['required', 'string', 'max:120'],
            'footer_notice' => ['required', 'string', 'max:300'],
            // The risk statement is shown on every page and cannot be removed.
            'risk_statement' => ['required', 'string', 'min:80', 'max:600'],
        ]);
        $data['announcement'] = [
            'enabled' => (bool) $data['announcement']['enabled'],
            'text' => $data['announcement']['text'] ?? '',
            'link' => $data['announcement']['link'] ?? '',
        ];
        $this->content->saveSiteSettings($data, $request->user());

        return back()->with('success', 'Website settings saved. The change is live.');
    }
}
