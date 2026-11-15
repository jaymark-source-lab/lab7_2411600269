{{-- purchase order fields shared by create and edit --}}
@php
    $items = old('items', $oldItems);
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Please fix the following:</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label for="supplier_id" class="form-label">Supplier</label>
        <select name="supplier_id" id="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
            <option value="">Select a supplier...</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" data-lead="{{ $supplier->lead_time_days }}"
                        @selected(old('supplier_id', $order->supplier_id) == $supplier->id)>
                    {{ $supplier->name }} ({{ $supplier->lead_time_days }}-day lead time)
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label for="order_date" class="form-label">Order Date</label>
        <input type="date" name="order_date" id="order_date" class="form-control"
               value="{{ old('order_date', $order->order_date?->toDateString()) }}" required>
    </div>
    <div class="col-md-3">
        <label for="expected_delivery_date" class="form-label">Expected Delivery</label>
        <input type="date" name="expected_delivery_date" id="expected_delivery_date" class="form-control"
               value="{{ old('expected_delivery_date', $order->expected_delivery_date?->toDateString()) }}">
        <div class="form-text">Blank = order date + lead time</div>
    </div>
</div>

{{-- line items --}}
<div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="fw-bold mb-0">Line Items</h6>
    <button type="button" id="add-item" class="btn btn-sm btn-outline-secondary"><i class="bi bi-plus-lg"></i> Add Item</button>
</div>

<div class="table-responsive">
    <table class="table ft-table align-middle">
        <thead>
            <tr>
                <th style="min-width: 260px;">Product</th>
                <th style="width: 120px;">Quantity</th>
                <th style="width: 160px;">Unit Price</th>
                <th class="text-end" style="width: 130px;">Line Total</th>
                <th style="width: 50px;"></th>
            </tr>
        </thead>
        <tbody id="items-body"></tbody>
        <tfoot>
            <tr><td colspan="3" class="text-end text-muted">Subtotal</td><td class="text-end" id="subtotal">$0.00</td><td></td></tr>
            <tr><td colspan="3" class="text-end text-muted">VAT (12%)</td><td class="text-end" id="tax">$0.00</td><td></td></tr>
            <tr><td colspan="3" class="text-end fw-bold">Total</td><td class="text-end fw-bold" id="total">$0.00</td><td></td></tr>
        </tfoot>
    </table>
</div>

{{-- one row, cloned by JavaScript --}}
<template id="item-row-template">
    <tr>
        <td>
            <select name="items[__INDEX__][product_id]" class="form-select item-product" required>
                <option value="">Select product...</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" name="items[__INDEX__][quantity]" class="form-control item-qty" min="1" value="1" required></td>
        <td>
            <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" name="items[__INDEX__][unit_price]" class="form-control item-price" min="0" step="0.01" required>
            </div>
        </td>
        <td class="text-end item-total">$0.00</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="bi bi-x-lg"></i></button></td>
    </tr>
</template>

<div class="row g-3 mt-2">
    <div class="col-md-6">
        <label for="notes" class="form-label">Notes</label>
        <textarea name="notes" id="notes" rows="3" class="form-control">{{ old('notes', $order->notes) }}</textarea>
    </div>
    <div class="col-md-6">
        <label for="terms" class="form-label">Terms</label>
        <textarea name="terms" id="terms" rows="3" class="form-control" placeholder="Blank = supplier's payment terms">{{ old('terms', $order->terms) }}</textarea>
    </div>
</div>

@push('scripts')
<script>
    // agreed prices: supplier id -> { product id: cost }
    const supplierPrices = @json($prices);
    const existingItems = @json(array_values($items));

    const body = document.getElementById('items-body');
    const template = document.getElementById('item-row-template').innerHTML;
    const supplierSelect = document.getElementById('supplier_id');
    let nextIndex = 0;

    const money = n => '$' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // fill the price from the supplier's agreed cost (unless typed by hand)
    function prefillPrice(row) {
        const price = row.querySelector('.item-price');
        if (price.dataset.manual === '1') return;

        const cost = supplierPrices[supplierSelect.value]?.[row.querySelector('.item-product').value];
        price.value = cost ?? '';
    }

    // recompute line totals and order totals
    function recalc() {
        let subtotal = 0;

        body.querySelectorAll('tr').forEach(row => {
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const line = qty * price;
            row.querySelector('.item-total').textContent = money(line);
            subtotal += line;
        });

        const tax = Math.round(subtotal * 0.12 * 100) / 100;
        document.getElementById('subtotal').textContent = money(subtotal);
        document.getElementById('tax').textContent = money(tax);
        document.getElementById('total').textContent = money(subtotal + tax);
    }

    // add a row, optionally with saved values
    function addRow(item = {}) {
        body.insertAdjacentHTML('beforeend', template.replaceAll('__INDEX__', nextIndex++));
        const row = body.lastElementChild;

        if (item.product_id) row.querySelector('.item-product').value = item.product_id;
        if (item.quantity) row.querySelector('.item-qty').value = item.quantity;
        if (item.unit_price !== undefined && item.unit_price !== null && item.unit_price !== '') {
            row.querySelector('.item-price').value = item.unit_price;
            row.querySelector('.item-price').dataset.manual = '1';
        }

        recalc();
    }

    body.addEventListener('change', e => {
        if (e.target.classList.contains('item-product')) prefillPrice(e.target.closest('tr'));
        recalc();
    });

    body.addEventListener('input', e => {
        if (e.target.classList.contains('item-price')) e.target.dataset.manual = '1';
        recalc();
    });

    body.addEventListener('click', e => {
        const button = e.target.closest('.remove-item');
        if (!button) return;
        if (body.children.length > 1) button.closest('tr').remove();
        recalc();
    });

    supplierSelect.addEventListener('change', () => {
        body.querySelectorAll('tr').forEach(prefillPrice);
        recalc();
    });

    document.getElementById('add-item').addEventListener('click', () => addRow());

    // start with saved rows, or one empty row
    existingItems.length ? existingItems.forEach(addRow) : addRow();
</script>
@endpush