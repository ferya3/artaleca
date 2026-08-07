<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** An admin manages every account; anyone may edit their own profile. */
    public function update(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->is($model);
    }

    /** Deleting your own account would lock you out mid-request. */
    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin() && ! $user->is($model);
    }
}
