<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecordItem extends Model
{
    protected $fillable = [
        'record_id',
        'item_id',
        'quantity'
    ];

    public function record()
    {
        return $this->belongsTo(Record::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}