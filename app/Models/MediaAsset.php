<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
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
        return $query->where('storage_driver', 'asset_storage');
    }

    public function scopeInCode($query)
    {
        return $query->where('storage_driver', 'code');
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
        if (! empty($this->public_url)) {
            return $this->public_url;
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

        return Storage::disk($disk)->url($this->path);
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

            return '<video controls preload="metadata" class="w-full rounded-lg">'."\n".
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

        return '<a href="'.$url.'" target="_blank" download="'.$name.'">'.$name.'</a>';
    }

    /**
     * Get Markdown snippet.
     */
    public function getMarkdownTagAttribute(): string
    {
        $url = $this->resolved_url;
        $name = $this->original_name;

        if ($this->isImage()) {
            return "![{$name}]({$url})";
        }

        return "[{$name}]({$url})";
    }
}
