@extends('layouts.fittrack')

@section('title', 'Add Supplier')

@section('content')
    <h4 class="fw-bold mb-3">Add New Supplier</h4>

    <div class="ft-panel" style="max-width: 820px;">
        <form method="POST" action="{{ route('suppliers.store') }}">
            @csrf
            @include('suppliers._form')

            <div class="mt-4">
                <button type="submit" class="btn ft-btn-primary">Save Supplier</button>
                <a href="{{ route('suppliers.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
@endsection
