<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogPolicy
{
    /**
     * Determine whether the user can view any activity logs.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the activity log.
     */
    public function view(User $user, ActivityLog $activityLog): bool
    {
        return $activityLog->user_id === $user->id;
    }
}
