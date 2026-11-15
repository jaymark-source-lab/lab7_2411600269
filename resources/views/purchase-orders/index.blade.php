@extends('layouts.fittrack')

@section('title', 'Purchase Orders')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0">Purchase Orders</h4>
        @can('create', App\Models\PurchaseOrder::class)
            <a href="{{ route('purchase-orders.create') }}" class="btn ft-btn-primary">
                <i class="bi bi-plus-lg"></i> New Purchase Order
            </a>
        @endcan
    </div>

    <form method="GET" action="{{ route('purchase-orders.index') }}" class="ft-filter-bar">
        <div class="ft-filter-group">
            <label for="search">Reference</label>
            <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="PO-...">
        </div>
        <div class="ft-filter-group">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-select">
                <option value="">All</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="ft-filter-group">
            <label for="supplier_id">Supplier</label>
            <select name="supplier_id" id="supplier_id" class="form-select">
                <option value="">All suppliers</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="ft-filter-group">
            <label for="from">From</label>
            <input type="date" name="from" id="from" class="form-control" value="{{ request('from') }}">
        </div>
        <div class="ft-filter-group">
            <label for="to">To</label>
            <input type="date" name="to" id="to" class="form-control" value="{{ request('to') }}">
        </div>
        <div class="ft-filter-group">
            <button type="submit" class="btn ft-btn-primary">Filter</button>
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-light">Reset</a>
        </div>
    </form>

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
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('purchase-orders.show', $order) }}" class="fw-semibold text-decoration-none">{{ $order->reference_number }}</a>
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
                            <td class="text-nowrap">{{ $order->order_date->format('M j, Y') }}</td>
                            <td class="text-nowrap">{{ $order->expected_delivery_date?->format('M j, Y') ?? '—' }}</td>
                            <td class="text-end">{{ $order->items_count }}</td>
                            <td class="text-end fw-semibold">${{ number_format($order->total, 2) }}</td>
                            <td><span class="ft-badge {{ $order->status_badge }} text-nowrap">{{ $order->status_label }}</span></td>
                            <td class="small">{{ $order->createdBy->name }}</td>
                            <td class="text-end">
                                <a href="{{ route('purchase-orders.show', $order) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No purchase orders found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $orders->links() }}</div>
    </div>
@endsection