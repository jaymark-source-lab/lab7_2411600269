<?php

namespace App\Http\Controllers;

use App\Mail\PurchaseOrderApproved;
use App\Mail\PurchaseOrderCreated;
use App\Mail\PurchaseOrderSent;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    // list purchase orders using the search service
    public function index(\Illuminate\Http\Request $request, \App\Services\SearchService $search)
    {
        $orders = $search->purchaseOrders($request->only(['search', 'status', 'supplier_id', 'source', 'from', 'to']))
            ->paginate(10)
            ->withQueryString();

        return view('purchase-orders.index', ['orders' => $orders] + $search->purchaseOrderOptions());
    }

    // new order form
    public function create(Request $request)
    {
        $this->authorize('create', PurchaseOrder::class);

        return view('purchase-orders.create', $this->formData() + [
            'order'    => new PurchaseOrder(['order_date' => now(), 'supplier_id' => $request->query('supplier_id')]),
            'oldItems' => [],
        ]);
    }

    // save a new order as draft or submit it
    public function store(Request $request)
    {
        $this->authorize('create', PurchaseOrder::class);

        $data = $this->validateOrder($request);

        $order = DB::transaction(function () use ($data, $request) {
            $order = PurchaseOrder::create($this->headerFields($data) + [
                'reference_number' => PurchaseOrder::generateReferenceNumber(),
                'status'           => 'draft',
                'created_by'       => $request->user()->id,
            ]);

            $order->items()->createMany($data['items']);
            $order->calculateTotal();
            $order->log('created', 'Order created with ' . count($data['items']) . ' item(s).');

            if ($data['action'] === 'submit') {
                $this->markSubmitted($order);
            }

            return $order;
        });

        return redirect()->route('purchase-orders.show', $order)
            ->with('success', "Purchase order {$order->reference_number} " . ($order->status === 'pending' ? 'submitted for approval.' : 'saved as draft.'));
    }

    // order details, items and history
    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load(['supplier', 'items.product', 'createdBy', 'approvedBy', 'activities.user']);

        return view('purchase-orders.show', ['order' => $purchaseOrder]);
    }

    // edit form (drafts only)
    public function edit(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        $purchaseOrder->load('items');

        return view('purchase-orders.edit', $this->formData() + [
            'order'    => $purchaseOrder,
            'oldItems' => $purchaseOrder->items->map->only(['product_id', 'quantity', 'unit_price'])->all(),
        ]);
    }

    // save changes to a draft
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        $data = $this->validateOrder($request);

        DB::transaction(function () use ($data, $purchaseOrder) {
            $purchaseOrder->update($this->headerFields($data));

            // replace all lines with the submitted ones
            $purchaseOrder->items()->delete();
            $purchaseOrder->items()->createMany($data['items']);
            $purchaseOrder->calculateTotal();
            $purchaseOrder->log('updated', 'Draft updated.');

            if ($data['action'] === 'submit') {
                $this->markSubmitted($purchaseOrder);
            }
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('success', 'Purchase order updated.');
    }

    // soft delete a draft (admin)
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('delete', $purchaseOrder);

        $purchaseOrder->log('deleted', 'Draft deleted.');
        $purchaseOrder->delete();

        return redirect()->route('purchase-orders.index')->with('success', "{$purchaseOrder->reference_number} deleted.");
    }

    // draft -> pending
    public function submit(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('submit', $purchaseOrder);

        if ($purchaseOrder->items()->doesntExist()) {
            return back()->withErrors(['order' => 'Add at least one item before submitting.']);
        }

        $this->markSubmitted($purchaseOrder);

        return back()->with('success', 'Submitted for approval. Approvers were notified by email.');
    }

    // pending -> approved, then email the creator
    public function approve(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('approve', $purchaseOrder);

        $purchaseOrder->update([
            'status'      => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);
        $purchaseOrder->log('approved', 'Approved by ' . $request->user()->name . '.');

        Mail::to($purchaseOrder->createdBy)->send(new PurchaseOrderApproved($purchaseOrder));

        return back()->with('success', "{$purchaseOrder->reference_number} approved. {$purchaseOrder->createdBy->name} was notified by email.");
    }

    // pending -> rejected (reason required)
    public function reject(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('reject', $purchaseOrder);

        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:500']]);

        $purchaseOrder->update([
            'status'           => 'rejected',
            'approved_by'      => $request->user()->id,
            'approved_at'      => now(),
            'rejection_reason' => $data['rejection_reason'],
        ]);
        $purchaseOrder->log('rejected', $data['rejection_reason']);

        return back()->with('success', "{$purchaseOrder->reference_number} rejected.");
    }

    // approved -> sent, and email the order to the supplier
    public function send(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('send', $purchaseOrder);

        $purchaseOrder->update(['status' => 'sent', 'sent_at' => now()]);
        $purchaseOrder->log('sent', 'Emailed to ' . $purchaseOrder->supplier->email . '.');

        Mail::to($purchaseOrder->supplier->email)->send(new PurchaseOrderSent($purchaseOrder));

        return back()->with('success', "{$purchaseOrder->reference_number} emailed to {$purchaseOrder->supplier->name}.");
    }

    // record delivered quantities and add them to stock
    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('receive', $purchaseOrder);

        $data = $request->validate([
            'received'   => ['required', 'array'],
            'received.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $purchaseOrder->load('items.product');

        $received = DB::transaction(function () use ($data, $purchaseOrder, $request) {
            $lines = [];

            foreach ($purchaseOrder->items as $item) {
                // never receive more than is still outstanding
                $qty = min((int) ($data['received'][$item->id] ?? 0), $item->outstanding_quantity);

                if ($qty === 0) {
                    continue;
                }

                $item->increment('received_quantity', $qty);

                // the Lab 6 observer adds this to the product's stock
                InventoryTransaction::create([
                    'product_id'       => $item->product_id,
                    'user_id'          => $request->user()->id,
                    'type'             => 'stock_in',
                    'quantity'         => $qty,
                    'reference_number' => $purchaseOrder->reference_number,
                    'notes'            => 'Received from ' . $purchaseOrder->supplier->name,
                ]);

                $lines[] = "{$qty} × {$item->product->name}";
            }

            if (empty($lines)) {
                throw ValidationException::withMessages(['received' => 'Enter at least one received quantity.']);
            }

            // fully received when nothing is outstanding
            $complete = $purchaseOrder->items()->whereColumn('received_quantity', '<', 'quantity')->doesntExist();

            if ($complete) {
                $purchaseOrder->update(['status' => 'received', 'received_at' => now()]);
            }

            $purchaseOrder->log($complete ? 'received' : 'partially received', implode(', ', $lines));

            return $lines;
        });

        return back()->with('success', 'Received ' . implode(', ', $received) . '. Stock updated.');
    }

    // stop an order before it is sent
    public function cancel(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('cancel', $purchaseOrder);

        $purchaseOrder->update(['status' => 'cancelled']);
        $purchaseOrder->log('cancelled', 'Order cancelled.');

        return back()->with('success', "{$purchaseOrder->reference_number} cancelled.");
    }

    // shared data for create/edit forms
    private function formData(): array
    {
        $suppliers = Supplier::active()->orderBy('name')->get(['id', 'name', 'lead_time_days']);

        // supplier id => [product id => agreed cost], used to prefill prices
        $prices = DB::table('product_supplier')->get()
            ->groupBy('supplier_id')
            ->map(fn ($rows) => $rows->pluck('unit_cost', 'product_id'));

        $products = Product::orderBy('name')->get(['id', 'name', 'sku', 'unit_price']);

        return compact('suppliers', 'prices', 'products');
    }

    // shared validation for store/update
    private function validateOrder(Request $request): array
    {
        return $request->validate([
            'supplier_id'            => ['required', Rule::exists('suppliers', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'order_date'             => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'notes'                  => ['nullable', 'string', 'max:1000'],
            'terms'                  => ['nullable', 'string', 'max:1000'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
            'items.*.unit_price'     => ['required', 'numeric', 'min:0'],
            'action'                 => ['required', Rule::in(['draft', 'submit'])],
        ], [
            'supplier_id.exists'          => 'Choose an active supplier.',
            'items.required'              => 'Add at least one line item.',
            'items.*.product_id.distinct' => 'Each product can only appear once.',
        ]);
    }

    // header columns from validated data (expected date defaults to lead time)
    private function headerFields(array $data): array
    {
        $supplier = Supplier::find($data['supplier_id']);

        return [
            'supplier_id'            => $data['supplier_id'],
            'order_date'             => $data['order_date'],
            'expected_delivery_date' => $data['expected_delivery_date']
                ?? Carbon::parse($data['order_date'])->addDays($supplier->lead_time_days)->toDateString(),
            'notes'                  => $data['notes'] ?? null,
            'terms'                  => $data['terms'] ?? $supplier->payment_terms,
        ];
    }

    // draft -> pending + history entry + email approvers
    private function markSubmitted(PurchaseOrder $order): void
    {
        $order->update(['status' => 'pending']);
        $order->log('submitted', 'Submitted for approval.');

        // managers and admins, except the person submitting
        $approvers = User::whereIn('role', ['admin', 'manager'])
            ->where('id', '!=', auth()->id())
            ->get();

        // only email once the database transaction is saved
        if ($approvers->isNotEmpty()) {
            DB::afterCommit(fn () => Mail::to($approvers)->send(new PurchaseOrderCreated($order)));
        }
    }
}