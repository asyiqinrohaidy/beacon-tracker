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
        '40915187ded4' => 1, // GW1 - Meeting Room A (First Floor)
        '409151888184' => 2, // GW2 - Office (First Floor)
        '409151b99f40' => 3, // GW3 - Meeting Room B (Second Floor)
    ];

    protected $beaconRssi = [];
    protected $rssiHistory = [];
    protected $historySize = 5;

    protected $lastSeenAt = [];
    protected $offlineThresholdSeconds = 30;

    public function handle()
    {
        $this->info('Connecting to MQTT broker...');
        $this->info('Listening to ALL gateways via wildcard...');

        MQTT::connection()->subscribe('/MK107/+/send', function (string $topic, string $message) {
            preg_match('/\/MK107\/([^\/]+)\/send/', $topic, $matches);
            $gatewayMac = $matches[1] ?? null;
            if (!$gatewayMac) return;

            if (!in_array($gatewayMac, array_keys($this->gatewayLocationMap))) {
                $this->info("🆕 NEW GATEWAY DETECTED: {$gatewayMac}");
            }

            $this->checkAndMarkOffline();
            $this->processMessage($gatewayMac, $message);
        }, 0);

        MQTT::connection()->loop(true);
    }

    protected function checkAndMarkOffline()
    {
        $now = time();

        foreach ($this->lastSeenAt as $mac => $lastSeen) {
            if (($now - $lastSeen) > $this->offlineThresholdSeconds) {
                $employee = Employee::whereRaw('UPPER(REPLACE(mac_address, ":", "")) = ?', [$mac])->first();
                if ($employee && $employee->is_online) {
                    $employee->update(['is_online' => false]);
                    $this->info("⚠ {$employee->name} marked OFFLINE (no signal for {$this->offlineThresholdSeconds}s)");
                }
            }
        }
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

            $employee->update([
                'is_online' => true,
                'last_seen' => now(),
            ]);

            $this->lastSeenAt[$mac] = time();

            if (!isset($this->beaconRssi[$mac])) {
                $this->beaconRssi[$mac] = [];
            }

            if (!isset($this->rssiHistory[$mac])) {
                $this->rssiHistory[$mac] = [];
            }

            if (!isset($this->rssiHistory[$mac][$gatewayMac])) {
                $this->rssiHistory[$mac][$gatewayMac] = [];
            }

            $this->rssiHistory[$mac][$gatewayMac][] = $rssi;
            if (count($this->rssiHistory[$mac][$gatewayMac]) > $this->historySize) {
                array_shift($this->rssiHistory[$mac][$gatewayMac]);
            }

            $avgRssi = (int) round(array_sum($this->rssiHistory[$mac][$gatewayMac]) / count($this->rssiHistory[$mac][$gatewayMac]));
            $this->beaconRssi[$mac][$gatewayMac] = $avgRssi;

            $gw1 = $this->beaconRssi[$mac]['40915187ded4'] ?? null;
            $gw2 = $this->beaconRssi[$mac]['409151888184'] ?? null;
            $gw3 = $this->beaconRssi[$mac]['409151b99f40'] ?? null;

            $this->info("--------------------------------------------------");
            $this->info("Employee : {$employee->name} [ONLINE]");
            $this->info("GW1 (Meeting Room A) : " . ($gw1 ?? 'N/A') . " dBm (avg of " . count($this->rssiHistory[$mac]['40915187ded4'] ?? []) . " readings)");
            $this->info("GW2 (Office)         : " . ($gw2 ?? 'N/A') . " dBm (avg of " . count($this->rssiHistory[$mac]['409151888184'] ?? []) . " readings)");
            $this->info("GW3 (Meeting Room B) : " . ($gw3 ?? 'N/A') . " dBm (avg of " . count($this->rssiHistory[$mac]['409151b99f40'] ?? []) . " readings)");

            if ($gw1 !== null && $gw2 !== null && $gw3 !== null) {
                $predicted = $this->predictLocation($gw1, $gw2, $gw3);

                if ($predicted) {
                    $location = Location::where('name', $predicted['location'])->first();

                    if ($location) {
                        PresenceLog::create([
                            'employee_id' => $employee->id,
                            'location_id' => $location->id,
                            'spot_name'   => $predicted['spot'],
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

    protected function predictLocation(int $gw1, int $gw2, int $gw3): ?array
    {
        $fingerprints = Fingerprint::all();

        if ($fingerprints->isEmpty()) return null;

        $distances = $fingerprints->map(function ($fp) use ($gw1, $gw2, $gw3) {
            $distance = sqrt(
                pow($gw1 - $fp->gateway_1_rssi, 2) +
                pow($gw2 - $fp->gateway_2_rssi, 2) +
                pow($gw3 - ($fp->gateway_3_rssi ?? $gw3), 2)
            );
            return [
                'spot'     => $fp->spot_name,
                'location' => $fp->location_name,
                'distance' => $distance,
            ];
        });

        $k = 5;
        $nearest = $distances->sortBy('distance')->take($k);

        $weightedVotes = [];
        foreach ($nearest as $neighbor) {
            $location = $neighbor['location'];
            $weight = $neighbor['distance'] > 0 ? 1 / $neighbor['distance'] : 100;

            if (!isset($weightedVotes[$location])) {
                $weightedVotes[$location] = 0;
            }
            $weightedVotes[$location] += $weight;
        }

        arsort($weightedVotes);

        return [
            'location' => array_key_first($weightedVotes),
            'spot'     => $nearest->first()['spot'],
        ];
    }
}