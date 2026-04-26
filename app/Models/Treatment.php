<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Item;
use App\Models\Location;

class Treatment extends Model
{
    protected $fillable = [
        'location_id',
        'code',
        'name',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function items()
    {
        return $this->belongsToMany(Item::class, 'treatment_items')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }
}