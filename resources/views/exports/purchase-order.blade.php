<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $order->reference_number }}</title>
    <style>
        @page { margin: 36px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #222; }
        table { width: 100%; border-collapse: collapse; }
        .brand { font-size: 20px; font-weight: bold; color: #085054; }
        .muted { color: #777; }
        .doc-title { font-size: 18px; font-weight: bold; text-align: right; color: #085054; }
        .boxes td { width: 50%; vertical-align: top; padding: 10px; border: 1px solid #ddd; }
        .label { font-size: 8.5px; text-transform: uppercase; color: #777; letter-spacing: 1px; margin-bottom: 4px; }
        .items th { background: #085054; color: #fff; padding: 6px; text-align: left; font-size: 9px; text-transform: uppercase; }
        .items td { padding: 6px; border-bottom: 1px solid #e2e2e2; }
        .right { text-align: right; }
        .totals td { padding: 4px 6px; }
        .grand td { font-weight: bold; font-size: 12.5px; border-top: 2px solid #085054; }
        .sign td { width: 50%; padding-top: 40px; vertical-align: top; }
        .line { border-top: 1px solid #555; width: 80%; padding-top: 4px; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td>
                <div class="brand">FitTrack Pro</div>
                <div class="muted">Procurement Department<br>procurement@fittrack.test</div>
            </td>
            <td class="doc-title">
                PURCHASE ORDER<br>
                <span style="font-size: 12px; color: #222;">{{ $order->reference_number }}</span>
            </td>
        </tr>
    </table>

    <br>

    <table class="boxes">
        <tr>
            <td>
                <div class="label">Supplier</div>
                <strong>{{ $order->supplier->name }}</strong><br>
                @if ($order->supplier->contact_person)
                    Attn: {{ $order->supplier->contact_person }}<br>
                @endif
                {{ $order->supplier->email }}<br>
                @if ($order->supplier->phone)
                    {{ $order->supplier->phone }}<br>
                @endif
                @if ($order->supplier->address)
                    {{ $order->supplier->address }}
                @endif
            </td>
            <td>
                <div class="label">Order Details</div>
                Order date: {{ \Illuminate\Support\Carbon::parse($order->order_date)->format('M j, Y') }}<br>
                Expected delivery: {{ $order->expected_delivery_date ? \Illuminate\Support\Carbon::parse($order->expected_delivery_date)->format('M j, Y') : 'To be confirmed' }}<br>
                Payment terms: {{ $order->terms ?: $order->supplier->payment_terms }}<br>
                Status: {{ ucfirst($order->status) }}
            </td>
        </tr>
    </table>

    <br>

    <table class="items">
        <thead>
            <tr>
                <th>#</th>
                <th>SKU</th>
                <th>Product</th>
                <th class="right">Qty</th>
                <th class="right">Unit Price</th>
                <th class="right">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->product?->sku }}</td>
                    <td>{{ $item->product?->name ?? 'Deleted product' }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="right">${{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td style="width: 60%;"></td><td>Subtotal</td><td class="right">${{ number_format($order->subtotal, 2) }}</td></tr>
        <tr><td></td><td>VAT (12%)</td><td class="right">${{ number_format($order->tax, 2) }}</td></tr>
        <tr class="grand"><td></td><td>Total</td><td class="right">${{ number_format($order->total, 2) }}</td></tr>
    </table>

    @if ($order->notes)
        <p><span class="label">Notes</span><br>{{ $order->notes }}</p>
    @endif

    <p class="muted">Please put the PO number on all invoices and delivery receipts.</p>

    <table class="sign">
        <tr>
            <td>
                <div class="line">Prepared by: {{ $order->createdBy?->name ?? 'System' }}</div>
            </td>
            <td>
                <div class="line">
                    Approved by: {{ $order->approvedBy?->name ?? '-' }}
                    @if ($order->approved_at)
                        ({{ \Illuminate\Support\Carbon::parse($order->approved_at)->format('M j, Y') }})
                    @endif
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
