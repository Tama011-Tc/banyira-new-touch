<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What investors may see about a listed business. Registration numbers,
 * addresses, owner identity, contact details and raw documents are never
 * included.
 */
class ListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'name' => $this->public_name,
            'industry' => $this->industry,
            'region' => $this->region,
            'summary' => $this->summary,
            'purpose' => $this->purpose,
            'requested' => $this->amount_requested,
            'funded' => $this->amount_accepted,
            'remaining' => $this->remaining(),
            'progress' => (float) $this->amount_requested > 0 ? round($this->amount_accepted / $this->amount_requested * 100, 1) : 0,
            'risk' => $this->risk_class,
            'rate' => $this->indicative_rate,
            'term' => $this->term_months,
            'status' => $this->isOpen() ? 'open' : $this->status,
            'closes' => $this->closes_at,
            'published' => $this->published_at,
        ];
    }
}
