<?php

namespace App\Policies;

use App\Models\LicenseKey;
use App\Models\User;

class LicenseKeyPolicy
{
    /**
     * Determine if the user can view any license keys.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can view the license key.
     */
    public function view(User $user, LicenseKey $licenseKey): bool
    {
        return $user->isAdmin() || $user->id === $licenseKey->user_id;
    }

    /**
     * Determine if the user can create license keys.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can update the license key.
     */
    public function update(User $user, LicenseKey $licenseKey): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can delete the license key.
     */
    public function delete(User $user, LicenseKey $licenseKey): bool
    {
        return $user->isAdmin();
    }
}