<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ['name', 'description'];

    public function presenceLogs()
    {
        return $this->hasMany(PresenceLog::class);
    }
}