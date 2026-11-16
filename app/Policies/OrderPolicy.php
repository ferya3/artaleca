<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Orders are commercial records, like enquiries: anyone on the staff can read
 * and move them along, nobody creates one by hand, and nobody deletes one — a
 * paid order is an accounting record, and a cancelled one is the explanation
 * for a refund.
 */
class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function view(User $user, Order $order): bool
    {
        return $user->canAccessAdmin();
    }

    public function update(User $user, Order $order): bool
    {
        return $user->canAccessAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Order $order): bool
    {
        return false;
    }
}
