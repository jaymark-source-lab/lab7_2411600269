<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Observers\InventoryTransactionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(InventoryTransactionObserver::class)]
class InventoryTransaction extends Model
{
    // allowed transaction types (value => label)
    public const TYPES = [
        'stock_in'   => 'Stock In',
        'stock_out'  => 'Stock Out',
        'adjustment' => 'Adjustment',
    ];

    protected $fillable = [
        'product_id',
        'user_id',
        'type',
        'quantity',
        'reference_number',
        'notes',
    ];

    // transaction belongs to a product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // transaction belongs to the user who made it
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // only stock-in rows
    public function scopeStockIn($query)
    {
        return $query->where('type', 'stock_in');
    }

    // only stock-out rows
    public function scopeStockOut($query)
    {
        return $query->where('type', 'stock_out');
    }

    // newest first
    public function scopeRecent($query)
    {
        return $query->latest();
    }

    // InventoryTransaction::search('whey') - product name, SKU or reference number
    public function scopeSearch($query, string $term)
    {
        $like = '%' . $term . '%';

        return $query->where(function ($q) use ($like) {
            $q->where('reference_number', 'like', $like)
              ->orWhereHas('product', fn ($p) => $p->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('sku', 'like', $like)));
        });
    }

    // InventoryTransaction::ofType('stock_in')
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // InventoryTransaction::forProduct(5)
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    // InventoryTransaction::recordedBy(2)
    public function scopeRecordedBy($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // InventoryTransaction::recordedBetween('2026-11-01', '2026-11-30') - either date can be empty
    public function scopeRecordedBetween($query, ?string $from, ?string $to)
    {
        return $query
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));
    }
}
