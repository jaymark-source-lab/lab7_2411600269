{{-- line items table shared by PO emails --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; font-size:14px; margin:16px 0;">
    <tr style="background:#f4f7f7; color:#6b7b7c; text-transform:uppercase; font-size:11px;">
        <th align="left" style="padding:8px;">Product</th>
        <th align="right" style="padding:8px;">Qty</th>
        <th align="right" style="padding:8px;">Unit Price</th>
        <th align="right" style="padding:8px;">Total</th>
    </tr>
    @foreach ($order->items as $item)
        <tr style="border-bottom:1px solid #e7edee;">
            <td style="padding:8px;">{{ $item->product->name }}<br><span style="color:#6b7b7c; font-size:12px;">{{ $item->product->sku }}</span></td>
            <td align="right" style="padding:8px;">{{ $item->quantity }}</td>
            <td align="right" style="padding:8px;">${{ number_format($item->unit_price, 2) }}</td>
            <td align="right" style="padding:8px;">${{ number_format($item->line_total, 2) }}</td>
        </tr>
    @endforeach
    <tr><td colspan="3" align="right" style="padding:6px 8px; color:#6b7b7c;">Subtotal</td><td align="right" style="padding:6px 8px;">${{ number_format($order->subtotal, 2) }}</td></tr>
    <tr><td colspan="3" align="right" style="padding:6px 8px; color:#6b7b7c;">VAT (12%)</td><td align="right" style="padding:6px 8px;">${{ number_format($order->tax, 2) }}</td></tr>
    <tr><td colspan="3" align="right" style="padding:6px 8px; font-weight:bold;">Total</td><td align="right" style="padding:6px 8px; font-weight:bold; color:#085054;">${{ number_format($order->total, 2) }}</td></tr>
</table>