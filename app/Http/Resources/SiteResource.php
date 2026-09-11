<?php

namespace App\Http\Resources;

use App\Models\MediaAsset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteResource extends JsonResource
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
            'slug' => $this->slug,
            'original_name' => $this->original_name,
            'status' => $this->status,
            'is_permanent' => (bool) $this->is_permanent,
            'expires_at' => $this->expires_at,
            'views_count' => (int) $this->views,
            'path' => $this->path,
            'url' => url('/s/'.$this->slug),
            'github_repo' => $this->github_repo_full_name,
            'github_branch' => $this->github_branch,
            'auto_deploy' => (bool) $this->auto_deploy,
            'is_password_protected' => (bool) $this->is_password_protected,
            'is_spa' => (bool) $this->is_spa,
            'storage_bytes' => (int) $this->storage_bytes,
            'formatted_storage_bytes' => MediaAsset::formatBytes((int) $this->storage_bytes),
            'media_assets_count' => (int) ($this->media_assets_count ?? $this->mediaAssets()->count()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'domains' => DomainResource::collection($this->whenLoaded('domains')),
            'deployments' => DeploymentResource::collection($this->whenLoaded('deployments')),
        ];
    }
}
