<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowStockAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // the product running low
    public function __construct(public Product $product)
    {
    }

    // subject line
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Low stock: {$this->product->name} ({$this->product->quantity} left)",
        );
    }

    // template + data
    public function content(): Content
    {
        return new Content(
            view: 'emails.low-stock-alert',
            with: [
                'product'  => $this->product,
                'supplier' => $this->product->preferredSupplier(),
            ],
        );
    }

    // no attachments
    public function attachments(): array
    {
        return [];
    }
}