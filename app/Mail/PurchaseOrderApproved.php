<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderApproved extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // the approved order
    public function __construct(public PurchaseOrder $order)
    {
    }

    // subject line
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Approved: {$this->order->reference_number}",
        );
    }

    // template + data
    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-order-approved',
            with: ['order' => $this->order->loadMissing(['supplier', 'createdBy', 'approvedBy'])],
        );
    }

    // no attachments
    public function attachments(): array
    {
        return [];
    }
}
