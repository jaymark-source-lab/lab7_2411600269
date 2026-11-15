@extends('layouts.fittrack')

@section('title', 'New Purchase Order')

@section('content')
    <h4 class="fw-bold mb-3">New Purchase Order</h4>

    <div class="ft-panel">
        <form method="POST" action="{{ route('purchase-orders.store') }}">
            @csrf
            @include('purchase-orders._form')

            <div class="mt-4 d-flex flex-wrap gap-2">
                <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">
                    <i class="bi bi-save"></i> Save as Draft
                </button>
                <button type="submit" name="action" value="submit" class="btn ft-btn-primary">
                    <i class="bi bi-send"></i> Submit for Approval
                </button>
                <a href="{{ route('purchase-orders.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
@endsection