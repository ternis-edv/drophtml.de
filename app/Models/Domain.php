<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Domain extends Model
{
    use HasUuids, SoftDeletes;

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
