<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    // status => label
    public const STATUSES = [
        'draft'     => 'Draft',
        'pending'   => 'Pending Approval',
        'approved'  => 'Approved',
        'rejected'  => 'Rejected',
        'sent'      => 'Sent',
        'received'  => 'Received',
        'cancelled' => 'Cancelled',
    ];

    // Philippine VAT
    public const TAX_RATE = 0.12;

    protected $fillable = [
        'reference_number',
        'supplier_id',
        'status',
        'order_date',
        'expected_delivery_date',
        'subtotal',
        'tax',
        'total',
        'notes',
        'terms',
        'is_auto_generated',
        'created_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'sent_at',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'order_date'             => 'date',
            'expected_delivery_date' => 'date',
            'approved_at'            => 'datetime',
            'sent_at'                => 'datetime',
            'received_at'            => 'datetime',
            'is_auto_generated'      => 'boolean',
            'subtotal'               => 'decimal:2',
            'tax'                    => 'decimal:2',
            'total'                  => 'decimal:2',
        ];
    }

    // supplier, even if it was soft-deleted later
    public function supplier()
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    // line items
    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    // who created the order
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // who approved or rejected it
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // approval history (polymorphic)
    public function activities()
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest('id');
    }

    // orders waiting for approval
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // orders still in progress
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['received', 'cancelled', 'rejected']);
    }

    // PurchaseOrder::byStatus('approved')
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // recompute subtotal, tax and total from the items
    public function calculateTotal(): float
    {
        $subtotal = (float) $this->items()->sum(DB::raw('quantity * unit_price'));
        $tax = round($subtotal * self::TAX_RATE, 2);

        $this->update([
            'subtotal' => $subtotal,
            'tax'      => $tax,
            'total'    => $subtotal + $tax,
        ]);

        return $subtotal + $tax;
    }

    // only drafts can be edited
    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    // only pending orders can be approved or rejected
    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    // approved or sent orders can receive deliveries
    public function canBeReceived(): bool
    {
        return in_array($this->status, ['approved', 'sent'], true);
    }

    // next number for today: PO-20261115-001
    public static function generateReferenceNumber(): string
    {
        $prefix = 'PO-' . now()->format('Ymd') . '-';

        $last = static::withTrashed()
            ->where('reference_number', 'like', $prefix . '%')
            ->max('reference_number');

        $next = $last ? (int) substr($last, -3) + 1 : 1;

        return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    // add an entry to the approval history
    public function log(string $action, ?string $description = null): ActivityLog
    {
        return $this->activities()->create([
            'user_id'     => auth()->id(),
            'action'      => $action,
            'description' => $description,
        ]);
    }

    // readable status
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    // badge class for the status
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'approved', 'received' => 'badge-completed',
            'pending', 'sent'      => 'badge-scheduled',
            'rejected', 'cancelled' => 'badge-missed',
            default                => 'bg-light text-secondary',
        };
    }

    // PurchaseOrder::search('PO-2026') - reference number or supplier name
    public function scopeSearch($query, string $term)
    {
        $like = '%' . $term . '%';

        return $query->where(function ($q) use ($like) {
            $q->where('reference_number', 'like', $like)
              ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', $like));
        });
    }

    // PurchaseOrder::forSupplier(3)
    public function scopeForSupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    // PurchaseOrder::orderedBetween('2026-11-01', '2026-11-30') - either date can be empty
    public function scopeOrderedBetween($query, ?string $from, ?string $to)
    {
        return $query
            ->when($from, fn ($q) => $q->whereDate('order_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('order_date', '<=', $to));
    }
}
