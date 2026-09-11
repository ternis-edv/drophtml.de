<?php

namespace App\Policies;

use App\Models\MediaAsset;
use App\Models\User;

class MediaAssetPolicy
{
    /**
     * Determine whether the user can view any media assets.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the media asset.
     */
    public function view(User $user, MediaAsset $mediaAsset): bool
    {
        return $mediaAsset->user_id === $user->id;
    }

    /**
     * Determine whether the user can create media assets.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update or migrate the media asset.
     */
    public function update(User $user, MediaAsset $mediaAsset): bool
    {
        return $mediaAsset->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the media asset.
     */
    public function delete(User $user, MediaAsset $mediaAsset): bool
    {
        return $mediaAsset->user_id === $user->id;
    }
}
