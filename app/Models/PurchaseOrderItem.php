<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity',
        'unit_price',
        'received_quantity',
    ];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2'];
    }

    // the order this line belongs to
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    // the product being ordered
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // quantity x unit price
    public function getLineTotalAttribute(): float
    {
        return round($this->quantity * (float) $this->unit_price, 2);
    }

    // ordered - received
    public function getOutstandingQuantityAttribute(): int
    {
        return max($this->quantity - $this->received_quantity, 0);
    }
}