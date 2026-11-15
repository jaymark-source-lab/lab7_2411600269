<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderSent extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // the order going to the supplier
    public function __construct(public PurchaseOrder $order)
    {
    }

    // subject line
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Purchase Order {$this->order->reference_number} from FitTrack Pro",
        );
    }

    // template + data
    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-order-sent',
            with: ['order' => $this->order->loadMissing(['supplier', 'items.product', 'approvedBy'])],
        );
    }

    // PDF of the order is attached in Part 8
    public function attachments(): array
    {
        return [];
    }
}
