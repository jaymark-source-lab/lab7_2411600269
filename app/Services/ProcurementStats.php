<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProcurementStats
{
    public const CACHE_KEY = 'widgets.supplier-performance';

    public function __construct(private ReorderService $reorder)
    {
    }

    // orders waiting for approval
    public function pendingOrders(): array
    {
        return [
            'count'  => PurchaseOrder::pending()->count(),
            'value'  => (float) PurchaseOrder::pending()->sum('total'),
            'orders' => PurchaseOrder::pending()->with('supplier')->latest('id')->take(5)->get(),
        ];
    }

    // newest orders of any status
    public function recentOrders(int $limit = 6): Collection
    {
        return PurchaseOrder::with('supplier')->latest('id')->take($limit)->get();
    }

    // suppliers ranked by on-time delivery (cached for 10 minutes)
    public function supplierPerformance(int $limit = 5): Collection
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function () use ($limit) {
            return DB::table('purchase_orders')
                ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
                ->where('purchase_orders.status', 'received')
                ->whereNull('purchase_orders.deleted_at')
                ->groupBy('suppliers.id', 'suppliers.name')
                ->selectRaw('suppliers.id, suppliers.name, COUNT(*) AS orders')
                ->selectRaw('SUM(CASE WHEN DATE(purchase_orders.received_at) <= purchase_orders.expected_delivery_date THEN 1 ELSE 0 END) AS on_time')
                ->selectRaw('SUM(purchase_orders.total) AS value')
                ->get()
                ->map(fn ($row) => [
                    'id'           => $row->id,
                    'name'         => $row->name,
                    'orders'       => (int) $row->orders,
                    'on_time_rate' => round($row->on_time / $row->orders * 100, 1),
                    'value'        => (float) $row->value,
                ])
                ->sortByDesc(fn ($row) => $row['on_time_rate'] * 1000 + $row['orders'])
                ->take($limit)
                ->values();
        });
    }

    // products at or below their reorder point that are not on order yet
    public function reorderAlerts(int $limit = 6): Collection
    {
        return $this->reorder->getProductsNeedingReorder()
            ->take($limit)
            ->map(function (Product $product) {
                $supplier = $this->reorder->preferredSupplier($product);

                return [
                    'id'            => $product->id,
                    'name'          => $product->name,
                    'sku'           => $product->sku,
                    'quantity'      => $product->quantity,
                    'reorder_point' => $this->reorder->calculateReorderPoint($product),
                    'suggested_qty' => $this->reorder->calculateOptimalOrderQuantity($product),
                    'supplier_id'   => $supplier?->id,
                    'supplier'      => $supplier?->name,
                ];
            })
            ->values();
    }
}
