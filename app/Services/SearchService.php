<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SearchService
{
    // transaction types (value => label)
    public const TRANSACTION_TYPES = [
        'stock_in'   => 'Stock In',
        'stock_out'  => 'Stock Out',
        'adjustment' => 'Adjustment',
    ];

    // product sort options (key => [column, direction])
    public const PRODUCT_SORTS = [
        'name'       => ['name', 'asc'],
        'name_desc'  => ['name', 'desc'],
        'stock_low'  => ['quantity', 'asc'],
        'stock_high' => ['quantity', 'desc'],
        'price_high' => ['unit_price', 'desc'],
    ];

    // products by name, SKU, category, supplier and stock level
    public function products(array $filters): Builder
    {
        [$column, $direction] = self::PRODUCT_SORTS[$filters['sort'] ?? 'name'] ?? self::PRODUCT_SORTS['name'];

        return Product::query()
            ->with('suppliers')
            ->when($filters['search'] ?? null, fn ($q, $term) => $q->search($term))
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->inCategory($category))
            ->when($filters['supplier_id'] ?? null, fn ($q, $id) => $q->fromSupplier($id))
            ->when($filters['stock'] ?? null, fn ($q, $status) => $q->stockStatus($status))
            ->orderBy($column, $direction);
    }

    // purchase orders by reference, supplier, status, source and date range
    public function purchaseOrders(array $filters): Builder
    {
        $source = $filters['source'] ?? null;

        return PurchaseOrder::query()
            ->with(['supplier', 'createdBy'])
            ->withCount('items')
            ->when($filters['search'] ?? null, fn ($q, $term) => $q->search($term))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->byStatus($status))
            ->when($filters['supplier_id'] ?? null, fn ($q, $id) => $q->forSupplier($id))
            ->when($source === 'auto', fn ($q) => $q->where('is_auto_generated', true))
            ->when($source === 'manual', fn ($q) => $q->where('is_auto_generated', false))
            ->orderedBetween($filters['from'] ?? null, $filters['to'] ?? null)
            ->latest('order_date')
            ->latest('id');
    }

    // transactions by product, type, user and date range
    public function transactions(array $filters): Builder
    {
        return InventoryTransaction::query()
            ->with(['product', 'user'])
            ->when($filters['search'] ?? null, fn ($q, $term) => $q->search($term))
            ->when($filters['product_id'] ?? null, fn ($q, $id) => $q->forProduct($id))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->ofType($type))
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->recordedBy($id))
            ->recordedBetween($filters['from'] ?? null, $filters['to'] ?? null)
            ->latest()
            ->latest('id');
    }

    // dropdown options for the product filters
    public function productOptions(): array
    {
        return [
            'categories'    => Product::orderBy('category')->pluck('category')->filter()->unique()->mapWithKeys(fn ($c) => [$c => $c]),
            'suppliers'     => Supplier::orderBy('name')->pluck('name', 'id'),
            'stockStatuses' => ['in' => 'In stock', 'low' => 'Low stock', 'out' => 'Out of stock'],
            'sorts'         => [
                'name'       => 'Name (A-Z)',
                'name_desc'  => 'Name (Z-A)',
                'stock_low'  => 'Lowest stock',
                'stock_high' => 'Highest stock',
                'price_high' => 'Highest price',
            ],
        ];
    }

    // dropdown options for the purchase order filters
    public function purchaseOrderOptions(): array
    {
        return [
            'statuses'  => collect(['draft', 'pending', 'approved', 'rejected', 'sent', 'received', 'cancelled'])
                ->mapWithKeys(fn ($s) => [$s => ucfirst($s)]),
            'suppliers' => Supplier::withTrashed()->orderBy('name')->pluck('name', 'id'),
            'sources'   => ['auto' => 'Auto-generated', 'manual' => 'Created by staff'],
        ];
    }

    // dropdown options for the transaction filters
    public function transactionOptions(): array
    {
        return [
            'products' => Product::orderBy('name')->pluck('name', 'id'),
            'types'    => self::TRANSACTION_TYPES,
            'users'    => User::orderBy('name')->pluck('name', 'id'),
        ];
    }
}
