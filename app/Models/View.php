<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class View extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

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
