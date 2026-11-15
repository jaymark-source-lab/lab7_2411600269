@extends('layouts.fittrack')

@section('title', 'Edit Supplier')

@section('content')
    <h4 class="fw-bold mb-3">Edit Supplier</h4>

    <div class="ft-panel" style="max-width: 820px;">
        <form method="POST" action="{{ route('suppliers.update', $supplier) }}">
            @csrf
            @method('PUT')
            @include('suppliers._form')

            <div class="mt-4">
                <button type="submit" class="btn ft-btn-primary">Update Supplier</button>
                <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
@endsection