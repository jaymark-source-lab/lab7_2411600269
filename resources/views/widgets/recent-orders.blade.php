{{-- widget: newest purchase orders ($recentOrders comes from ViewServiceProvider) --}}
<div class="ft-panel">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="ft-panel-title mb-0"><i class="bi bi-receipt"></i> Recent Purchase Orders</div>
        <a href="{{ route('purchase-orders.index') }}" class="small text-decoration-none">View all</a>
    </div>

    <div class="table-responsive">
        <table class="table ft-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Supplier</th>
                    <th>Status</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentOrders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('purchase-orders.show', $order) }}" class="text-decoration-none fw-semibold">{{ $order->reference_number }}</a>
                            @if ($order->is_auto_generated)
                                <i class="bi bi-robot text-muted" title="Auto-generated"></i>
                            @endif
                        </td>
                        <td>{{ $order->supplier->name }}</td>
                        <td><span class="ft-badge {{ $order->status_badge }} text-nowrap">{{ $order->status_label }}</span></td>
                        <td class="text-end">${{ number_format($order->total, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">No purchase orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
