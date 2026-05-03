<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;

class Site extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::random(12);
            }
        });
    }

    protected $fillable = [
        'id',
        'slug',
        'original_name',
        'path',
        'user_id',
        'views',
        'is_permanent',
        'status',
        'expires_at',
        'github_repo_full_name',
        'github_branch',
        'auto_deploy',
        'github_webhook_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_permanent' => 'boolean',
        'auto_deploy' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function siteViews()
    {
        return $this->hasMany(View::class);
    }

    public function deployments()
    {
        return $this->hasMany(Deployment::class);
    }
}
