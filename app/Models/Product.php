<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'category',
        'description',
        'quantity',
        'reorder_level',
        'unit_price',
        'supplier',
    ];

    // all stock movements for this product
    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    // the most recent stock movement
    public function latestTransaction()


    
    {
        return $this->hasOne(InventoryTransaction::class)->latestOfMany();
    }

    // at or below reorder level
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->reorder_level;
    }
    // suppliers that sell this product (pivot holds the agreed price)
   
    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class)
            ->withPivot(['unit_cost', 'minimum_order_quantity', 'is_preferred'])
            ->withTimestamps();
    }

    // the supplier used for automatic reorders
    public function preferredSupplier(): ?Supplier
    {
        return $this->suppliers()->wherePivot('is_preferred', true)->first();
    }
    // nothing left
    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
    }

    // readable status: $product->stock_status
    public function getStockStatusAttribute(): string
    {
        if ($this->isOutOfStock()) {
            return 'Out of Stock';
        }

        return $this->isLowStock() ? 'Low Stock' : 'In Stock';
    }

    // bootstrap color: $product->stock_status_color
    public function getStockStatusColorAttribute(): string
    {
        if ($this->isOutOfStock()) {
            return 'danger';
        }

        return $this->isLowStock() ? 'warning' : 'success';
    }

    // Product::lowStock()
    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'reorder_level');
    }

    // Product::outOfStock()
    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', '<=', 0);
    }

    // Product::inCategory('Accessories')
    public function scopeInCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Product::search('whey') - name, SKU, category or supplier name
    public function scopeSearch($query, string $term)
    {
        $like = '%' . $term . '%';

        return $query->where(function ($q) use ($like) {
            $q->where('name', 'like', $like)
              ->orWhere('sku', 'like', $like)
              ->orWhere('category', 'like', $like)
              ->orWhereHas('suppliers', fn ($s) => $s->where('name', 'like', $like));
        });
    }

    // Product::fromSupplier(3) - products sold by a supplier
    public function scopeFromSupplier($query, $supplierId)
    {
        return $query->whereHas('suppliers', fn ($s) => $s->where('suppliers.id', $supplierId));
    }

    // Product::stockStatus('low') - in, low or out of stock
    public function scopeStockStatus($query, string $status)
    {
        return match ($status) {
            'out'   => $query->where('quantity', '<=', 0),
            'low'   => $query->where('quantity', '>', 0)->whereColumn('quantity', '<=', 'reorder_level'),
            'in'    => $query->whereColumn('quantity', '>', 'reorder_level'),
            default => $query,
        };
    }
}
