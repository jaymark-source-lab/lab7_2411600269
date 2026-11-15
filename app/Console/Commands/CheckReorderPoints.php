<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\PurchaseOrdersGenerated;
use App\Services\ReorderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckReorderPoints extends Command
{
    protected $signature = 'inventory:check-reorder {--dry-run : List products without creating orders}';

    protected $description = 'Create draft purchase orders for products at or below their reorder point';

    // run the reorder check
    public function handle(ReorderService $service): int
    {
        $products = $service->getProductsNeedingReorder();

        if ($products->isEmpty()) {
            $this->info('No products need reordering (all are above their reorder point or already on order).');

            return self::SUCCESS;
        }

        $this->table(
            ['Product', 'Stock', 'Reorder Point', 'Daily Usage', 'Order Qty'],
            $products->map(fn ($p) => [
                $p->name,
                $p->quantity,
                $service->calculateReorderPoint($p),
                $service->averageDailyUsage($p),
                $service->calculateOptimalOrderQuantity($p),
            ])->all()
        );

        if ($this->option('dry-run')) {
            $this->comment('Dry run: no purchase orders were created.');

            return self::SUCCESS;
        }

        $result = $service->generatePurchaseOrders();

        foreach ($result['orders'] as $order) {
            $this->line("Created {$order->reference_number} for {$order->supplier->name}: "
                . $order->items()->count() . ' item(s), $' . number_format($order->total, 2));
        }

        foreach ($result['skipped'] as $product) {
            $this->warn("Skipped {$product->name}: no active preferred supplier.");
        }

        // tell admins and managers
        if ($result['orders']->isNotEmpty()) {
            Notification::send(
                User::whereIn('role', ['admin', 'manager'])->get(),
                new PurchaseOrdersGenerated($result['orders'])
            );
        }

        $this->info($result['orders']->count() . ' draft purchase order(s) created.');

        return self::SUCCESS;
    }
}