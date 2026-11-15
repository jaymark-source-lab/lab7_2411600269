{{-- supplier fields shared by create and edit --}}
<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Company Name</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $supplier->name ?? '') }}" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="contact_person" class="form-label">Contact Person</label>
        <input type="text" name="contact_person" id="contact_person" class="form-control @error('contact_person') is-invalid @enderror"
               value="{{ old('contact_person', $supplier->contact_person ?? '') }}">
        @error('contact_person') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="email" class="form-label">Email</label>
        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $supplier->email ?? '') }}" required>
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="phone" class="form-label">Phone</label>
        <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror"
               value="{{ old('phone', $supplier->phone ?? '') }}" placeholder="+63 917 555 0101">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label for="address" class="form-label">Address</label>
        <input type="text" name="address" id="address" class="form-control @error('address') is-invalid @enderror"
               value="{{ old('address', $supplier->address ?? '') }}">
        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label for="payment_terms" class="form-label">Payment Terms</label>
        <select name="payment_terms" id="payment_terms" class="form-select @error('payment_terms') is-invalid @enderror">
            @foreach ($terms as $term)
                <option value="{{ $term }}" @selected(old('payment_terms', $supplier->payment_terms ?? 'Net 30') === $term)>{{ $term }}</option>
            @endforeach
        </select>
        @error('payment_terms') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label for="lead_time_days" class="form-label">Lead Time (days)</label>
        <input type="number" name="lead_time_days" id="lead_time_days" min="1" max="365"
               class="form-control @error('lead_time_days') is-invalid @enderror"
               value="{{ old('lead_time_days', $supplier->lead_time_days ?? 7) }}" required>
        @error('lead_time_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label for="minimum_order_amount" class="form-label">Minimum Order</label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="minimum_order_amount" id="minimum_order_amount" min="0" step="0.01"
                   class="form-control @error('minimum_order_amount') is-invalid @enderror"
                   value="{{ old('minimum_order_amount', $supplier->minimum_order_amount ?? 0) }}" required>
            @error('minimum_order_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="col-12">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                   @checked(old('is_active', $supplier->is_active ?? true))>
            <label class="form-check-label" for="is_active">Active supplier (can receive purchase orders)</label>
        </div>
    </div>
</div>