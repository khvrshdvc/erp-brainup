<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\ShopOrder;
use App\Models\User;

class ShopOrderPolicy
{
    /**
     * Admins hand over or cancel pending orders from the branches they manage.
     */
    public function handle(User $user, ShopOrder $order): bool
    {
        return $order->isPending()
            && $user->hasRole(Role::SuperAdmin, Role::BranchAdmin)
            && in_array($order->branch_id, $user->availableBranchIds(), true);
    }

    /**
     * Students may withdraw their own order while it is still pending.
     */
    public function cancelOwn(User $user, ShopOrder $order): bool
    {
        return $order->isPending() && $order->user_id === $user->id;
    }
}
