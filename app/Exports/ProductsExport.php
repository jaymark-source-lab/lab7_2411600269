<?php

namespace App\Exports;

use App\Services\SearchService;

class ProductsExport extends Export
{
    // same query as the products page
    public function __construct(private SearchService $search, array $filters)
    {
        $this->filters = $filters;
    }

    // report title
    public function title(): string
    {
        return 'Products';
    }

    // column headings
    public function headings(): array
    {
        return ['SKU', 'Product', 'Category', 'Supplier', 'Quantity', 'Reorder Level', 'Unit Price', 'Stock Value', 'Status'];
    }

    // number columns
    public function rightAligned(): array
    {
        return [4, 5, 6, 7];
    }

    // one row per product
    public function rows(): iterable
    {
        foreach ($this->search->products($this->filters)->lazy() as $product) {
            $supplier = $product->suppliers->firstWhere('pivot.is_preferred', true) ?? $product->suppliers->first();

            yield [
                $product->sku,
                $product->name,
                $product->category,
                $supplier?->name ?? '',
                $product->quantity,
                $product->reorder_level,
                $this->money($product->unit_price),
                $this->money($product->quantity * $product->unit_price),
                $product->stock_status,
            ];
        }
    }
}
