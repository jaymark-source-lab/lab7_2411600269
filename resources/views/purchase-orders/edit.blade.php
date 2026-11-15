@extends('layouts.fittrack')

@section('title', 'Edit Purchase Order')

@section('content')
    <h4 class="fw-bold mb-3">Edit {{ $order->reference_number }}</h4>

    <div class="ft-panel">
        <form method="POST" action="{{ route('purchase-orders.update', $order) }}">
            @csrf
            @method('PUT')
            @include('purchase-orders._form')

            <div class="mt-4 d-flex flex-wrap gap-2">
                <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">
                    <i class="bi bi-save"></i> Save Draft
                </button>
                <button type="submit" name="action" value="submit" class="btn ft-btn-primary">
                    <i class="bi bi-send"></i> Save & Submit for Approval
                </button>
                <a href="{{ route('purchase-orders.show', $order) }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
@endsection