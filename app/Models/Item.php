<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ItemStock;
use App\Models\ItemTransaction;
use App\Models\Treatment;
use App\Models\Location;
use App\Models\Category;

use Illuminate\Database\Eloquent\BroadcastsEvents;

class Item extends Model
{
    use BroadcastsEvents;

    public function broadcastOn($event)
    {
        return ['inventory'];
    }
    protected $fillable = [
        'location_id',
        'category_id',
        'name',
        'type',
        'min_stock',
        'alert_status',
        'last_alert_sent_at',
        'version'
    ];

    protected $casts = [
        'last_alert_sent_at' => 'datetime'
    ];

    public function stocks()
    {
        return $this->hasMany(ItemStock::class);
    }

    public function transactions()
    {
        return $this->hasMany(ItemTransaction::class);
    }

    public function getTotalStockAttribute()
    {
        return $this->stocks()->sum('quantity');
    }

    public function treatments()
    {
        return $this->belongsToMany(Treatment::class, 'treatment_items')
                    ->withPivot('quantity');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}