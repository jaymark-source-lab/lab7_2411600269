@extends('emails.layout')

@section('title', 'Approval needed')
@section('tag', 'Approval needed')

@section('content')
    <p style="margin-top:0;">Hello,</p>
    <p>
        <strong>{{ $order->createdBy->name }}</strong> submitted purchase order
        <strong style="color:#085054;">{{ $order->reference_number }}</strong> for your approval.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="font-size:14px; margin:12px 0;">
        <tr><td style="padding:2px 16px 2px 0; color:#6b7b7c;">Supplier</td><td>{{ $order->supplier->name }}</td></tr>
        <tr><td style="padding:2px 16px 2px 0; color:#6b7b7c;">Order date</td><td>{{ $order->order_date->format('M j, Y') }}</td></tr>
        <tr><td style="padding:2px 16px 2px 0; color:#6b7b7c;">Expected delivery</td><td>{{ $order->expected_delivery_date?->format('M j, Y') ?? '—' }}</td></tr>
    </table>

    @include('emails.partials.items')

    <p style="text-align:center; margin:24px 0;">
        <a href="{{ route('purchase-orders.show', $order) }}"
           style="background:#0b6e73; color:#ffffff; padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:bold;">
            Review &amp; Approve
        </a>
    </p>
@endsection