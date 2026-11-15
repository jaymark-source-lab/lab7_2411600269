<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    // every logged-in user can see the list
    public function viewAny(User $user): bool
    {
        return true;
    }

    // every logged-in user can open an order
    public function view(User $user, PurchaseOrder $order): bool
    {
        return true;
    }

    // warehouse staff only receive, they don't create orders
    public function create(User $user): bool
    {
        return $user->hasRole('admin', 'manager', 'staff');
    }

    // drafts only, by the creator or an admin
    public function update(User $user, PurchaseOrder $order): bool
    {
        return $order->isEditable() && ($user->isAdmin() || $this->isCreator($user, $order));
    }

    // drafts only, admin only
    public function delete(User $user, PurchaseOrder $order): bool
    {
        return $order->isEditable() && $user->isAdmin();
    }

    // send a draft for approval (same people who can edit it)
    public function submit(User $user, PurchaseOrder $order): bool
    {
        return $this->update($user, $order);
    }

    // managers/admins approve pending orders, but not their own (admins excepted)
    public function approve(User $user, PurchaseOrder $order): bool
    {
        return $order->canBeApproved()
            && $user->hasRole('admin', 'manager')
            && ($user->isAdmin() || ! $this->isCreator($user, $order));
    }

    // same rule as approving
    public function reject(User $user, PurchaseOrder $order): bool
    {
        return $this->approve($user, $order);
    }

    // approved orders are sent by managers/admins
    public function send(User $user, PurchaseOrder $order): bool
    {
        return $order->status === 'approved' && $user->hasRole('admin', 'manager');
    }

    // warehouse staff and admins receive deliveries
    public function receive(User $user, PurchaseOrder $order): bool
    {
        return $order->canBeReceived() && $user->hasRole('admin', 'warehouse');
    }

    // cancel before it is sent: managers/admins, or the creator while not yet approved
    public function cancel(User $user, PurchaseOrder $order): bool
    {
        if (! in_array($order->status, ['draft', 'pending', 'approved'], true)) {
            return false;
        }

        return $user->hasRole('admin', 'manager')
            || ($this->isCreator($user, $order) && $order->status !== 'approved');
    }

    // did this user create the order?
    private function isCreator(User $user, PurchaseOrder $order): bool
    {
        return (int) $order->created_by === $user->id;
    }
}