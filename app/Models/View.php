<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class View extends Model
{
    protected $fillable = [
        'site_id',
        'ip_address',
        'user_agent',
        'referer',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
