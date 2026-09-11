<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaAssetResource extends JsonResource
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
            'user_id' => $this->user_id,
            'site_id' => $this->site_id,
            'original_name' => $this->original_name,
            'path' => $this->path,
            'storage_driver' => $this->storage_driver,
            'file_type' => $this->file_type,
            'mime_type' => $this->mime_type,
            'size' => (int) $this->size,
            'formatted_size' => $this->formatted_size,
            'url' => $this->resolved_url,
            'html_tag' => $this->html_tag,
            'markdown_tag' => $this->markdown_tag,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
