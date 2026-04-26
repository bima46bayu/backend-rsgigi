<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\Location;
use App\Models\User;
use App\Models\Item;

class GoodsReceiptItem extends Model
{
    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_item_id',
        'item_id',
        'qty_received',
        'qty_rejected',
        'reject_reason',
        'unit_cost',
        'subtotal',
        'expiry_date'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
