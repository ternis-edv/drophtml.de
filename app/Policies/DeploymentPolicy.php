<?php

namespace App\Policies;

use App\Models\Deployment;
use App\Models\Site;
use App\Models\User;

class DeploymentPolicy
{
    /**
     * Determine whether the user can view any deployments of a site.
     */
    public function viewAny(User $user, ?Site $site = null): bool
    {
        return $site ? $site->user_id === $user->id : true;
    }

    /**
     * Determine whether the user can view the deployment.
     */
    public function view(User $user, Deployment $deployment): bool
    {
        return $deployment->site?->user_id === $user->id;
    }
}
