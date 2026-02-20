<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresenceLog extends Model
{
    protected $fillable = ['employee_id', 'location_id', 'rssi', 'detected_at'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}