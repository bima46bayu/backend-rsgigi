<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecordTreatment extends Model
{
    protected $fillable = [
        'record_id',
        'treatment_id'
    ];

    public function record()
    {
        return $this->belongsTo(Record::class);
    }

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }

    public function items()
    {
        return $this->hasMany(RecordItem::class, 'record_treatment_id', 'id');
    }
}
