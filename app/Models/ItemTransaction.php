<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Item;
use App\Models\ItemStock;

class ItemTransaction extends Model
{
    protected $fillable = [
        'item_id',
        'item_stock_id',
        'type',
        'quantity',
        'reference_type',
        'reference_id'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function stock()
    {
        return $this->belongsTo(ItemStock::class, 'item_stock_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isIn()
    {
        return $this->type === 'in';
    }

    public function isOut()
    {
        return $this->type === 'out';
    }
}