<?php

namespace App\Exports;

use App\Services\SupplierPerformanceService;

class SupplierPerformanceExport extends Export
{
    // uses the cached performance report
    public function __construct(private SupplierPerformanceService $service)
    {
    }

    // report title
    public function title(): string
    {
        return 'Supplier Performance';
    }

    // explain the score instead of filters
    public function summary(): string
    {
        return 'Score = 60% on-time delivery + 40% order accuracy';
    }

    // column headings
    public function headings(): array
    {
        return ['Supplier', 'Orders', 'Received', 'On-Time %', 'Avg Days', 'Quoted Days', 'Accuracy %', 'Total Value', 'Score', 'Rating'];
    }

    // number columns
    public function rightAligned(): array
    {
        return [1, 2, 3, 4, 5, 6, 7, 8];
    }

    // one row per supplier
    public function rows(): iterable
    {
        foreach ($this->service->report() as $row) {
            yield [
                $row['name'] . ($row['deleted'] ? ' (deleted)' : ''),
                $row['total_orders'],
                $row['received'],
                $row['on_time_rate'] ?? '',
                $row['avg_days'] ?? '',
                $row['lead_time'],
                $row['accuracy'] ?? '',
                $this->money($row['total_value']),
                $row['score'] ?? '',
                $row['rating'],
            ];
        }
    }
}
