@extends('emails.layout')

@section('title', 'Purchase order')
@section('tag', 'Purchase Order')

@section('content')
    <p style="margin-top:0;">Dear {{ $order->supplier->contact_person ?: $order->supplier->name }},</p>
    <p>
        Please find below our purchase order <strong style="color:#085054;">{{ $order->reference_number }}</strong>.
        Kindly confirm receipt and the delivery date.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="font-size:14px; margin:12px 0;">
        <tr><td style="padding:2px 16px 2px 0; color:#6b7b7c;">Order date</td><td>{{ $order->order_date->format('M j, Y') }}</td></tr>
        <tr><td style="padding:2px 16px 2px 0; color:#6b7b7c;">Requested delivery</td><td>{{ $order->expected_delivery_date?->format('M j, Y') ?? '—' }}</td></tr>
        <tr><td style="padding:2px 16px 2px 0; color:#6b7b7c;">Payment terms</td><td>{{ $order->terms ?: $order->supplier->payment_terms }}</td></tr>
    </table>

    @include('emails.partials.items')

    @if ($order->notes)
        <p style="background:#f4f7f7; padding:12px; border-radius:8px; font-size:14px;"><strong>Notes:</strong> {{ $order->notes }}</p>
    @endif

    <p>
        Thank you,<br>
        <strong>{{ $order->approvedBy?->name ?? 'Procurement Team' }}</strong><br>
        FitTrack Pro
    </p>
@endsection