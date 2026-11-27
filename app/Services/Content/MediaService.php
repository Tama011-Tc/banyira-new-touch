<?php

namespace App\Services\Content;

use App\Models\Media;
use App\Models\Page;
use App\Models\PageVersion;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** Images used on public pages. Unlike customer documents, these are public once uploaded. */
class MediaService
{
    public function __construct(private AuditLogger $audit) {}

    public function store(UploadedFile $file, string $alt, User $by): Media
    {
        [$width, $height] = getimagesize($file->getRealPath()) ?: throw ValidationException::withMessages(['file' => 'That file is not a readable image.']);

        $media = Media::create([
            'path' => $file->store('', self::disk()),
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'alt' => $alt,
            'uploaded_by' => $by->id,
        ]);
        $this->audit->log('content', "Uploaded image {$media->original_name}", $media, [], $by);

        return $media;
    }

    public function delete(Media $media, User $by): void
    {
        if (in_array($media->id, $this->usedIds(), true)) {
            throw ValidationException::withMessages(['media' => 'This image is used by a page or one of its earlier versions.']);
        }
        $this->audit->log('content', "Deleted image {$media->original_name}", $media, [], $by);
        $media->delete();
        Storage::disk(self::disk())->delete($media->path);
    }

    /** @return list<int> images referenced by any draft, live page or saved version */
    public function usedIds(): array
    {
        $contents = Page::get(['draft', 'published'])->flatMap(fn (Page $p) => [$p->draft, $p->published])
            ->concat(PageVersion::get(['content'])->pluck('content'));

        return $contents->flatMap(fn ($content) => self::idsIn($content ?? []))->unique()->values()->all();
    }

    /** @return list<int> */
    public static function idsIn(array $content): array
    {
        return collect($content['blocks'] ?? [])->pluck('data.media_id')->filter()->map(fn ($id) => (int) $id)->values()->all();
    }

    public static function disk(): string
    {
        return config('banyira.media_disk');
    }
}
