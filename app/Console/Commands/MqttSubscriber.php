<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;
use App\Models\Employee;
use App\Models\Fingerprint;
use App\Models\PresenceLog;
use App\Models\Location;

class MqttSubscriber extends Command
{
    protected $signature = 'mqtt:subscribe';
    protected $description = 'Subscribe to MQTT broker and listen for beacon data';

    protected $gatewayLocationMap = [
        '40915187ded4' => 1,
        '409151b99f40' => 2,
    ];

    // Store latest RSSI from each gateway per beacon MAC
    protected $beaconRssi = [];

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

        foreach ($data['data'] as $beacon) {
            $mac = strtoupper(str_replace(':', '', $beacon['mac'] ?? ''));
            $rssi = $beacon['rssi'] ?? 0;

            $employee = Employee::whereRaw('UPPER(REPLACE(mac_address, ":", "")) = ?', [$mac])->first();
            if (!$employee) continue;

            // Store latest RSSI for this beacon from this gateway
            if (!isset($this->beaconRssi[$mac])) {
                $this->beaconRssi[$mac] = [
                    '40915187ded4' => null,
                    '409151b99f40' => null,
                ];
            }

            $this->beaconRssi[$mac][$gatewayMac] = $rssi;

            $gw1 = $this->beaconRssi[$mac]['40915187ded4'];
            $gw2 = $this->beaconRssi[$mac]['409151b99f40'];

            // Show both gateway RSSI
            $this->info("--------------------------------------------------");
            $this->info("Employee : {$employee->name}");
            $this->info("GW1 (Workshop First Floor)       : " . ($gw1 ?? 'N/A') . " dBm");
            $this->info("GW2 (Meeting Room Second Floor)  : " . ($gw2 ?? 'N/A') . " dBm");

            // Only predict if we have RSSI from both gateways
            if ($gw1 !== null && $gw2 !== null) {
                $predicted = $this->predictLocation($gw1, $gw2);

                if ($predicted) {
                    $location = Location::where('name', $predicted['location'])->first();

                    if ($location) {
                        PresenceLog::create([
                            'employee_id' => $employee->id,
                            'location_id' => $location->id,
                            'rssi'        => $rssi,
                            'detected_at' => now(),
                        ]);

                        $this->info("Predicted: {$predicted['location']} (nearest: {$predicted['spot']})");
                    }
                }
            }

            $this->info("--------------------------------------------------");
        }
    }

    protected function predictLocation(int $gw1, int $gw2): ?array
    {
        $fingerprints = Fingerprint::all();

        if ($fingerprints->isEmpty()) return null;

        $distances = $fingerprints->map(function ($fp) use ($gw1, $gw2) {
            $distance = sqrt(
                pow($gw1 - $fp->gateway_1_rssi, 2) +
                pow($gw2 - $fp->gateway_2_rssi, 2)
            );
            return [
                'spot'     => $fp->spot_name,
                'location' => $fp->location_name,
                'distance' => $distance,
            ];
        });

        $k = 3;
        $nearest = $distances->sortBy('distance')->take($k);

        $votes = $nearest->groupBy('location')
            ->map(fn($group) => $group->count())
            ->sortDesc();

        return [
            'location' => $votes->keys()->first(),
            'spot'     => $nearest->first()['spot'],
        ];
    }
}