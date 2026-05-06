<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\RecordItem;
use App\Models\Treatment;
use App\Models\Location;

class Record extends Model
{

    protected $fillable = [
        'code',
        'location_id',
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

    public function treatments()
    {
        return $this->hasMany(RecordTreatment::class);
    }
}
