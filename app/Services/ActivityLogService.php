<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Site;
use App\Models\User;

class ActivityLogService
{
    /**
     * Record an activity log entry.
     */
    public function log(
        ?User $user,
        ?Site $site,
        string $action,
        string $description,
        ?string $ip = null
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $user?->id,
            'site_id' => $site?->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $ip ?? request()?->ip(),
        ]);
    }
}
