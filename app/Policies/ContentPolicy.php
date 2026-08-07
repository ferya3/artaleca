<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared authorisation for every editor-managed content model.
 *
 * The rule is deliberately simple and stated once: anyone with panel access may
 * read content, editors and admins may write it, and only admins may delete it.
 * Registering one policy for eight models means a new content type cannot ship
 * with its permissions accidentally left open.
 */
class ContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function view(User $user, Model $model): bool
    {
        return $user->canAccessAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, Model $model): bool
    {
        return $user->isEditor();
    }

    /** Destructive, and unlike an edit it cannot be undone from the panel. */
    public function delete(User $user, Model $model): bool
    {
        return $user->isAdmin();
    }
}
