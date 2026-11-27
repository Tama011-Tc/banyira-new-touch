<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LoanProduct;
use App\Models\Media;
use App\Models\Page;
use App\Services\Content\MediaService;
use App\Services\Content\PageRenderer;
use App\Services\RulesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicController extends Controller
{
    public function show(string $slug, PageRenderer $renderer): Response
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();

        return Inertia::render('Public/Page', $renderer->props($page->published));
    }

    /** Pages created by staff. Built-in pages are only served from their own addresses. */
    public function custom(string $slug, PageRenderer $renderer): Response
    {
        abort_if(Page::where('slug', $slug)->where('is_system', true)->exists(), 404);

        return $this->show($slug, $renderer);
    }

    public function media(Media $media)
    {
        return Storage::disk(MediaService::disk())->response($media->path, $media->original_name, [
            'Content-Type' => $media->mime,
            // A replaced picture is uploaded as a new record, so a file never changes under its address.
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    public function product(LoanProduct $product, RulesService $rules): Response
    {
        abort_unless($product->is_active, 404);

        return Inertia::render('Public/Product', [
            'product' => $product,
            'others' => LoanProduct::active()->whereKeyNot($product->id)->get(['slug', 'name', 'summary']),
            'rules' => $rules->summary(),
        ]);
    }

    public function contact(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:20'],
            'type' => ['required', 'in:business,investor,other'],
            'message' => ['required', 'string', 'max:2000'],
        ]);
        Lead::create([...$data, 'source' => 'Website contact form', 'stage' => 'new']);

        return back()->with('success', 'Thank you. We will reply within two working days.');
    }
}
