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

    public function handle()
    {
        $this->info('Connecting to MQTT broker...');

        MQTT::connection()->subscribe('beacon/data', function (string $topic, string $message) {
            $this->info("Received: $message");

            $data = json_decode($message, true);

            if (!isset($data['mac_address'], $data['location_id'], $data['rssi'])) {
                $this->warn('Invalid data received');
                return;
            }

            $employee = Employee::where('mac_address', strtoupper($data['mac_address']))->first();

            if (!$employee) {
                $this->warn('Unknown beacon: ' . $data['mac_address']);
                return;
            }

            PresenceLog::create([
                'employee_id' => $employee->id,
                'location_id' => $data['location_id'],
                'rssi'        => $data['rssi'],
                'detected_at' => now(),
            ]);

            $this->info('Logged: ' . $employee->name . ' at location ' . $data['location_id']);
        }, 0);

        MQTT::connection()->loop(true);
    }
}