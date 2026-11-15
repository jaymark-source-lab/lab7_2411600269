@extends('emails.layout')

@section('title', 'Purchase order approved')
@section('tag', 'Approved')

@section('content')
    <p style="margin-top:0;">Hi {{ $order->createdBy->name }},</p>
    <p>
        Good news! Your purchase order <strong style="color:#085054;">{{ $order->reference_number }}</strong>
        to <strong>{{ $order->supplier->name }}</strong> was
        <span style="color:#1f9d55; font-weight:bold;">approved</span>
        by {{ $order->approvedBy?->name ?? 'a manager' }}@if ($order->approved_at) on {{ $order->approved_at->format('M j, Y g:i A') }}@endif.
    </p>
    <p>
        Order total: <strong>${{ number_format($order->total, 2) }}</strong><br>
        Expected delivery: <strong>{{ $order->expected_delivery_date?->format('M j, Y') ?? '—' }}</strong>
    </p>
    <p>It will now be sent to the supplier.</p>

    <p style="text-align:center; margin:24px 0;">
        <a href="{{ route('purchase-orders.show', $order) }}"
           style="background:#0b6e73; color:#ffffff; padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:bold;">
            View Order
        </a>
    </p>
@endsection
