<?php

namespace App\Providers;

use App\Models\PurchaseOrder;
use App\Services\ProcurementStats;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class ViewServiceProvider extends ServiceProvider
{
    // register services
    public function register(): void
    {
        //
    }

    // attach data to widget views automatically
    public function boot(): void
    {
        View::composer('widgets.pending-orders', function (ViewInstance $view) {
            $view->with('pending', app(ProcurementStats::class)->pendingOrders());
        });

        View::composer('widgets.recent-orders', function (ViewInstance $view) {
            $view->with('recentOrders', app(ProcurementStats::class)->recentOrders());
        });

        View::composer('widgets.supplier-performance', function (ViewInstance $view) {
            $view->with('topSuppliers', app(ProcurementStats::class)->supplierPerformance());
        });

        View::composer('widgets.reorder-alerts', function (ViewInstance $view) {
            $view->with('reorderAlerts', app(ProcurementStats::class)->reorderAlerts());
        });

        // clear cached supplier stats whenever an order changes
        PurchaseOrder::saved(fn () => Cache::forget(ProcurementStats::CACHE_KEY));
    }
}
