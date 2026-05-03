<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deployment extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'site_id',
        'status',
        'commit_hash',
        'commit_message',
        'source',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'json',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
