{{-- widget: orders waiting for approval ($pending comes from ViewServiceProvider) --}}
<div class="ft-panel">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="ft-panel-title mb-0"><i class="bi bi-hourglass-split text-warning"></i> Pending Approval</div>
        <a href="{{ route('purchase-orders.index', ['status' => 'pending']) }}" class="small text-decoration-none">View all</a>
    </div>

    <div class="d-flex align-items-baseline gap-2 mb-3">
        <div class="ft-stat-value" id="widget-pending-count">{{ $pending['count'] }}</div>
        <div class="small text-muted">orders worth <span id="widget-pending-value">${{ number_format($pending['value'], 2) }}</span></div>
    </div>

    <ul class="list-group list-group-flush small">
        @forelse ($pending['orders'] as $order)
            <li class="list-group-item px-0 d-flex justify-content-between gap-2">
                <a href="{{ route('purchase-orders.show', $order) }}" class="text-decoration-none fw-semibold">{{ $order->reference_number }}</a>
                <span class="text-muted text-truncate">{{ $order->supplier->name }}</span>
                <span>${{ number_format($order->total, 2) }}</span>
            </li>
        @empty
            <li class="list-group-item px-0 text-muted">Nothing is waiting for approval.</li>
        @endforelse
    </ul>
</div>
