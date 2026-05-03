<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    protected $fillable = [
        'site_id',
        'domain',
        'is_custom',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'is_custom' => 'boolean',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
