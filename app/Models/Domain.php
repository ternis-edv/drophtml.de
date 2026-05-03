<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Domain extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

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
