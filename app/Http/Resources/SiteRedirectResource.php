<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteRedirectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_id' => $this->site_id,
            'from_path' => $this->from_path,
            'to_url' => $this->to_url,
            'status_code' => $this->status_code,
            'is_active' => $this->is_active,
            'hit_count' => $this->hit_count,
            'last_accessed_at' => $this->last_accessed_at?->toIso8601String(),
            'description' => $this->description,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
