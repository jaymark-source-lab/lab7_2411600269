<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PurchaseOrderAuthorizationTest extends TestCase
{
    // in-memory user with a role (not saved)
    private function user(string $role, int $id): User
    {
        return (new User)->forceFill([
            'id'       => $id,
            'name'     => ucfirst($role) . ' User',
            'role'     => $role,
            'is_admin' => $role === 'admin',
        ]);
    }

    // in-memory purchase order (not saved)
    private function order(string $status, int $createdBy): PurchaseOrder
    {
        return (new PurchaseOrder)->forceFill([
            'id'                => 99,
            'status'            => $status,
            'created_by'        => $createdBy,
            'is_auto_generated' => false,
        ]);
    }

    // ask the policy if a user may do something
    private function can(User $user, string $ability, $target = null): bool
    {
        return Gate::forUser($user)->allows($ability, $target);
    }

    // guests must log in first
    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/purchase-orders')->assertRedirect('/login');
    }

    // warehouse staff only receive goods, they don't order
    public function test_warehouse_cannot_create_orders(): void
    {
        $this->assertTrue($this->can($this->user('staff', 4), 'create', PurchaseOrder::class));
        $this->assertTrue($this->can($this->user('manager', 2), 'create', PurchaseOrder::class));
        $this->assertFalse($this->can($this->user('warehouse', 3), 'create', PurchaseOrder::class));
    }

    // managers approve, staff and warehouse cannot
    public function test_only_managers_approve_pending_orders(): void
    {
        $order = $this->order('pending', 4);

        $this->assertTrue($this->can($this->user('manager', 2), 'approve', $order));
        $this->assertFalse($this->can($this->user('staff', 4), 'approve', $order));
        $this->assertFalse($this->can($this->user('warehouse', 3), 'approve', $order));
    }

    // separation of duties: a manager cannot approve their own order
    public function test_manager_cannot_approve_own_order(): void
    {
        $this->assertFalse($this->can($this->user('manager', 2), 'approve', $this->order('pending', 2)));
    }

    // a draft must be submitted before approval
    public function test_drafts_cannot_be_approved(): void
    {
        $this->assertFalse($this->can($this->user('manager', 2), 'approve', $this->order('draft', 4)));
    }

    // only managers send approved orders to the supplier
    public function test_only_managers_send_approved_orders(): void
    {
        $order = $this->order('approved', 4);

        $this->assertTrue($this->can($this->user('manager', 2), 'send', $order));
        $this->assertFalse($this->can($this->user('staff', 4), 'send', $order));
        $this->assertFalse($this->can($this->user('warehouse', 3), 'send', $order));
    }

    // only warehouse receives, and only after the order is sent
    public function test_only_warehouse_receives_sent_orders(): void
    {
        $this->assertTrue($this->can($this->user('warehouse', 3), 'receive', $this->order('sent', 4)));
        $this->assertFalse($this->can($this->user('manager', 2), 'receive', $this->order('sent', 4)));
        $this->assertFalse($this->can($this->user('staff', 4), 'receive', $this->order('sent', 4)));
        $this->assertFalse($this->can($this->user('warehouse', 3), 'receive', $this->order('pending', 4)));
    }

    // only admins delete drafts
    public function test_only_admin_deletes_drafts(): void
    {
        $draft = $this->order('draft', 4);

        $this->assertTrue($this->can($this->user('admin', 1), 'delete', $draft));
        $this->assertFalse($this->can($this->user('manager', 2), 'delete', $draft));
        $this->assertFalse($this->can($this->user('staff', 4), 'delete', $draft));
    }

    // admin gate (supplier delete/restore, stock adjustments)
    public function test_admin_gate(): void
    {
        $this->assertTrue($this->can($this->user('admin', 1), 'admin'));
        $this->assertFalse($this->can($this->user('manager', 2), 'admin'));
        $this->assertFalse($this->can($this->user('staff', 4), 'admin'));
    }
}
