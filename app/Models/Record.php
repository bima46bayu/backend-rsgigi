<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    protected $fillable = [
        'location_id',
        'treatment_id',
        'patient_name',
        'status',
        'performed_at'
    ];

    protected $casts = [
        'performed_at' => 'datetime'
    ];

    public function items()
    {
        return $this->hasMany(RecordItem::class);
    }

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }
}
