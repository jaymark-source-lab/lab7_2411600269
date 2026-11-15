<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseOrderPdf
{
    // supplier copy of a purchase order
    public static function make(PurchaseOrder $order)
    {
        $order->loadMissing(['supplier', 'items.product', 'createdBy', 'approvedBy']);

        return Pdf::loadView('exports.purchase-order', ['order' => $order])->setPaper('a4');
    }
}
