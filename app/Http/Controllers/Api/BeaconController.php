<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PresenceLog;
use Illuminate\Http\Request;

class BeaconController extends Controller
{
    public function receive(Request $request)
    {
        $data = $request->all();

        // Find employee by MAC address
        $employee = Employee::where('mac_address', strtoupper($data['mac_address']))->first();

        if (!$employee) {
            return response()->json(['message' => 'Unknown beacon'], 404);
        }

        // Log the presence
        PresenceLog::create([
            'employee_id' => $employee->id,
            'location_id' => $data['location_id'],
            'rssi'        => $data['rssi'],
            'detected_at' => now(),
        ]);

        return response()->json(['message' => 'Presence logged successfully']);
    }
}