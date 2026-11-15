<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SupplierPerformanceService
{
    public const CACHE_KEY = 'reports.supplier-performance';

    // metrics for every supplier that has orders (cached 10 minutes)
    public function report(): Collection
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function () {
            // received orders that needed more than one delivery, per supplier
            $partial = DB::table('activity_logs')
                ->join('purchase_orders', 'purchase_orders.id', '=', 'activity_logs.subject_id')
                ->where('activity_logs.subject_type', PurchaseOrder::class)
                ->where('activity_logs.action', 'partially received')
                ->where('purchase_orders.status', 'received')
                ->groupBy('purchase_orders.supplier_id')
                ->selectRaw('purchase_orders.supplier_id, COUNT(DISTINCT purchase_orders.id) AS orders')
                ->pluck('orders', 'supplier_id');

            // one row per supplier with all counts
            $rows = DB::table('purchase_orders')
                ->whereNull('deleted_at')
                ->whereNotIn('status', ['draft', 'cancelled', 'rejected'])
                ->groupBy('supplier_id')
                ->selectRaw("supplier_id,
                    COUNT(*) AS total_orders,
                    SUM(status = 'received') AS received,
                    SUM(status = 'received' AND DATE(received_at) <= expected_delivery_date) AS on_time,
                    AVG(CASE WHEN status = 'received' THEN DATEDIFF(received_at, order_date) END) AS avg_days,
                    SUM(total) AS total_value")
                ->get();

            $suppliers = Supplier::withTrashed()->whereIn('id', $rows->pluck('supplier_id'))->get()->keyBy('id');

            return $rows->map(function ($row) use ($suppliers, $partial) {
                $supplier = $suppliers[$row->supplier_id];
                $received = (int) $row->received;

                $onTimeRate = $received ? round($row->on_time / $received * 100, 1) : null;
                $accuracy = $received ? round(($received - ($partial[$row->supplier_id] ?? 0)) / $received * 100, 1) : null;
                $score = $received ? (int) round($onTimeRate * 0.6 + $accuracy * 0.4) : null;

                return [
                    'id'           => $supplier->id,
                    'name'         => $supplier->name,
                    'deleted'      => $supplier->trashed(),
                    'lead_time'    => $supplier->lead_time_days,
                    'total_orders' => (int) $row->total_orders,
                    'received'     => $received,
                    'on_time_rate' => $onTimeRate,
                    'avg_days'     => $row->avg_days !== null ? round($row->avg_days, 1) : null,
                    'accuracy'     => $accuracy,
                    'total_value'  => (float) $row->total_value,
                    'score'        => $score,
                    'rating'       => $this->rating($score),
                ];
            })->sortByDesc(fn ($row) => $row['score'] ?? -1)->values();
        });
    }

    // deliveries per month for the trend chart (last 6 months)
    public function trend(): Collection
    {
        $rows = DB::table('purchase_orders')
            ->whereNull('deleted_at')
            ->where('status', 'received')
            ->where('received_at', '>=', now()->startOfMonth()->subMonths(5))
            ->groupByRaw("DATE_FORMAT(received_at, '%Y-%m')")
            ->selectRaw("DATE_FORMAT(received_at, '%Y-%m') AS month, COUNT(*) AS received,
                SUM(DATE(received_at) <= expected_delivery_date) AS on_time,
                AVG(DATEDIFF(received_at, order_date)) AS avg_days")
            ->get()
            ->keyBy('month');

        // one entry per month, including months with no deliveries
        return collect(range(5, 0))->map(function ($i) use ($rows) {
            $month = now()->startOfMonth()->subMonths($i);
            $row = $rows[$month->format('Y-m')] ?? null;

            return [
                'label'        => $month->format('M Y'),
                'received'     => (int) ($row->received ?? 0),
                'on_time_rate' => $row && $row->received ? round($row->on_time / $row->received * 100, 1) : null,
                'avg_days'     => $row ? round($row->avg_days, 1) : null,
            ];
        })->values();
    }

    // label for a score
    public function rating(?int $score): string
    {
        return match (true) {
            $score === null => 'No data',
            $score >= 90    => 'Excellent',
            $score >= 75    => 'Good',
            $score >= 50    => 'Fair',
            default         => 'Poor',
        };
    }
}
