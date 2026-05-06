<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Location;

class Category extends Model
{

    protected $fillable = [
        'location_id',
        'name',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}