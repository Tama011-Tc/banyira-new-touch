<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\Content\MediaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** The picture library used by website pages. */
class MediaController extends Controller
{
    public function __construct(private MediaService $media) {}

    public function index()
    {
        $used = array_flip($this->media->usedIds());

        return Inertia::render('Staff/Content/Media', [
            'media' => Media::with('uploader:id,name')->latest('id')->paginate(24)->through(fn (Media $m) => [
                ...$m->present(), 'name' => $m->original_name, 'size' => $m->size, 'by' => $m->uploader?->name, 'used' => isset($used[$m->id]),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            // SVG is excluded: it can carry script.
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.config('banyira.media_max_kb'), 'dimensions:max_width=6000,max_height=6000'],
            'alt' => ['required', 'string', 'max:160'],
        ], ['alt.required' => 'Describe the picture for people who cannot see it.']);
        $this->media->store($data['file'], $data['alt'], $request->user());

        return back()->with('success', 'Image uploaded.');
    }

    public function destroy(Request $request, Media $media)
    {
        $this->media->delete($media, $request->user());

        return back()->with('success', 'Image deleted.');
    }
}
