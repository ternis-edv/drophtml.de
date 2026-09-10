<?php

namespace App\Policies;

use App\Models\Domain;
use App\Models\Site;
use App\Models\User;

class DomainPolicy
{
    /**
     * Determine whether the user can view any domains of a site.
     */
    public function viewAny(User $user, ?Site $site = null): bool
    {
        return $site ? $site->user_id === $user->id : true;
    }

    /**
     * Determine whether the user can view the domain.
     */
    public function view(User $user, Domain $domain): bool
    {
        return $domain->site?->user_id === $user->id;
    }

    /**
     * Determine whether the user can create domains for a site.
     */
    public function create(User $user, Site $site): bool
    {
        return $site->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the domain.
     */
    public function delete(User $user, Domain $domain): bool
    {
        return $domain->site?->user_id === $user->id;
    }
}
