<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\Location;
use App\Models\User;

class GoodsReceipt extends Model
{
    protected $fillable = [
        'gr_number',
        'purchase_order_id',
        'location_id',
        'status',
        'received_at',
        'completed_at',
        'created_by'
    ];

    public function items()
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}