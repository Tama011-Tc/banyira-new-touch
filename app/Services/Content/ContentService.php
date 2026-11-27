<?php

namespace App\Services\Content;

use App\Models\Page;
use App\Models\PageVersion;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only place website content changes. Editors work on a draft; publishing
 * copies it to the live page and keeps a version that can be restored.
 */
class ContentService
{
    private const NAV_CACHE = 'site-nav';

    private const SETTINGS_CACHE = 'site-settings';

    public const SETTINGS_KEY = 'site';

    public function __construct(private ContentValidator $validator, private AuditLogger $audit) {}

    public function create(string $slug, string $title, User $by): Page
    {
        $content = $this->validator->validate([
            'title' => $title,
            'blocks' => [['type' => 'page_header', 'data' => ['title' => $title]]],
        ]);
        $page = Page::create(['slug' => $slug, 'draft' => $content, 'updated_by' => $by->id]);
        $this->audit->log('content', "Created page {$page->path()}", $page, [], $by);

        return $page;
    }

    /** @param int $revision the revision the editor started from */
    public function saveDraft(Page $page, array $content, int $revision, User $by): Page
    {
        $clean = $this->validator->validate($content);

        return $this->locked($page, $revision, function (Page $page) use ($clean, $by) {
            $page->update(['draft' => $clean, 'revision' => $page->revision + 1, 'updated_by' => $by->id]);
            $this->audit->log('content', "Saved a draft of {$page->path()}", $page, ['revision' => $page->revision], $by);

            return $page;
        });
    }

    public function publish(Page $page, int $revision, User $by): Page
    {
        return $this->locked($page, $revision, function (Page $page) use ($by) {
            if ($page->state() === 'published') {
                throw ValidationException::withMessages(['page' => 'There are no changes to publish.']);
            }
            try {
                $content = $this->validator->validate($page->draft, forPublishing: true);
            } catch (ValidationException $e) {
                // Listed first, so the confirmation dialog explains where to look.
                throw ValidationException::withMessages(['page' => 'This page is not finished. The sections that need attention are marked in the editor.', ...$e->errors()]);
            }
            $page->update(['published' => $content, 'published_revision' => $page->revision, 'published_at' => now()]);
            $page->versions()->create(['revision' => $page->revision, 'content' => $content, 'published_by' => $by->id]);
            $this->audit->log('content', "Published {$page->path()}", $page, ['revision' => $page->revision], $by);

            return $page;
        });
    }

    public function discardDraft(Page $page, User $by): void
    {
        $this->locked($page, null, function (Page $page) use ($by) {
            if ($page->published === null) {
                throw ValidationException::withMessages(['page' => 'This page has never been published, so there is nothing to go back to.']);
            }
            // The revision only ever moves forward, so an editor holding older text cannot overwrite newer work.
            $next = $page->revision + 1;
            $page->update(['draft' => $page->published, 'revision' => $next, 'published_revision' => $next, 'updated_by' => $by->id]);
            $this->audit->log('content', "Discarded the draft of {$page->path()}", $page, [], $by);
        });
    }

    /** Copies an earlier version into the draft. It goes live only when published. */
    public function restore(Page $page, PageVersion $version, User $by): void
    {
        $this->locked($page, null, function (Page $page) use ($version, $by) {
            $page->update(['draft' => $version->content, 'revision' => $page->revision + 1, 'updated_by' => $by->id]);
            $this->audit->log('content', "Restored an earlier version of {$page->path()} to the draft", $page, ['from_revision' => $version->revision], $by);
        });
    }

    public function unpublish(Page $page, User $by): void
    {
        $this->assertCustom($page, 'Built-in pages cannot be taken down. Edit or hide their sections instead.');
        $this->locked($page, null, function (Page $page) use ($by) {
            $page->update(['published' => null, 'published_revision' => null, 'published_at' => null, 'revision' => $page->revision + 1, 'updated_by' => $by->id]);
            $this->audit->log('content', "Took down {$page->path()}", $page, [], $by);
        });
    }

    public function delete(Page $page, User $by): void
    {
        $this->assertCustom($page, 'Built-in pages cannot be deleted.');
        if ($page->published_revision !== null) {
            throw ValidationException::withMessages(['page' => 'Take the page down before deleting it.']);
        }
        $this->audit->log('content', "Deleted page {$page->path()}", $page, ['content' => $page->draft], $by);
        $page->delete();
        $this->forgetNavigation();
    }

    /**
     * Header and footer links taken from published pages.
     *
     * @return array{header: list<array>, footer: array<string, list<array>>}
     */
    public function navigation(): array
    {
        return Cache::rememberForever(self::NAV_CACHE, function () {
            $pages = Page::published()->get()->sortBy(fn (Page $p) => [$p->published['nav']['order'] ?? 100, $p->id])->values();
            $link = fn (Page $p, string $labelKey) => ['href' => $p->path(), 'label' => ($p->published['nav'][$labelKey] ?? '') ?: $p->published['title']];
            $footer = [];
            foreach (ContentValidator::NAV_COLUMNS as $column) {
                $footer[$column] = $pages->filter(fn (Page $p) => ($p->published['nav']['footer'] ?? '') === $column)->map(fn ($p) => $link($p, 'footer_label'))->values()->all();
            }

            return [
                'header' => $pages->filter(fn (Page $p) => $p->published['nav']['header'] ?? false)->map(fn ($p) => $link($p, 'header_label'))->values()->all(),
                'footer' => $footer,
            ];
        });
    }

    /** Words shown on every public page. */
    public function siteSettings(): array
    {
        return Cache::rememberForever(self::SETTINGS_CACHE, fn () => SystemSetting::find(self::SETTINGS_KEY)?->value ?? SiteDefaults::settings());
    }

    public function saveSiteSettings(array $settings, User $by): void
    {
        $before = $this->siteSettings();
        SystemSetting::updateOrCreate(['key' => self::SETTINGS_KEY], ['value' => $settings, 'updated_by' => $by->id]);
        Cache::forget(self::SETTINGS_CACHE);
        $this->audit->log('content', 'Changed website settings', null, ['before' => $before, 'after' => $settings], $by);
    }

    /** Runs a change against the locked row, refusing it if someone else saved first. */
    private function locked(Page $page, ?int $revision, Closure $change): mixed
    {
        $result = DB::transaction(function () use ($page, $revision, $change) {
            $fresh = Page::whereKey($page->id)->lockForUpdate()->firstOrFail();
            if ($revision !== null && $fresh->revision !== $revision) {
                throw ValidationException::withMessages(['page' => 'Someone else changed this page after you opened it. Reload to see their version.']);
            }

            return $change($fresh);
        });
        $this->forgetNavigation();

        return $result;
    }

    private function forgetNavigation(): void
    {
        Cache::forget(self::NAV_CACHE);
    }

    private function assertCustom(Page $page, string $message): void
    {
        if ($page->is_system) {
            throw ValidationException::withMessages(['page' => $message]);
        }
    }
}
