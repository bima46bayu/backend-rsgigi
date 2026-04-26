<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Location;

use Illuminate\Database\Eloquent\BroadcastsEvents;

class Category extends Model
{
    use BroadcastsEvents;

    public function broadcastOn($event)
    {
        return ['master'];
    }
    protected $fillable = [
        'location_id',
        'name',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}