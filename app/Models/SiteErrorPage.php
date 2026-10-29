<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteErrorPage extends Model
{
    public const STATUSES = [404, 403, 500, 503];

    protected $fillable = ['status_code', 'html', 'enabled'];

    protected function casts(): array
    {
        return ['status_code' => 'integer', 'enabled' => 'boolean'];
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
