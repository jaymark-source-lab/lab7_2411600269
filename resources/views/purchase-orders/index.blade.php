@extends('layouts.fittrack')

@section('title', 'Purchase Orders')

@section('content')
    @php
        // badge style per status
        $badges = [
            'draft'     => 'bg-light text-secondary',
            'pending'   => 'badge-scheduled',
            'approved'  => 'badge-completed',
            'rejected'  => 'badge-missed',
            'sent'      => 'badge-scheduled',
            'received'  => 'badge-completed',
            'cancelled' => 'badge-missed',
        ];
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0">Purchase Orders</h4>
        @can('create', App\Models\PurchaseOrder::class)
            <a href="{{ route('purchase-orders.create') }}" class="btn ft-btn-primary">
                <i class="bi bi-plus-lg"></i> New Purchase Order
            </a>
        @endcan
    </div>

    <x-filters.bar :action="route('purchase-orders.index')" :count="$orders->total()" export="purchase-orders">
        <x-filters.search placeholder="Reference or supplier" />
        <x-filters.select name="status" label="Status" :options="$statuses" placeholder="All statuses" />
        <x-filters.select name="supplier_id" label="Supplier" :options="$suppliers" placeholder="All suppliers" />
        <x-filters.select name="source" label="Source" :options="$sources" placeholder="All" />
        <x-filters.date-range />
    </x-filters.bar>

    <div class="ft-panel">
        <div class="table-responsive">
            <table class="table ft-table align-middle">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Supplier</th>
                        <th>Order Date</th>
                        <th>Expected</th>
                        <th class="text-end">Items</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('purchase-orders.show', $order) }}" class="text-decoration-none fw-semibold">{{ $order->reference_number }}</a>
                                @if ($order->is_auto_generated)
                                    <div class="small text-muted"><i class="bi bi-robot"></i> auto</div>
                                @endif
                            </td>
                            <td>
                                {{ $order->supplier->name }}
                                @if ($order->supplier->trashed())
                                    <span class="small text-danger">(deleted)</span>
                                @endif
                            </td>
                            <td>{{ \Illuminate\Support\Carbon::parse($order->order_date)->format('M j, Y') }}</td>
                            <td>{{ $order->expected_delivery_date ? \Illuminate\Support\Carbon::parse($order->expected_delivery_date)->format('M j, Y') : '-' }}</td>
                            <td class="text-end">{{ $order->items_count }}</td>
                            <td class="text-end fw-semibold">${{ number_format($order->total, 2) }}</td>
                            <td><span class="ft-badge {{ $badges[$order->status] ?? '' }}">{{ ucfirst($order->status) }}</span></td>
                            <td class="small">{{ $order->createdBy?->name ?? 'System' }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('purchase-orders.pdf', $order) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                                <a href="{{ route('purchase-orders.show', $order) }}" class="btn btn-sm btn-outline-secondary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No purchase orders match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $orders->links() }}
        </div>
    </div>
@endsection
