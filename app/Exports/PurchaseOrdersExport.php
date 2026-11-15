<?php

namespace App\Exports;

use App\Services\SearchService;

class PurchaseOrdersExport extends Export
{
    // same query as the purchase orders page
    public function __construct(private SearchService $search, array $filters)
    {
        $this->filters = $filters;
    }

    // report title
    public function title(): string
    {
        return 'Purchase Orders';
    }

    // column headings
    public function headings(): array
    {
        return ['Reference', 'Supplier', 'Status', 'Source', 'Order Date', 'Expected', 'Received', 'Items', 'Subtotal', 'VAT', 'Total', 'Created By'];
    }

    // number columns
    public function rightAligned(): array
    {
        return [7, 8, 9, 10];
    }

    // one row per order
    public function rows(): iterable
    {
        foreach ($this->search->purchaseOrders($this->filters)->lazy() as $order) {
            yield [
                $order->reference_number,
                $order->supplier?->name ?? '',
                ucfirst($order->status),
                $order->is_auto_generated ? 'Auto' : 'Manual',
                $this->date($order->order_date),
                $this->date($order->expected_delivery_date),
                $this->date($order->received_at),
                $order->items_count,
                $this->money($order->subtotal),
                $this->money($order->tax),
                $this->money($order->total),
                $order->createdBy?->name ?? 'System',
            ];
        }
    }
}
