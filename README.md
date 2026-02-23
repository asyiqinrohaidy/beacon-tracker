# Beacon Tracker — Laravel Backend

A real-time IoT employee location tracking system built with Laravel, MQTT, and Bluetooth beacons.

## Tech Stack
- **Laravel 12** — REST API backend
- **MySQL** — Database
- **MQTT (Mosquitto)** — IoT message broker
- **MokoSmart H8** — Bluetooth BLE beacon
- **php-mqtt/laravel-client** — MQTT subscriber

## Architecture
```
H8 Beacon → Gateway → MQTT Broker → Laravel Subscriber → MySQL → React Dashboard
```

## API Endpoints
| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/beacon/data` | Receive beacon detection data |
| GET | `/api/presence/current` | Get current employee locations |
| GET | `/api/presence/logs` | Get full presence history |
| GET | `/api/locations` | Get all office locations |
| GET | `/api/employees` | Get all employees |

## Database Schema
- **locations** — Office rooms/areas
- **employees** — Employee info + beacon MAC address
- **presence_logs** — Beacon detection records (who, where, when)

## Installation

1. Clone the repository
```bash
git clone https://github.com/asyiqinrohaidy/beacon-tracker.git
cd beacon-tracker
```

2. Install dependencies
```bash
composer install
```

3. Set up environment
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure `.env` with your database and MQTT settings
```env
DB_DATABASE=beacon_tracker
DB_USERNAME=root
DB_PASSWORD=

MQTT_HOST=127.0.0.1
MQTT_PORT=1883
MQTT_CLIENT_ID=beacon-tracker
```

5. Run migrations and seed data
```bash
php artisan migrate --seed
```

6. Start the server
```bash
php artisan serve
```

7. Start MQTT subscriber
```bash
php artisan mqtt:subscribe
```

## Testing with Simulated Data
Make sure Mosquitto is running, then publish a test message:
```bash
mosquitto_pub -h 127.0.0.1 -p 1883 -t "beacon/data" -m "{\"mac_address\":\"C1DCC29A8D6A\",\"location_id\":1,\"rssi\":-75}"
```

## 🔗 Frontend
React dashboard: [beacon-dashboard](https://github.com/asyiqinrohaidy/beacon-dashboard)
