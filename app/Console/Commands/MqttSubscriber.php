<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;
use App\Models\Employee;
use App\Models\PresenceLog;

class MqttSubscriber extends Command
{
    protected $signature = 'mqtt:subscribe';
    protected $description = 'Subscribe to MQTT broker and listen for beacon data';

    protected $gatewayLocationMap = [
        '40915187ded4' => 1, // Workshop First Floor
        '409151b99f40' => 2, // Meeting Room Second Floor
    ];

    // Store latest RSSI from each gateway
    protected $latestRssi = [
        '40915187ded4' => null,
        '409151b99f40' => null,
    ];

    public function handle()
    {
        $this->info('Connecting to MQTT broker...');

        MQTT::connection()->subscribe('/MK107/40915187ded4/send', function (string $topic, string $message) {
            $this->processMessage('40915187ded4', $message);
        }, 0);

        MQTT::connection()->subscribe('/MK107/409151b99f40/send', function (string $topic, string $message) {
            $this->processMessage('409151b99f40', $message);
        }, 0);

        MQTT::connection()->loop(true);
    }

    protected function processMessage(string $gatewayMac, string $message)
    {
        $data = json_decode($message, true);

        if (!isset($data['data']) || !is_array($data['data'])) {
            return;
        }

        $locationId = $this->gatewayLocationMap[$gatewayMac] ?? null;
        if (!$locationId) return;

        foreach ($data['data'] as $beacon) {
            $mac = strtoupper(str_replace(':', '', $beacon['mac'] ?? ''));
            $rssi = $beacon['rssi'] ?? 0;

            $employee = Employee::whereRaw('UPPER(REPLACE(mac_address, ":", "")) = ?', [$mac])->first();
            if (!$employee) continue;

            // Store latest RSSI for this gateway
            $this->latestRssi[$gatewayMac] = $rssi;

            // Log presence
            PresenceLog::create([
                'employee_id' => $employee->id,
                'location_id' => $locationId,
                'rssi'        => $rssi,
                'detected_at' => now(),
            ]);

            // Show RSSI from both gateways simultaneously
            $gw1 = $this->latestRssi['40915187ded4'] ?? 'N/A';
            $gw2 = $this->latestRssi['409151b99f40'] ?? 'N/A';

            $this->info("--------------------------------------------------");
            $this->info("Employee : {$employee->name}");
            $this->info("GW1 (Workshop First Floor)       : {$gw1} dBm");
            $this->info("GW2 (Meeting Room Second Floor)  : {$gw2} dBm");
            $this->info("--------------------------------------------------");
        }
    }
}