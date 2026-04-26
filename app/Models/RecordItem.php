<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Record;
use App\Models\Item;

class RecordItem extends Model
{
    protected $fillable = [
        'record_id',
        'record_treatment_id',
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

    public function recordTreatment()
    {
        return $this->belongsTo(RecordTreatment::class);
    }
}