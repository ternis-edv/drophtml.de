<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SiteRedirect extends Model
{
    use HasUuids, SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'site_id',
        'from_path',
        'to_url',
        'status_code',
        'is_active',
        'hit_count',
        'last_accessed_at',
        'description',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'is_active' => 'boolean',
        'hit_count' => 'integer',
        'last_accessed_at' => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function views()
    {
        return $this->hasMany(View::class, 'redirect_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
