<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Services\ProcurementStats;

class ProcurementApiController extends Controller
{
    public function __construct(private ProcurementStats $stats)
    {
    }

    // GET /api/dashboard/pending-orders
    public function pendingOrders()
    {
        $pending = $this->stats->pendingOrders();

        return response()->json([
            'count'  => $pending['count'],
            'value'  => $pending['value'],
            'orders' => $pending['orders']->map(fn ($order) => $this->order($order)),
        ]);
    }

    // GET /api/dashboard/recent-orders
    public function recentOrders()
    {
        return response()->json([
            'data' => $this->stats->recentOrders()->map(fn ($order) => $this->order($order)),
        ]);
    }

    // GET /api/dashboard/supplier-performance
    public function supplierPerformance()
    {
        return response()->json(['data' => $this->stats->supplierPerformance()]);
    }

    // GET /api/dashboard/reorder-alerts
    public function reorderAlerts()
    {
        return response()->json(['data' => $this->stats->reorderAlerts()]);
    }

    // one order as JSON
    private function order(PurchaseOrder $order): array
    {
        return [
            'id'           => $order->id,
            'reference'    => $order->reference_number,
            'supplier'     => $order->supplier->name,
            'status'       => $order->status,
            'status_label' => $order->status_label,
            'total'        => (float) $order->total,
            'url'          => route('purchase-orders.show', $order),
        ];
    }
}
