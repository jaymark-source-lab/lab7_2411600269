<?php

namespace App\Exports;

use App\Services\SearchService;

class TransactionsExport extends Export
{
    // same query as the transactions page
    public function __construct(private SearchService $search, array $filters)
    {
        $this->filters = $filters;
    }

    // report title
    public function title(): string
    {
        return 'Inventory Transactions';
    }

    // column headings
    public function headings(): array
    {
        return ['Date', 'SKU', 'Product', 'Type', 'Quantity', 'Reference', 'Recorded By'];
    }

    // number columns
    public function rightAligned(): array
    {
        return [4];
    }

    // one row per transaction
    public function rows(): iterable
    {
        $types = SearchService::TRANSACTION_TYPES;

        foreach ($this->search->transactions($this->filters)->lazy() as $transaction) {
            yield [
                $this->date($transaction->created_at, 'Y-m-d H:i'),
                $transaction->product?->sku ?? '',
                $transaction->product?->name ?? '',
                $types[$transaction->type] ?? $transaction->type,
                $transaction->quantity,
                $transaction->reference_number ?? '',
                $transaction->user?->name ?? 'System',
            ];
        }
    }
}
