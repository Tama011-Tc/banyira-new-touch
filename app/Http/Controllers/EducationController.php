<?php

namespace App\Http\Controllers;

use App\Models\EducationArticle;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EducationController extends Controller
{
    public const CATEGORIES = ['cash_flow' => 'Cash flow', 'bookkeeping' => 'Bookkeeping', 'separate_finances' => 'Business and personal money', 'borrowing' => 'Borrowing and interest', 'repayment' => 'Repayment', 'expenses' => 'Expenses', 'records' => 'Record keeping'];

    public function index(Request $request)
    {
        $locale = $request->user()->locale;

        return Inertia::render('learn/Index', [
            'articles' => EducationArticle::where('published', true)->orderBy('position')->orderBy('id')->get()->map(fn ($a) => ['slug' => $a->slug, 'category' => self::CATEGORIES[$a->category] ?? $a->category] + collect($a->localised($locale))->only(['title', 'translated'])->all() + ['summary' => Str::limit(strtok($a->localised($locale)['body'], "\n"), 140)]),
            'locale' => $locale,
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $a = EducationArticle::where('published', true)->where('slug', $slug)->firstOrFail();

        return Inertia::render('learn/Show', ['article' => ['slug' => $a->slug, 'category' => self::CATEGORIES[$a->category] ?? $a->category] + $a->localised($request->user()->locale)]);
    }

    public function locale(Request $request)
    {
        $request->user()->forceFill(['locale' => $request->validate(['locale' => 'required|in:en,ny'])['locale']])->save();

        return back();
    }

    // ---- content management (support and IT, never financial data)
    public function manage()
    {
        return Inertia::render('learn/Manage', ['articles' => EducationArticle::orderBy('position')->orderBy('id')->get(), 'categories' => self::CATEGORIES]);
    }

    private function rules(?EducationArticle $a = null): array
    {
        return [
            'category' => ['required', Rule::in(array_keys(self::CATEGORIES))], 'title_en' => 'required|string|max:160', 'body_en' => 'required|string|min:40|max:8000',
            'title_ny' => 'nullable|string|max:160', 'body_ny' => 'nullable|string|max:8000', 'published' => 'boolean', 'position' => 'nullable|integer|min:0|max:999',
        ];
    }

    /** Content is plain text with blank-line paragraphs and "- " bullets. Markup is stripped on save and escaped on display. */
    private function clean(array $d): array
    {
        foreach (['title_en', 'body_en', 'title_ny', 'body_ny'] as $f) {
            if (isset($d[$f])) {
                $d[$f] = trim(strip_tags($d[$f]));
            }
        }

        return $d + ['position' => 0];
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $d = $this->clean($request->validate($this->rules()));
        $base = Str::slug($d['title_en']) ?: 'article';
        $slug = $base;
        for ($i = 2; EducationArticle::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }
        $a = EducationArticle::create($d + ['slug' => $slug, 'updated_by' => $request->user()->id]);
        $audit->record('education.created', $a);

        return back()->with('success', 'Article saved.');
    }

    public function update(Request $request, EducationArticle $article, AuditLogger $audit)
    {
        $article->update($this->clean($request->validate($this->rules($article))) + ['updated_by' => $request->user()->id]);
        $audit->record('education.updated', $article, ['published' => $article->published]);

        return back()->with('success', 'Article updated.');
    }

    public function destroy(EducationArticle $article, AuditLogger $audit)
    {
        $audit->record('education.deleted', $article, ['title' => $article->title_en]);
        $article->delete();

        return back()->with('success', 'Article removed.');
    }
}
