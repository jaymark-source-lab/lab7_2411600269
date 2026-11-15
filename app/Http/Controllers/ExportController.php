<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Exports\PurchaseOrderPdf;
use App\Exports\PurchaseOrdersExport;
use App\Exports\SupplierPerformanceExport;
use App\Exports\TransactionsExport;
use App\Models\PurchaseOrder;
use App\Services\SearchService;
use App\Services\SupplierPerformanceService;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    // download a list as CSV or PDF with the current filters
    public function download(Request $request, string $report, string $format, SearchService $search, SupplierPerformanceService $performance)
    {
        $export = match ($report) {
            'products'             => new ProductsExport($search, $request->only(['search', 'category', 'supplier_id', 'stock', 'sort'])),
            'purchase-orders'      => new PurchaseOrdersExport($search, $request->only(['search', 'status', 'supplier_id', 'source', 'from', 'to'])),
            'transactions'         => new TransactionsExport($search, $request->only(['search', 'product_id', 'type', 'user_id', 'from', 'to'])),
            'supplier-performance' => new SupplierPerformanceExport($performance),
            default                => abort(404),
        };

        return $format === 'pdf' ? $export->toPdf() : $export->toCsv();
    }

    // printable purchase order (opens in the browser)
    public function purchaseOrder(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        return PurchaseOrderPdf::make($purchaseOrder)->stream($purchaseOrder->reference_number . '.pdf');
    }
}
