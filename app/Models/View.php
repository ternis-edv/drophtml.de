<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class View extends Model
{
    use HasUuids, SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'site_id',
        'ip_address',
        'user_agent',
        'referer',
        'referer_domain',
        'path',
        'country_code',
        'browser',
        'os',
        'device',
        'is_bot',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'is_quiet',
        'status_code',
        'is_redirect',
        'redirect_id',
        'created_at',
    ];

    protected $casts = [
        'is_quiet' => 'boolean',
        'is_bot' => 'boolean',
        'is_redirect' => 'boolean',
        'status_code' => 'integer',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function redirect()
    {
        return $this->belongsTo(SiteRedirect::class, 'redirect_id');
    }

    /**
     * Scope for real page views (non-asset).
     */
    public function scopePageViews($query)
    {
        return $query->where('is_quiet', false);
    }

    /**
     * Scope for human (non-bot) traffic.
     */
    public function scopeHuman($query)
    {
        return $query->where('is_bot', false);
    }

    /**
     * Scope for redirect hits.
     */
    public function scopeRedirects($query)
    {
        return $query->where('is_redirect', true);
    }

    /**
     * Scope for 404 not found views.
     */
    public function scopeNotFound($query)
    {
        return $query->where('status_code', 404);
    }

    /**
     * Scope for filtering by days.
     */
    public function scopeForDays($query, int $days)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
