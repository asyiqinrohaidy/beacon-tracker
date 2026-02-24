<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fingerprint extends Model
{
    protected $fillable = [
        'spot_name',
        'location_name',
        'gateway_1_rssi',
        'gateway_2_rssi',
    ];
}