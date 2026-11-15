{{-- widget: products that need reordering ($reorderAlerts comes from ViewServiceProvider) --}}
<div class="ft-panel">
    <div class="ft-panel-title">
        <i class="bi bi-arrow-repeat text-danger"></i> Reorder Alerts
        <span class="badge rounded-pill bg-danger">{{ $reorderAlerts->count() }}</span>
    </div>

    <ul class="list-group list-group-flush small">
        @forelse ($reorderAlerts as $alert)
            <li class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2">
                <div>
                    <a href="{{ route('products.show', $alert['id']) }}" class="text-decoration-none fw-semibold">{{ $alert['name'] }}</a>
                    <div class="text-muted">
                        Stock {{ $alert['quantity'] }} / reorder at {{ $alert['reorder_point'] }}
                        &middot; suggest {{ $alert['suggested_qty'] }} from {{ $alert['supplier'] ?? 'no supplier' }}
                    </div>
                </div>
                @can('create', App\Models\PurchaseOrder::class)
                    @if ($alert['supplier_id'])
                        <a href="{{ route('purchase-orders.create', ['supplier_id' => $alert['supplier_id']]) }}" class="btn btn-sm ft-btn-primary text-nowrap">
                            <i class="bi bi-plus-lg"></i> PO
                        </a>
                    @endif
                @endcan
            </li>
        @empty
            <li class="list-group-item px-0 text-muted">All products are above their reorder point or already on order.</li>
        @endforelse
    </ul>
</div>
