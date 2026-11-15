<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    // everyone logged in can see the list
    public function viewAny(User $user): bool
    {
        return true;
    }

    // everyone logged in can open an order
    public function view(User $user, PurchaseOrder $order): bool
    {
        return true;
    }

    // warehouse only receives, so it cannot order
    public function create(User $user): bool
    {
        return $user->hasRole('admin', 'manager', 'staff');
    }

    // drafts: admin, the creator, or a manager for auto drafts
    public function update(User $user, PurchaseOrder $order): bool
    {
        return $order->isEditable()
            && ($user->isAdmin()
                || $this->isCreator($user, $order)
                || ($order->is_auto_generated && $user->hasRole('manager')));
    }

    // only admins delete, and only drafts
    public function delete(User $user, PurchaseOrder $order): bool
    {
        return $order->isEditable() && $user->isAdmin();
    }

    // submitting for approval follows the edit rule
    public function submit(User $user, PurchaseOrder $order): bool
    {
        return $this->update($user, $order);
    }

    // managers approve, but not their own orders (admin may)
    public function approve(User $user, PurchaseOrder $order): bool
    {
        return $order->canBeApproved()
            && $user->hasRole('admin', 'manager')
            && ($user->isAdmin() || ! $this->isCreator($user, $order));
    }

    // same people who approve can reject
    public function reject(User $user, PurchaseOrder $order): bool
    {
        return $this->approve($user, $order);
    }

    // approved orders are sent by a manager or admin
    public function send(User $user, PurchaseOrder $order): bool
    {
        return $order->status === 'approved' && $user->hasRole('admin', 'manager');
    }

    // warehouse receives approved or sent orders
    public function receive(User $user, PurchaseOrder $order): bool
    {
        return $order->canBeReceived() && $user->hasRole('admin', 'warehouse');
    }

    // cancel before delivery: managers any time, creator before approval
    public function cancel(User $user, PurchaseOrder $order): bool
    {
        if (! in_array($order->status, ['draft', 'pending', 'approved'])) {
            return false;
        }

        return $user->hasRole('admin', 'manager')
            || ($this->isCreator($user, $order) && $order->status !== 'approved');
    }

    // did this user create the order
    private function isCreator(User $user, PurchaseOrder $order): bool
    {
        return (int) $order->created_by === $user->id;
    }
}
