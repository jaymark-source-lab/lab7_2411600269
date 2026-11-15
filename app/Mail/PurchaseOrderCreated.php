<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderCreated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // the order waiting for approval
    public function __construct(public PurchaseOrder $order)
    {
    }

    // subject line
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Approval needed: {$this->order->reference_number} ({$this->order->supplier->name})",
        );
    }

    // template + data
    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-order-created',
            with: ['order' => $this->order->loadMissing(['supplier', 'items.product', 'createdBy'])],
        );
    }

    // no attachments yet (PDF added in Part 8)
    public function attachments(): array
    {
        return [];
    }
}