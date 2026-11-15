<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReorderService
{
    public const USAGE_DAYS = 30;   // look-back window for average usage
    public const SAFETY_DAYS = 3;   // extra days of stock kept as safety
    public const COVER_DAYS = 30;   // one order should last about a month

    // product id => units sold in the last 30 days (loaded once)
    private ?Collection $usage = null;

    // average units sold per day
    public function averageDailyUsage(Product $product): float
    {
        $this->usage ??= InventoryTransaction::stockOut()
            ->where('created_at', '>=', now()->subDays(self::USAGE_DAYS))
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(ABS(quantity)) as total')
            ->pluck('total', 'product_id');

        return round(($this->usage[$product->id] ?? 0) / self::USAGE_DAYS, 2);
    }

    // (daily usage x lead time) + safety stock, never below the manual reorder level
    public function calculateReorderPoint(Product $product): int
    {
        $daily = $this->averageDailyUsage($product);
        $leadTime = $this->preferredSupplier($product)?->lead_time_days ?? 7;
        $safetyStock = $daily * self::SAFETY_DAYS;

        return max((int) ceil($daily * $leadTime + $safetyStock), $product->reorder_level);
    }

    // enough to reach the reorder point plus a month of usage, in supplier pack sizes
    public function calculateOptimalOrderQuantity(Product $product): int
    {
        $daily = $this->averageDailyUsage($product);
        $reorderPoint = $this->calculateReorderPoint($product);

        $target = $daily > 0
            ? $reorderPoint + (int) ceil($daily * self::COVER_DAYS)
            : $reorderPoint * 2;

        $needed = max($target - $product->quantity, 1);
        $packSize = max((int) ($this->preferredSupplier($product)?->pivot->minimum_order_quantity ?? 1), 1);

        // round up to a full pack
        return (int) (ceil($needed / $packSize) * $packSize);
    }

    // products at or below their reorder point that are not already on an open order
    public function getProductsNeedingReorder(): Collection
    {
        $alreadyOrdered = PurchaseOrderItem::whereHas('purchaseOrder', fn ($q) => $q->active())
            ->pluck('product_id')
            ->unique();

        return Product::with('suppliers')
            ->whereNotIn('id', $alreadyOrdered)
            ->orderBy('name')
            ->get()
            ->filter(fn (Product $product) => $product->quantity <= $this->calculateReorderPoint($product))
            ->values();
    }

    // supplier id => products (key 0 = no active preferred supplier)
    public function groupProductsBySupplier(Collection $products): Collection
    {
        return $products->groupBy(fn (Product $product) => $this->preferredSupplier($product)?->id ?? 0);
    }

    // create one draft PO per supplier for everything that needs reordering
    public function generatePurchaseOrders(?User $creator = null): array
    {
        $creator ??= User::where('role', 'admin')->firstOrFail();
        $groups = $this->groupProductsBySupplier($this->getProductsNeedingReorder());
        $skipped = $groups->pull(0, collect());
        $orders = collect();

        foreach ($groups as $products) {
            $supplier = $this->preferredSupplier($products->first());
            $orders->push($this->createDraftOrder($supplier, $products, $creator));
        }

        return ['orders' => $orders, 'skipped' => $skipped];
    }

    // one draft order for one supplier
    private function createDraftOrder(Supplier $supplier, Collection $products, User $creator): PurchaseOrder
    {
        return DB::transaction(function () use ($supplier, $products, $creator) {
            $order = PurchaseOrder::create([
                'reference_number'       => PurchaseOrder::generateReferenceNumber(),
                'supplier_id'            => $supplier->id,
                'status'                 => 'draft',
                'order_date'             => today(),
                'expected_delivery_date' => today()->addDays($supplier->lead_time_days),
                'terms'                  => $supplier->payment_terms,
                'notes'                  => 'Auto-generated: stock at or below the reorder point.',
                'is_auto_generated'      => true,
                'created_by'             => $creator->id,
            ]);

            foreach ($products as $product) {
                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity'   => $this->calculateOptimalOrderQuantity($product),
                    'unit_price' => $this->preferredSupplier($product)->pivot->unit_cost,
                ]);
            }

            $total = $order->calculateTotal();

            // warn when the order is too small for the supplier
            if ($total < (float) $supplier->minimum_order_amount) {
                $order->update([
                    'notes' => $order->notes . ' Below the supplier minimum of $'
                        . number_format($supplier->minimum_order_amount, 2) . '. Review before submitting.',
                ]);
            }

            $order->log('created', 'Auto-generated by the reorder check for ' . $products->count() . ' product(s).');

            return $order;
        });
    }

    // the active preferred supplier for a product
    public function preferredSupplier(Product $product): ?Supplier
    {
        return $product->suppliers->first(
            fn (Supplier $supplier) => $supplier->pivot->is_preferred && $supplier->is_active
        );
    }
}
