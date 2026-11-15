{{-- widget: best on-time suppliers ($topSuppliers comes from ViewServiceProvider, cached) --}}
<div class="ft-panel">
    <div class="ft-panel-title"><i class="bi bi-trophy text-warning"></i> Supplier Performance</div>

    @forelse ($topSuppliers as $row)
        <div class="mb-3">
            <div class="d-flex justify-content-between small mb-1">
                <a href="{{ route('suppliers.show', $row['id']) }}" class="text-decoration-none fw-semibold">{{ $row['name'] }}</a>
                <span class="text-muted">{{ $row['on_time_rate'] }}% on time &middot; {{ $row['orders'] }} order(s)</span>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar" style="width: {{ $row['on_time_rate'] }}%; background: var(--ft-teal);"></div>
            </div>
        </div>
    @empty
        <p class="text-muted small mb-0">No received orders yet. Ratings appear after the first delivery.</p>
    @endforelse
</div>
