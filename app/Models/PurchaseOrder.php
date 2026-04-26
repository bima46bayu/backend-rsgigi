<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Location;
use App\Models\User;
use App\Models\GoodsReceipt;

use Illuminate\Database\Eloquent\BroadcastsEvents;

class PurchaseOrder extends Model
{
    use BroadcastsEvents;

    public function broadcastOn($event)
    {
        return ['purchases'];
    }
    protected $fillable = [
        'po_number',
        'location_id',
        'supplier_id',
        'status',
        'ordered_at',
        'approved_at',
        'created_by',
        'approved_by',
        'total_amount',
        'notes'
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function goodsReceipts()
    {
        return $this->hasMany(GoodsReceipt::class);
    }
}