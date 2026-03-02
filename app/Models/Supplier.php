<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
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