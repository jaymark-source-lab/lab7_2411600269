<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'payment_terms',
        'lead_time_days',
        'minimum_order_amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'            => 'boolean',
            'minimum_order_amount' => 'decimal:2',
        ];
    }

    // products this supplier sells (pivot holds the agreed price)
    public function products()
    {
        return $this->belongsToMany(Product::class)
            ->withPivot(['unit_cost', 'minimum_order_quantity', 'is_preferred'])
            ->withTimestamps();
    }

    // purchase orders sent to this supplier (Part 2)
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    // Supplier::active()
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Supplier::withLeadTime(7) -> delivers within 7 days
    public function scopeWithLeadTime($query, int $days)
    {
        return $query->where('lead_time_days', '<=', $days);
    }

    // "Maria Santos · orders@x.test · +63 917 ..."
    public function getFullContactAttribute(): string
    {
        return implode(' · ', array_filter([$this->contact_person, $this->email, $this->phone]));
    }

    // % of received orders that arrived on or before the expected date (null = no data yet)
    public function getPerformanceRatingAttribute(): ?float
    {
        $received = $this->purchaseOrders()
            ->where('status', 'received')
            ->whereNotNull('received_at')
            ->get(['expected_delivery_date', 'received_at']);

        if ($received->isEmpty()) {
            return null;
        }

        $onTime = $received->filter(
            fn ($order) => $order->received_at->toDateString() <= $order->expected_delivery_date->toDateString()
        )->count();

        return round($onTime / $received->count() * 100, 1);
    }
}