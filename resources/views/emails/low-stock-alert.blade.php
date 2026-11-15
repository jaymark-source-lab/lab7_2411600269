@extends('emails.layout')

@section('title', 'Low stock alert')
@section('tag', 'Low Stock Alert')

@section('content')
    <p style="margin-top:0;">Hello,</p>
    <p>
        <strong>{{ $product->name }}</strong> ({{ $product->sku }}) has dropped to its reorder level.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;">
        <tr>
            <td align="center" style="background:#f9e9e8; border-radius:8px; padding:14px;">
                <div style="font-size:12px; color:#6b7b7c; text-transform:uppercase;">In stock</div>
                <div style="font-size:28px; font-weight:bold; color:#e0524d;">{{ $product->quantity }}</div>
            </td>
            <td width="12"></td>
            <td align="center" style="background:#f4f7f7; border-radius:8px; padding:14px;">
                <div style="font-size:12px; color:#6b7b7c; text-transform:uppercase;">Reorder level</div>
                <div style="font-size:28px; font-weight:bold; color:#085054;">{{ $product->reorder_level }}</div>
            </td>
        </tr>
    </table>

    <p>
        <strong>Suggested action:</strong> reorder from
        <strong>{{ $supplier?->name ?? ($product->supplier ?: 'your supplier') }}</strong>
        @if ($supplier)
            ({{ $supplier->lead_time_days }}-day lead time).
        @endif
    </p>

    <p style="text-align:center; margin:24px 0;">
        <a href="{{ route('purchase-orders.create', ['supplier_id' => $supplier?->id]) }}"
           style="background:#ff6b35; color:#ffffff; padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:bold;">
            Create Purchase Order
        </a>
    </p>
@endsection