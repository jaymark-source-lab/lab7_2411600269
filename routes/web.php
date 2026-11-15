<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WorkoutController;
use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\Api\InventoryApiController;
use App\Http\Controllers\Api\ProcurementApiController;
use App\Mail\LowStockAlert;
use App\Mail\PurchaseOrderApproved;
use App\Mail\PurchaseOrderCreated;
use App\Mail\PurchaseOrderSent;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

// logged-in users only
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('products', ProductController::class);

    // suppliers (restore and performance must come before the resource)
    Route::patch('/suppliers/{id}/restore', [SupplierController::class, 'restore'])->name('suppliers.restore');
    Route::get('/suppliers/performance', [SupplierController::class, 'performance'])->name('suppliers.performance');
    Route::resource('suppliers', SupplierController::class);

    // purchase orders + workflow actions
    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::post('/purchase-orders/{purchase_order}/submit', [PurchaseOrderController::class, 'submit'])->name('purchase-orders.submit');
    Route::post('/purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('/purchase-orders/{purchase_order}/reject', [PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
    Route::post('/purchase-orders/{purchase_order}/send', [PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
    Route::post('/purchase-orders/{purchase_order}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::post('/purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');

    // CSV / PDF exports and the printable purchase order
    Route::get('/exports/{report}/{format}', [ExportController::class, 'download'])
        ->whereIn('report', ['products', 'purchase-orders', 'transactions', 'supplier-performance'])
        ->whereIn('format', ['csv', 'pdf'])
        ->name('exports.download');
    Route::get('/purchase-orders/{purchaseOrder}/pdf', [ExportController::class, 'purchaseOrder'])->name('purchase-orders.pdf');

    // preview emails in the browser (local development only)
    if (app()->isLocal()) {
        Route::get('/mail-preview/{type}', function (string $type) {
            $order = PurchaseOrder::with(['supplier', 'items.product', 'createdBy', 'approvedBy'])->latest('id')->firstOrFail();

            return match ($type) {
                'created'   => new PurchaseOrderCreated($order),
                'approved'  => new PurchaseOrderApproved($order),
                'sent'      => new PurchaseOrderSent($order),
                'low-stock' => new LowStockAlert(Product::lowStock()->firstOrFail()),
                default     => abort(404),
            };
        })->name('mail.preview');
    }

    // stock movements (no edit/update: audit trail)
    Route::resource('transactions', InventoryTransactionController::class)->except(['edit', 'update']);

    // stock adjustments (admin only, checked in the controller)
    Route::get('/adjustments/create', [StockAdjustmentController::class, 'create'])->name('adjustments.create');
    Route::post('/adjustments', [StockAdjustmentController::class, 'store'])->name('adjustments.store');

    // inventory reports + CSV export
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    // notifications
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // JSON endpoints for the live dashboard
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/dashboard/stats', [InventoryApiController::class, 'stats'])->name('dashboard.stats');
        Route::get('/products/low-stock', [InventoryApiController::class, 'lowStock'])->name('products.lowStock');
        Route::get('/products/{product}', [InventoryApiController::class, 'product'])->whereNumber('product')->name('products.show');
        Route::get('/transactions/recent', [InventoryApiController::class, 'recentTransactions'])->name('transactions.recent');

        // procurement widgets
        Route::get('/dashboard/pending-orders', [ProcurementApiController::class, 'pendingOrders'])->name('dashboard.pendingOrders');
        Route::get('/dashboard/recent-orders', [ProcurementApiController::class, 'recentOrders'])->name('dashboard.recentOrders');
        Route::get('/dashboard/supplier-performance', [ProcurementApiController::class, 'supplierPerformance'])->name('dashboard.supplierPerformance');
        Route::get('/dashboard/reorder-alerts', [ProcurementApiController::class, 'reorderAlerts'])->name('dashboard.reorderAlerts');
    });

    Route::resource('workouts', WorkoutController::class);
    Route::get('/workouts-data', [WorkoutController::class, 'data'])->name('workouts.data');
    Route::get('/workouts-export', [WorkoutController::class, 'exportCsv'])->name('workouts.export');
});

require __DIR__.'/auth.php';
