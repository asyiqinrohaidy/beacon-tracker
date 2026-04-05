<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PresenceLog;

class PresenceController extends Controller
{
    // Get current location of all employees
    public function current()
    {
        $employees = Employee::with(['latestPresence.location'])->get();

        $data = $employees->map(function ($employee) {
            return [
                'employee'    => $employee->name,
                'department'  => $employee->department,
                'location'    => $employee->latestPresence?->location?->name ?? 'Unknown',
                'spot_name'   => $employee->latestPresence?->spot_name ?? null,
                'detected_at' => $employee->latestPresence?->detected_at,
            ];
        });

        return response()->json($data);
    }

    // Get full presence history
    public function logs()
    {
        $logs = PresenceLog::with(['employee', 'location'])
            ->orderBy('detected_at', 'desc')
            ->take(100)
            ->get()
            ->map(function ($log) {
                return [
                    'employee'    => $log->employee?->name,
                    'department'  => $log->employee?->department,
                    'location'    => $log->location?->name ?? 'Unknown',
                    'spot_name'   => $log->spot_name ?? $log->location?->name ?? 'Unknown',
                    'rssi'        => $log->rssi,
                    'detected_at' => $log->detected_at,
                ];
            });

        return response()->json($logs);
    }
}