<?php

namespace App\Services\Content;

use App\Models\LoanProduct;
use App\Models\Media;
use App\Services\RulesService;

/** Turns stored page content into what the browser needs to show it. */
class PageRenderer
{
    public function __construct(private RulesService $rules) {}

    public function props(array $content): array
    {
        // Hidden sections are removed here, so their text never reaches the browser.
        $blocks = array_values(array_filter($content['blocks'], fn ($b) => $b['visible']));
        $needs = array_unique(array_merge([], ...array_map(fn ($b) => BlockCatalogue::NEEDS[$b['type']] ?? [], $blocks)));
        $live = [
            'products' => fn () => LoanProduct::active()->get(['slug', 'name', 'summary', 'min_amount', 'max_amount', 'min_months', 'max_months']),
            'rules' => fn () => $this->rules->summary(),
            'minOffer' => fn () => config('banyira.marketplace.min_offer'),
        ];

        return [
            'page' => ['title' => $content['title'], 'description' => $content['description'], 'blocks' => $blocks],
            'live' => array_map(fn (string $need) => $live[$need](), array_combine($needs, $needs)),
            'media' => Media::whereKey(MediaService::idsIn(['blocks' => $blocks]))->get()->mapWithKeys(fn (Media $m) => [$m->id => $m->present()]),
        ];
    }
}
