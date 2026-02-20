<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = ['name', 'employee_id', 'mac_address', 'department'];

    public function presenceLogs()
    {
        return $this->hasMany(PresenceLog::class);
    }

    public function latestPresence()
    {
        return $this->hasOne(PresenceLog::class)->latestOfMany();
    }
}