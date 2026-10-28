<?php

namespace App\Models;

use App\Services\MediaStorageService;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'storage_key',
        'storage_location',
        'extension',
        'user_id',
        'site_id',
        'original_name',
        'path',
        'disk',
        'storage_driver',
        'file_type',
        'mime_type',
        'size',
        'public_url',
        'metadata',
    ];

    protected $casts = [
        'size' => 'integer',
        'metadata' => 'array',
    ];

    protected $appends = [
        'resolved_url',
        'formatted_size',
        'html_tag',
        'markdown_tag',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $asset) {
            $asset->extension = strtolower(pathinfo($asset->path, PATHINFO_EXTENSION));
            if ($asset->hasUuidKey() || $asset->storage_driver === 'code') {
                $asset->attributes['public_url'] = null;
            }
        });
    }

    public function storageDisk(): Filesystem
    {
        $disk = Storage::disk($this->disk ?: 'media');
        if ($this->storage_driver !== 'asset_storage' || $this->hasUuidKey()
            || config('filesystems.disks.media.driver') !== 's3' || $disk->exists($this->path)) {
            return $disk;
        }

        // Previous releases accidentally used the server path as the R2 key prefix.
        return Storage::build(array_merge(config('filesystems.disks.media'), [
            'root' => config('media.legacy_root'),
        ]));
    }

    public function hasUuidKey(): bool
    {
        return $this->path === $this->id.'.'.strtolower(pathinfo($this->path ?? '', PATHINFO_EXTENSION));
    }

    // Compatibility for existing API clients and site-code integrations.
    public function getPathAttribute(): ?string
    {
        return $this->attributes['storage_key'] ?? null;
    }

    public function setPathAttribute($value): void
    {
        $this->attributes['storage_key'] = $value;
    }

    public function getStorageDriverAttribute(): ?string
    {
        return $this->attributes['storage_location'] ?? null;
    }

    public function setStorageDriverAttribute($value): void
    {
        $this->attributes['storage_location'] = $value;
    }

    public function getPublicUrlAttribute(): string
    {
        return $this->resolved_url;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function scopeAssetStorage($query)
    {
        return $query->where('storage_location', 'asset_storage');
    }

    public function scopeInCode($query)
    {
        return $query->where('storage_location', 'code');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('file_type', $type);
    }

    public function scopeForSite($query, ?string $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    public function isImage(): bool
    {
        return $this->file_type === 'image';
    }

    public function isVideo(): bool
    {
        return $this->file_type === 'video';
    }

    public function isAudio(): bool
    {
        return $this->file_type === 'audio';
    }

    public function isPdf(): bool
    {
        return str_ends_with(strtolower($this->original_name), '.pdf') || $this->mime_type === 'application/pdf';
    }

    public function isDocument(): bool
    {
        return in_array($this->file_type, ['document', 'other'], true);
    }

    /**
     * Format a byte count into a human-readable string.
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    /**
     * Get human-readable formatted file size.
     */
    public function getFormattedSizeAttribute(): string
    {
        return static::formatBytes((int) $this->size);
    }

    /**
     * Resolve public CDN / local / site URL.
     */
    public function getResolvedUrlAttribute(): string
    {
        if (! empty($this->attributes['public_url']) && ! $this->hasUuidKey()) {
            return $this->attributes['public_url'];
        }

        if ($this->storage_driver === 'code') {
            if ($this->site) {
                // If path already starts with site slug or path, strip site path prefix
                $relativePath = $this->path;
                $prefix = rtrim($this->site->path, '/').'/';
                if (str_starts_with($relativePath, $prefix)) {
                    $relativePath = substr($relativePath, strlen($prefix));
                }

                return url('/s/'.$this->site->slug.'/'.ltrim($relativePath, '/'));
            }

            return Storage::disk('public')->url($this->path);
        }

        $disk = $this->disk ?: 'media';

        return app(MediaStorageService::class)->publicUrlFor($this->path);
    }

    /**
     * Get ready-to-use HTML tag for embedding.
     */
    public function getHtmlTagAttribute(): string
    {
        $url = htmlspecialchars($this->resolved_url, ENT_QUOTES, 'UTF-8');
        $name = htmlspecialchars($this->original_name, ENT_QUOTES, 'UTF-8');

        if ($this->isImage()) {
            return '<img src="'.$url.'" alt="'.$name.'" loading="lazy" />';
        }

        if ($this->isVideo()) {
            $mime = htmlspecialchars($this->mime_type ?: 'video/mp4', ENT_QUOTES, 'UTF-8');

            return '<video controls preload="metadata">'."\n".
                   '    <source src="'.$url.'" type="'.$mime.'">'."\n".
                   '    Your browser does not support the video tag.'."\n".
                   '</video>';
        }

        if ($this->isAudio()) {
            $mime = htmlspecialchars($this->mime_type ?: 'audio/mpeg', ENT_QUOTES, 'UTF-8');

            return '<audio controls preload="metadata">'."\n".
                   '    <source src="'.$url.'" type="'.$mime.'">'."\n".
                   '    Your browser does not support the audio element.'."\n".
                   '</audio>';
        }

        return '<a href="'.$url.'" target="_blank" rel="noopener" download="'.$name.'">'.$name.'</a>';
    }

    /**
     * Get Markdown snippet.
     */
    public function getMarkdownTagAttribute(): string
    {
        $url = str_replace([' ', '<', '>', "\r", "\n"], ['%20', '%3C', '%3E', '%0D', '%0A'], $this->resolved_url);
        $name = str_replace(['\\', '[', ']'], ['\\\\', '\\[', '\\]'], preg_replace('/[\r\n]+/', ' ', $this->original_name));

        if ($this->isImage()) {
            return "![{$name}](<{$url}>)";
        }

        return "[{$name}](<{$url}>)";
    }
}
