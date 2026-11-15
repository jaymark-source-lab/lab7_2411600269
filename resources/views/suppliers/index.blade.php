@extends('layouts.fittrack')

@section('title', 'Suppliers')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0">Suppliers</h4>
        <a href="{{ route('suppliers.create') }}" class="btn ft-btn-primary">
            <i class="bi bi-plus-lg"></i> Add Supplier
        </a>
    </div>

    <form method="GET" action="{{ route('suppliers.index') }}" class="ft-filter-bar">
        <div class="ft-filter-group">
            <label for="search">Search</label>
            <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}"
                   placeholder="Name, contact or email">
        </div>
        <div class="ft-filter-group">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-select">
                @foreach (['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive', 'trashed' => 'Deleted'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="ft-filter-group">
            <label for="sort">Sort by</label>
            <select name="sort" id="sort" class="form-select">
                @foreach (['name' => 'Name (A–Z)', 'name_desc' => 'Name (Z–A)', 'lead_time' => 'Fastest lead time', 'newest' => 'Newest'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('sort', 'name') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="ft-filter-group">
            <button type="submit" class="btn ft-btn-primary">Filter</button>
            <a href="{{ route('suppliers.index') }}" class="btn btn-light">Reset</a>
        </div>
    </form>

    <div class="ft-panel">
        <div class="table-responsive">
            <table class="table ft-table align-middle">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Email / Phone</th>
                        <th>Terms</th>
                        <th class="text-end">Lead Time</th>
                        <th class="text-end">Min. Order</th>
                        <th class="text-end">Products</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $supplier->name }}</div>
                                <div class="small text-muted">{{ $supplier->contact_person ?: '—' }}</div>
                            </td>
                            <td class="small">
                                {{ $supplier->email }}
                                <div class="text-muted">{{ $supplier->phone ?: '—' }}</div>
                            </td>
                            <td>{{ $supplier->payment_terms }}</td>
                            <td class="text-end">{{ $supplier->lead_time_days }} days</td>
                            <td class="text-end">${{ number_format($supplier->minimum_order_amount, 2) }}</td>
                            <td class="text-end">{{ $supplier->products_count }}</td>
                            <td>
                                @if ($supplier->trashed())
                                    <span class="ft-badge badge-missed text-nowrap">Deleted</span>
                                @elseif ($supplier->is_active)
                                    <span class="ft-badge badge-completed">Active</span>
                                @else
                                    <span class="ft-badge badge-scheduled">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if ($supplier->trashed())
                                    @can('admin')
                                        <form action="{{ route('suppliers.restore', $supplier->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-arrow-counterclockwise"></i> Restore
                                            </button>
                                        </form>
                                    @endcan
                                @else
                                    <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                    @can('admin')
                                        <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete {{ $supplier->name }}? It can be restored later.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No suppliers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $suppliers->links() }}</div>
    </div>
@endsection

@section('title', 'Suppliers')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0">Suppliers</h4>
        <a href="{{ route('suppliers.create') }}" class="btn ft-btn-primary">
            <i class="bi bi-plus-lg"></i> Add Supplier
        </a>
    </div>

    <form method="GET" action="{{ route('suppliers.index') }}" class="ft-filter-bar">
        <div class="ft-filter-group">
            <label for="search">Search</label>
            <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}"
                   placeholder="Name, contact or email">
        </div>
        <div class="ft-filter-group">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-select">
                @foreach (['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive', 'trashed' => 'Deleted'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="ft-filter-group">
            <label for="sort">Sort by</label>
            <select name="sort" id="sort" class="form-select">
                @foreach (['name' => 'Name (A–Z)', 'name_desc' => 'Name (Z–A)', 'lead_time' => 'Fastest lead time', 'newest' => 'Newest'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('sort', 'name') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="ft-filter-group">
            <button type="submit" class="btn ft-btn-primary">Filter</button>
            <a href="{{ route('suppliers.index') }}" class="btn btn-light">Reset</a>
        </div>
    </form>

    <div class="ft-panel">
        <div class="table-responsive">
            <table class="table ft-table align-middle">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Email / Phone</th>
                        <th>Terms</th>
                        <th class="text-end">Lead Time</th>
                        <th class="text-end">Min. Order</th>
                        <th class="text-end">Products</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $supplier->name }}</div>
                                <div class="small text-muted">{{ $supplier->contact_person ?: '—' }}</div>
                            </td>
                            <td class="small">
                                {{ $supplier->email }}
                                <div class="text-muted">{{ $supplier->phone ?: '—' }}</div>
                            </td>
                            <td>{{ $supplier->payment_terms }}</td>
                            <td class="text-end">{{ $supplier->lead_time_days }} days</td>
                            <td class="text-end">${{ number_format($supplier->minimum_order_amount, 2) }}</td>
                            <td class="text-end">{{ $supplier->products_count }}</td>
                            <td>
                                @if ($supplier->trashed())
                                    <span class="ft-badge badge-missed text-nowrap">Deleted</span>
                                @elseif ($supplier->is_active)
                                    <span class="ft-badge badge-completed">Active</span>
                                @else
                                    <span class="ft-badge badge-scheduled">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if ($supplier->trashed())
                                    @can('admin')
                                        <form action="{{ route('suppliers.restore', $supplier->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-arrow-counterclockwise"></i> Restore
                                            </button>
                                        </form>
                                    @endcan
                                @else
                                    <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                    @can('admin')
                                        <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete {{ $supplier->name }}? It can be restored later.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No suppliers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $suppliers->links() }}</div>
    </div>
@endsection