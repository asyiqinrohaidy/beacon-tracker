<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            ['name' => 'First Floor', 'description' => 'First floor area (Workshop, Office, Meeting Room)'],
            ['name' => 'Second Floor', 'description' => 'Second floor area (Pantry, Meeting Room B)'],
        ];

        foreach ($locations as $location) {
            Location::firstOrCreate(['name' => $location['name']], $location);
        }
    }
}