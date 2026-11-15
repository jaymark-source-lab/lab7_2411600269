<?php

namespace App\Mail;

use App\Exports\PurchaseOrderPdf;
use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderSent extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // the order being sent to the supplier
    public function __construct(public PurchaseOrder $order)
    {
    }

    // subject line
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Purchase Order {$this->order->reference_number} from " . config('mail.from.name'),
        );
    }

    // email body
    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-order-sent',
            with: ['order' => $this->order->loadMissing(['supplier', 'items.product'])],
        );
    }

    // PO as a PDF attachment
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => PurchaseOrderPdf::make($this->order)->output(), $this->order->reference_number . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
