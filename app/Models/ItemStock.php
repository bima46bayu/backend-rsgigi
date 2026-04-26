<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Item;
use App\Models\ItemTransaction;

class ItemStock extends Model
{
    protected $fillable = [
        'item_id',
        'location_id',
        'batch_number',
        'quantity',
        'expiry_date'
    ];

    protected $casts = [
        'expiry_date' => 'date'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function transactions()
    {
        return $this->hasMany(ItemTransaction::class);
    }
}