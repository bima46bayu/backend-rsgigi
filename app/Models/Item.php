<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
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
}