<?php

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Service
 */
class ServiceSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'service_code' => $this->service_code,
            'short_summary' => $this->short_summary,
            'price_range' => $this->price_range,
            'is_featured' => $this->is_featured,
            'public_url' => $this->publicUrl(),
            'categories' => ServiceCategoryResource::collection($this->whenLoaded('categories')),
        ];
    }
}
