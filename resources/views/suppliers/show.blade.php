@extends('layouts.fittrack')

@section('title', 'Supplier Details')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0">{{ $supplier->name }}</h4>
            <div class="small text-muted">{{ $supplier->full_contact }}</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="ft-badge {{ $supplier->is_active ? 'badge-completed' : 'badge-scheduled' }}">
                {{ $supplier->is_active ? 'Active' : 'Inactive' }}
            </span>
            @can('create', App\Models\PurchaseOrder::class)
                @if ($supplier->is_active)
                    <a href="{{ route('purchase-orders.create', ['supplier_id' => $supplier->id]) }}" class="btn ft-btn-primary">
                        <i class="bi bi-receipt"></i> New Purchase Order
                    </a>
                @endif
            @endcan
        </div>
    </div>

    {{-- performance metrics --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="ft-stat-card">
                <div class="ft-stat-label">Total Orders</div>
                <div class="ft-stat-value">{{ $stats['total_orders'] }}</div>
                <div class="ft-stat-delta">{{ $stats['open_orders'] }} open</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ft-stat-card">
                <div class="ft-stat-label">Orders Received</div>
                <div class="ft-stat-value">{{ $stats['received'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ft-stat-card">
                <div class="ft-stat-label">On-Time Delivery</div>
                <div class="ft-stat-value">{{ $stats['on_time'] !== null ? $stats['on_time'] . '%' : '—' }}</div>
                <div class="ft-stat-delta">{{ $stats['on_time'] === null ? 'no deliveries yet' : 'of received orders' }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ft-stat-card">
                <div class="ft-stat-label">Value Received</div>
                <div class="ft-stat-value">${{ number_format($stats['total_value'], 2) }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- supplier details --}}
        <div class="col-lg-5">
            <div class="ft-panel">
                <div class="ft-panel-title"><i class="bi bi-building"></i> Supplier Details</div>
                <dl class="row mb-0">
                    <dt class="col-sm-5 text-muted fw-normal">Contact Person</dt>
                    <dd class="col-sm-7">{{ $supplier->contact_person ?: '—' }}</dd>

                    <dt class="col-sm-5 text-muted fw-normal">Email</dt>
                    <dd class="col-sm-7 text-break">{{ $supplier->email }}</dd>

                    <dt class="col-sm-5 text-muted fw-normal">Phone</dt>
                    <dd class="col-sm-7">{{ $supplier->phone ?: '—' }}</dd>

                    <dt class="col-sm-5 text-muted fw-normal">Address</dt>
                    <dd class="col-sm-7">{{ $supplier->address ?: '—' }}</dd>

                    <dt class="col-sm-5 text-muted fw-normal">Payment Terms</dt>
                    <dd class="col-sm-7">{{ $supplier->payment_terms }}</dd>

                    <dt class="col-sm-5 text-muted fw-normal">Lead Time</dt>
                    <dd class="col-sm-7">{{ $supplier->lead_time_days }} days</dd>

                    <dt class="col-sm-5 text-muted fw-normal">Minimum Order</dt>
                    <dd class="col-sm-7">${{ number_format($supplier->minimum_order_amount, 2) }}</dd>
                </dl>

                <div class="mt-4 d-flex flex-wrap gap-2">
                    <a href="{{ route('suppliers.edit', $supplier) }}" class="btn ft-btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-light">Back</a>
                </div>
            </div>
        </div>

        {{-- products supplied --}}
        <div class="col-lg-7">
            <div class="ft-panel">
                <div class="ft-panel-title"><i class="bi bi-box-seam"></i> Products Supplied ({{ $supplier->products->count() }})</div>
                <div class="table-responsive">
                    <table class="table ft-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Our Stock</th>
                                <th class="text-end">Unit Cost</th>
                                <th class="text-end">Min. Qty</th>
                                <th>Preferred</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($supplier->products as $product)
                                <tr>
                                    <td>
                                        <a href="{{ route('products.show', $product) }}" class="text-decoration-none">{{ $product->name }}</a>
                                        <div class="small text-muted">{{ $product->sku }}</div>
                                    </td>
                                    <td class="text-end">{{ $product->quantity }}</td>
                                    <td class="text-end">${{ number_format($product->pivot->unit_cost, 2) }}</td>
                                    <td class="text-end">{{ $product->pivot->minimum_order_quantity }}</td>
                                    <td>
                                        @if ($product->pivot->is_preferred)
                                            <i class="bi bi-star-fill text-warning"></i>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No products linked yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- recent purchase orders --}}
        <div class="col-12">
            <div class="ft-panel">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="ft-panel-title mb-0"><i class="bi bi-receipt"></i> Recent Purchase Orders</div>
                    <a href="{{ route('purchase-orders.index', ['supplier_id' => $supplier->id]) }}" class="small text-decoration-none">View all</a>
                </div>
                <div class="table-responsive">
                    <table class="table ft-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Order Date</th>
                                <th>Expected</th>
                                <th>Received</th>
                                <th class="text-end">Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentOrders as $order)
                                <tr>
                                    <td><a href="{{ route('purchase-orders.show', $order) }}" class="text-decoration-none fw-semibold">{{ $order->reference_number }}</a></td>
                                    <td>{{ $order->order_date->format('M j, Y') }}</td>
                                    <td>{{ $order->expected_delivery_date?->format('M j, Y') ?? '—' }}</td>
                                    <td>{{ $order->received_at?->format('M j, Y') ?? '—' }}</td>
                                    <td class="text-end">${{ number_format($order->total, 2) }}</td>
                                    <td><span class="ft-badge {{ $order->status_badge }} text-nowrap">{{ $order->status_label }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">No purchase orders yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection