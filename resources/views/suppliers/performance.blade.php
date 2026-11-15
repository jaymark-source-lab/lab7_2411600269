@extends('layouts.fittrack')

@section('title', 'Supplier Performance')

@section('content')
    @php
        // badge style per rating
        $ratingBadge = [
            'Excellent' => 'badge-completed',
            'Good'      => 'badge-completed',
            'Fair'      => 'badge-scheduled',
            'Poor'      => 'badge-missed',
            'No data'   => 'bg-light text-secondary',
        ];
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0">Supplier Performance Report</h4>
            <div class="small text-muted">Score = 60% on-time delivery + 40% order accuracy &middot; updated every 10 minutes or when an order changes</div>
        </div>
        <div class="d-flex gap-2">
            <x-export-buttons report="supplier-performance" />
            <a href="{{ route('suppliers.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Suppliers</a>
        </div>
    </div>

    {{-- summary --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="ft-stat-card">
                <div class="ft-stat-icon teal"><i class="bi bi-truck"></i></div>
                <div class="ft-stat-label">Suppliers Rated</div>
                <div class="ft-stat-value">{{ $summary['suppliers'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ft-stat-card">
                <div class="ft-stat-icon orange"><i class="bi bi-clock-history"></i></div>
                <div class="ft-stat-label">Avg. On-Time</div>
                <div class="ft-stat-value">{{ $summary['on_time'] !== null ? round($summary['on_time'], 1) . '%' : '-' }}</div>
                <div class="ft-stat-delta">accuracy {{ $summary['accuracy'] !== null ? round($summary['accuracy'], 1) . '%' : '-' }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ft-stat-card">
                <div class="ft-stat-icon teal-light"><i class="bi bi-calendar-check"></i></div>
                <div class="ft-stat-label">Avg. Delivery Time</div>
                <div class="ft-stat-value">{{ $summary['avg_days'] !== null ? round($summary['avg_days'], 1) . ' days' : '-' }}</div>
                <div class="ft-stat-delta">quoted {{ $summary['avg_lead'] !== null ? round($summary['avg_lead'], 1) . ' days' : '-' }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ft-stat-card">
                <div class="ft-stat-icon purple"><i class="bi bi-cash-stack"></i></div>
                <div class="ft-stat-label">Total Order Value</div>
                <div class="ft-stat-value">${{ number_format($summary['value'], 2) }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- delivery trend chart --}}
        <div class="col-lg-6">
            <div class="ft-panel">
                <div class="ft-panel-title"><i class="bi bi-graph-up"></i> Delivery Trend (last 6 months)</div>
                <canvas id="trendChart" height="220"></canvas>
            </div>
        </div>

        {{-- top and bottom performers --}}
        <div class="col-lg-3">
            <div class="ft-panel">
                <div class="ft-panel-title"><i class="bi bi-trophy text-warning"></i> Top Performers</div>
                @forelse ($top as $row)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <a href="{{ route('suppliers.show', $row['id']) }}" class="text-decoration-none fw-semibold">{{ $row['name'] }}</a>
                        <span class="ft-badge badge-completed">{{ $row['score'] }}</span>
                    </div>
                @empty
                    <p class="small text-muted mb-0">No deliveries yet.</p>
                @endforelse
            </div>
        </div>
        <div class="col-lg-3">
            <div class="ft-panel">
                <div class="ft-panel-title"><i class="bi bi-exclamation-triangle text-danger"></i> Needs Attention</div>
                @forelse ($bottom as $row)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <a href="{{ route('suppliers.show', $row['id']) }}" class="text-decoration-none fw-semibold">{{ $row['name'] }}</a>
                        <span class="ft-badge {{ $ratingBadge[$row['rating']] }}">{{ $row['score'] }}</span>
                    </div>
                @empty
                    <p class="small text-muted mb-0">All suppliers score 75 or higher.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- full table --}}
    <div class="ft-panel">
        <div class="ft-panel-title"><i class="bi bi-table"></i> All Suppliers</div>
        <div class="table-responsive">
            <table class="table ft-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th class="text-end">Orders</th>
                        <th class="text-end">Received</th>
                        <th class="text-end">On-Time</th>
                        <th class="text-end">Avg Days (Quoted)</th>
                        <th class="text-end">Accuracy</th>
                        <th class="text-end">Total Value</th>
                        <th style="min-width: 160px;">Score</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $row)
                        <tr>
                            <td>
                                <a href="{{ route('suppliers.show', $row['id']) }}" class="text-decoration-none fw-semibold">{{ $row['name'] }}</a>
                                @if ($row['deleted'])
                                    <span class="small text-danger">(deleted)</span>
                                @endif
                            </td>
                            <td class="text-end">{{ $row['total_orders'] }}</td>
                            <td class="text-end">{{ $row['received'] }}</td>
                            <td class="text-end">{{ $row['on_time_rate'] !== null ? $row['on_time_rate'] . '%' : '-' }}</td>
                            <td class="text-end">{{ $row['avg_days'] ?? '-' }} ({{ $row['lead_time'] }})</td>
                            <td class="text-end">{{ $row['accuracy'] !== null ? $row['accuracy'] . '%' : '-' }}</td>
                            <td class="text-end">${{ number_format($row['total_value'], 2) }}</td>
                            <td>
                                @if ($row['score'] !== null)
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar" style="width: {{ $row['score'] }}%; background: var(--ft-teal);"></div>
                                        </div>
                                        <span class="ft-badge {{ $ratingBadge[$row['rating']] }} text-nowrap">{{ $row['score'] }} {{ $row['rating'] }}</span>
                                    </div>
                                @else
                                    <span class="ft-badge bg-light text-secondary">No deliveries yet</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No purchase orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // delivery trend: bars = orders received, line = on-time %
    const trend = @json($trend);

    new Chart(document.getElementById('trendChart'), {
        data: {
            labels: trend.map(m => m.label),
            datasets: [
                {
                    type: 'bar',
                    label: 'Orders received',
                    data: trend.map(m => m.received),
                    backgroundColor: '#14a3ad',
                    yAxisID: 'y',
                    order: 1,
                },
                {
                    type: 'line',
                    label: 'On-time %',
                    data: trend.map(m => m.on_time_rate),
                    borderColor: '#ff6b35',
                    backgroundColor: '#ff6b35',
                    tension: 0.3,
                    spanGaps: true,
                    yAxisID: 'y1',
                    order: 0,
                    pointRadius: 6,
                },
            ],
        },
        options: {
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: 'Orders' } },
                y1: { position: 'right', min: 0, max: 100, grid: { drawOnChartArea: false }, title: { display: true, text: 'On-time %' } },
            },
        },
    });
</script>
@endpush
