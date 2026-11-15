<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    // payment terms offered in the form
    public const PAYMENT_TERMS = ['COD', 'Net 15', 'Net 30', 'Net 45', 'Net 60'];

    // sort options (key => [column, direction])
    private const SORTS = [
        'name'      => ['name', 'asc'],
        'name_desc' => ['name', 'desc'],
        'lead_time' => ['lead_time_days', 'asc'],
        'newest'    => ['created_at', 'desc'],
    ];

    // list suppliers with search, status filter and sort
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');
        $query = $status === 'trashed' ? Supplier::onlyTrashed() : Supplier::query();

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('contact_person', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }

        [$column, $direction] = self::SORTS[$request->input('sort', 'name')] ?? self::SORTS['name'];

        $suppliers = $query->withCount('products')
            ->orderBy($column, $direction)
            ->paginate(10)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers', 'status'));
    }

    // show the add form
    public function create()
    {
        return view('suppliers.create', ['terms' => self::PAYMENT_TERMS]);
    }

    // save a new supplier
    public function store(Request $request)
    {
        Supplier::create($this->validated($request));

        return redirect()->route('suppliers.index')->with('success', 'Supplier added successfully.');
    }

    // supplier details, products, recent orders and performance
    public function show(Supplier $supplier)
    {
        $supplier->load(['products' => fn ($q) => $q->orderBy('name')]);

        $recentOrders = $supplier->purchaseOrders()
            ->latest('order_date')
            ->latest('id')
            ->take(5)
            ->get();

        $stats = [
            'total_orders' => $supplier->purchaseOrders()->count(),
            'open_orders'  => $supplier->purchaseOrders()->active()->count(),
            'received'     => $supplier->purchaseOrders()->byStatus('received')->count(),
            'total_value'  => (float) $supplier->purchaseOrders()->byStatus('received')->sum('total'),
            'on_time'      => $supplier->performance_rating,
        ];

        return view('suppliers.show', compact('supplier', 'recentOrders', 'stats'));
    }

    // show the edit form
    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', ['supplier' => $supplier, 'terms' => self::PAYMENT_TERMS]);
    }

    // save changes
    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validated($request, $supplier));

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier updated successfully.');
    }

    // soft delete (admin only, blocked while orders are open)
    public function destroy(Supplier $supplier)
    {
        Gate::authorize('admin');

        if ($supplier->purchaseOrders()->active()->exists()) {
            return back()->with('error', "{$supplier->name} still has open purchase orders. Receive or cancel them first.");
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('success', "{$supplier->name} was moved to deleted suppliers. It can be restored.");
    }

    // bring back a soft-deleted supplier (admin only)
    public function restore(int $id)
    {
        Gate::authorize('admin');

        $supplier = Supplier::onlyTrashed()->findOrFail($id);
        $supplier->restore();

        return redirect()->route('suppliers.show', $supplier)->with('success', "{$supplier->name} was restored.");
    }

    // shared validation for store and update
    private function validated(Request $request, ?Supplier $supplier = null): array
    {
        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'contact_person'       => ['nullable', 'string', 'max:255'],
            'email'                => ['required', 'email', 'max:255', Rule::unique('suppliers', 'email')->ignore($supplier?->id)],
            'phone'                => ['nullable', 'regex:/^\+?[0-9\s\-()]{7,20}$/'],
            'address'              => ['nullable', 'string', 'max:255'],
            'payment_terms'        => ['required', Rule::in(self::PAYMENT_TERMS)],
            'lead_time_days'       => ['required', 'integer', 'min:1', 'max:365'],
            'minimum_order_amount' => ['required', 'numeric', 'min:0'],
        ], [
            'phone.regex' => 'Enter a valid phone number, e.g. +63 917 555 0101.',
        ]);

        // unchecked checkbox sends nothing, so read it as a boolean
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}