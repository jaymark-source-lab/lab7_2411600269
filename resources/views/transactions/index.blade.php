@extends('layouts.fittrack')

@section('title', 'Transactions')

@section('content')
    @php
        // badge style per type
        $badges = [
            'stock_in'   => 'badge-completed',
            'stock_out'  => 'badge-missed',
            'adjustment' => 'badge-scheduled',
        ];
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0">Stock Transactions</h4>
        <div class="d-flex gap-2">
            @can('admin')
                <a href="{{ route('adjustments.create') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-sliders"></i> Stock Adjustment
                </a>
            @endcan
            <a href="{{ route('transactions.create') }}" class="btn ft-btn-primary">
                <i class="bi bi-plus-lg"></i> Record Transaction
            </a>
        </div>
    </div>

    {{-- errors, e.g. a delete that would make stock negative --}}
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <x-filters.bar :action="route('transactions.index')" :count="$transactions->total()" export="transactions">
        <x-filters.search placeholder="Product, SKU or reference" />
        <x-filters.select name="product_id" label="Product" :options="$products" placeholder="All products" />
        <x-filters.select name="type" label="Type" :options="$types" placeholder="All types" />
        <x-filters.select name="user_id" label="Recorded By" :options="$users" placeholder="Anyone" />
        <x-filters.date-range />
    </x-filters.bar>

    <div class="ft-panel">
        <div class="table-responsive">
            <table class="table ft-table align-middle">
                <thead>
                    <tr>
                        <th>Date / Time</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th class="text-end">Quantity</th>
                        <th>Reference</th>
                        <th>Recorded By</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->created_at->format('M j, Y g:i A') }}</td>
                            <td>
                                <a href="{{ route('products.show', $transaction->product) }}" class="text-decoration-none">
                                    {{ $transaction->product->name }}
                                </a>
                                <div class="small text-muted">{{ $transaction->product->sku }}</div>
                            </td>
                            <td>
                                <span class="ft-badge {{ $badges[$transaction->type] ?? '' }}">
                                    {{ $types[$transaction->type] ?? $transaction->type }}
                                </span>
                            </td>
                            <td class="text-end fw-semibold {{ $transaction->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                {{ $transaction->quantity > 0 ? '+' : '' }}{{ $transaction->quantity }}
                            </td>
                            <td>{{ $transaction->reference_number ?: '-' }}</td>
                            <td>{{ $transaction->user?->name ?? 'System' }}</td>
                            <td class="text-end">
                                <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('admin')
                                    <form action="{{ route('transactions.destroy', $transaction) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this transaction? Its stock change will be reversed.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No transactions match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $transactions->links() }}
        </div>
    </div>
@endsection
