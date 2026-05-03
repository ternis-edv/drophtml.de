<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
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
        'is_quiet',
    ];

    protected $casts = [
        'is_quiet' => 'boolean',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
