<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\RecordItem;
use App\Models\Treatment;
use App\Models\Location;

use Illuminate\Database\Eloquent\BroadcastsEvents;

class Record extends Model
{
    use BroadcastsEvents;

    public function broadcastOn($event)
    {
        return ['records'];
    }
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
