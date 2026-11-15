<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class PurchaseOrdersGenerated extends Notification
{
    use Queueable;

    // the auto-generated orders
    public function __construct(public Collection $orders)
    {
    }

    // bell icon only
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    // saved in the notifications table
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->orders->count() . ' draft purchase order(s) auto-generated: '
                . $this->orders->pluck('reference_number')->implode(', ') . '. Please review.',
            'url'     => '/purchase-orders?status=draft',
        ];
    }
}