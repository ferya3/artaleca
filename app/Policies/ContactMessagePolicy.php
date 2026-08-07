<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ContactMessage;
use App\Models\User;

/**
 * Enquiries are commercial records, so the rules differ from content: a viewer
 * (sales staff) can read and triage them but cannot create or destroy them.
 */
class ContactMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function view(User $user, ContactMessage $message): bool
    {
        return $user->canAccessAdmin();
    }

    public function create(User $user): bool
    {
        // Enquiries only ever originate from the public forms.
        return false;
    }

    public function update(User $user, ContactMessage $message): bool
    {
        return $user->canAccessAdmin();
    }

    public function delete(User $user, ContactMessage $message): bool
    {
        return $user->isAdmin();
    }
}
