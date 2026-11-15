@extends('layouts.fittrack')

@section('title', 'Purchase Order')

@section('content')
    @php
        // history icon per action
        $icons = [
            'created'            => 'bi-plus-circle',
            'updated'            => 'bi-pencil',
            'submitted'          => 'bi-send',
            'approved'           => 'bi-check-circle text-success',
            'rejected'           => 'bi-x-circle text-danger',
            'sent'               => 'bi-truck',
            'received'           => 'bi-box-seam text-success',
            'partially received' => 'bi-box-seam',
            'cancelled'          => 'bi-slash-circle text-danger',
            'deleted'            => 'bi-trash text-danger',
        ];

        // can the current user receive items?
        $canReceive = auth()->user()->can('receive', $order);
    @endphp

    {{-- header --}}
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                {{ $order->reference_number }}
                <span class="ft-badge {{ $order->status_badge }} align-middle">{{ $order->status_label }}</span>
                @if ($order->is_auto_generated)
                    <span class="ft-badge bg-light text-secondary align-middle"><i class="bi bi-robot"></i> Auto-generated</span>
                @endif
            </h4>
            <div class="small text-muted">Created by {{ $order->createdBy->name }} on {{ $order->created_at->format('M j, Y g:i A') }}</div>
        </div>

        {{-- only the actions this user is allowed to take --}}
        <div class="d-flex flex-wrap gap-2">
            @can('update', $order)
                <a href="{{ route('purchase-orders.edit', $order) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i> Edit</a>
            @endcan
            @can('submit', $order)
                <form method="POST" action="{{ route('purchase-orders.submit', $order) }}">
                    @csrf
                    <button class="btn ft-btn-primary"><i class="bi bi-send"></i> Submit for Approval</button>
                </form>
            @endcan
            @can('approve', $order)
                <form method="POST" action="{{ route('purchase-orders.approve', $order) }}">
                    @csrf
                    <button class="btn btn-success"><i class="bi bi-check-lg"></i> Approve</button>
                </form>
                <button class="btn btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#reject-box">
                    <i class="bi bi-x-lg"></i> Reject
                </button>
            @endcan
            @can('send', $order)
                <form method="POST" action="{{ route('purchase-orders.send', $order) }}">
                    @csrf
                    <button class="btn ft-btn-primary"><i class="bi bi-truck"></i> Mark as Sent</button>
                </form>
            @endcan
            @can('cancel', $order)
                <form method="POST" action="{{ route('purchase-orders.cancel', $order) }}" onsubmit="return confirm('Cancel this purchase order?')">
                    @csrf
                    <button class="btn btn-outline-danger"><i class="bi bi-slash-circle"></i> Cancel Order</button>
                </form>
            @endcan
            @can('delete', $order)
                <form method="POST" action="{{ route('purchase-orders.destroy', $order) }}" onsubmit="return confirm('Delete this draft?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            @endcan
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-light">Back</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    {{-- reject reason (opens from the Reject button) --}}
    @can('reject', $order)
        <div class="collapse mb-3" id="reject-box">
            <form method="POST" action="{{ route('purchase-orders.reject', $order) }}" class="ft-panel">
                @csrf
                <label for="rejection_reason" class="form-label fw-semibold">Reason for rejection</label>
                <textarea name="rejection_reason" id="rejection_reason" rows="2" class="form-control mb-2" required
                          placeholder="e.g. Price too high, please negotiate with the supplier"></textarea>
                <button class="btn btn-danger">Confirm Rejection</button>
            </form>
        </div>
    @endcan

    <div class="row g-3">
        {{-- order information --}}
        <div class="col-lg-4">
            <div class="ft-panel">
                <div class="ft-panel-title"><i class="bi bi-info-circle"></i> Order Information</div>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted fw-normal">Supplier</dt>
                    <dd class="col-7">
                        @if ($order->supplier->trashed())
                            {{ $order->supplier->name }} <span class="text-danger">(deleted)</span>
                        @else
                            <a href="{{ route('suppliers.show', $order->supplier) }}" class="text-decoration-none">{{ $order->supplier->name }}</a>
                        @endif
                        <div class="text-muted">{{ $order->supplier->email }}</div>
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Order Date</dt>
                    <dd class="col-7">{{ $order->order_date->format('M j, Y') }}</dd>

                    <dt class="col-5 text-muted fw-normal">Expected Delivery</dt>
                    <dd class="col-7">{{ $order->expected_delivery_date?->format('M j, Y') ?? '—' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Terms</dt>
                    <dd class="col-7">{{ $order->terms ?: '—' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Created By</dt>
                    <dd class="col-7">{{ $order->createdBy->name }}</dd>

                    @if ($order->approvedBy)
                        <dt class="col-5 text-muted fw-normal">{{ $order->status === 'rejected' ? 'Rejected By' : 'Approved By' }}</dt>
                        <dd class="col-7">
                            {{ $order->approvedBy->name }}
                            <div class="text-muted">{{ $order->approved_at?->format('M j, Y g:i A') }}</div>
                        </dd>
                    @endif

                    @if ($order->rejection_reason)
                        <dt class="col-5 text-muted fw-normal">Rejection Reason</dt>
                        <dd class="col-7 text-danger">{{ $order->rejection_reason }}</dd>
                    @endif

                    @if ($order->sent_at)
                        <dt class="col-5 text-muted fw-normal">Sent</dt>
                        <dd class="col-7">{{ $order->sent_at->format('M j, Y g:i A') }}</dd>
                    @endif

                    @if ($order->received_at)
                        <dt class="col-5 text-muted fw-normal">Received</dt>
                        <dd class="col-7">{{ $order->received_at->format('M j, Y g:i A') }}</dd>
                    @endif

                    <dt class="col-5 text-muted fw-normal">Notes</dt>
                    <dd class="col-7 mb-0">{{ $order->notes ?: '—' }}</dd>
                </dl>
            </div>
        </div>

        {{-- line items (+ receive form for warehouse/admin) --}}
        <div class="col-lg-8">
            <div class="ft-panel">
                <div class="ft-panel-title"><i class="bi bi-list-ul"></i> Line Items</div>

                @if ($canReceive)
                    <form method="POST" action="{{ route('purchase-orders.receive', $order) }}">
                        @csrf
                @endif

                <div class="table-responsive">
                    <table class="table ft-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Ordered</th>
                                <th class="text-end">Received</th>
                                <th class="text-end">Outstanding</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Line Total</th>
                                @if ($canReceive)
                                    <th style="width: 110px;">Receive Now</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td>
                                        <a href="{{ route('products.show', $item->product) }}" class="text-decoration-none">{{ $item->product->name }}</a>
                                        <div class="small text-muted">{{ $item->product->sku }}</div>
                                    </td>
                                    <td class="text-end">{{ $item->quantity }}</td>
                                    <td class="text-end {{ $item->received_quantity ? 'text-success' : '' }}">{{ $item->received_quantity }}</td>
                                    <td class="text-end">{{ $item->outstanding_quantity }}</td>
                                    <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-end fw-semibold">${{ number_format($item->line_total, 2) }}</td>
                                    @if ($canReceive)
                                        <td>
                                            <input type="number" name="received[{{ $item->id }}]" class="form-control form-control-sm"
                                                   min="0" max="{{ $item->outstanding_quantity }}" value="{{ $item->outstanding_quantity }}"
                                                   @disabled($item->outstanding_quantity === 0)>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-end text-muted">Subtotal</td>
                                <td class="text-end">${{ number_format($order->subtotal, 2) }}</td>
                                @if ($canReceive) <td></td> @endif
                            </tr>
                            <tr>
                                <td colspan="5" class="text-end text-muted">VAT (12%)</td>
                                <td class="text-end">${{ number_format($order->tax, 2) }}</td>
                                @if ($canReceive) <td></td> @endif
                            </tr>
                            <tr>
                                <td colspan="5" class="text-end fw-bold">Total</td>
                                <td class="text-end fw-bold">${{ number_format($order->total, 2) }}</td>
                                @if ($canReceive) <td></td> @endif
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if ($canReceive)
                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                            <div class="small text-muted">Lower a quantity for a partial delivery. Received items are added to stock.</div>
                            <button class="btn btn-success"><i class="bi bi-box-arrow-in-down"></i> Receive Items</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        {{-- approval history (polymorphic activity log) --}}
        <div class="col-12">
            <div class="ft-panel">
                <div class="ft-panel-title"><i class="bi bi-clock-history"></i> Approval History</div>
                <ul class="list-group list-group-flush">
                    @forelse ($order->activities as $activity)
                        <li class="list-group-item d-flex gap-3 px-0">
                            <i class="bi {{ $icons[$activity->action] ?? 'bi-dot' }} fs-5"></i>
                            <div class="flex-grow-1">
                                <div>
                                    <span class="fw-semibold text-capitalize">{{ $activity->action }}</span>
                                    by {{ $activity->user?->name ?? 'System' }}
                                </div>
                                @if ($activity->description)
                                    <div class="small text-muted">{{ $activity->description }}</div>
                                @endif
                            </div>
                            <div class="small text-muted text-nowrap">{{ $activity->created_at->format('M j, Y g:i A') }}</div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted px-0">No history yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection