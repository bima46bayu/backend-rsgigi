<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Location;

use Illuminate\Database\Eloquent\BroadcastsEvents;

class Supplier extends Model
{
    use BroadcastsEvents;

    public function broadcastOn($event)
    {
        return ['master'];
    }
    protected $fillable = [
        'location_id',
        'name',
        'phone',
        'email',
        'address',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}