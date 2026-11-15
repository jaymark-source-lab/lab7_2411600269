<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action', 'description'];

    // the record this log belongs to (PurchaseOrder, Supplier, ...)
    public function subject()
    {
        return $this->morphTo();
    }

    // who did it
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}