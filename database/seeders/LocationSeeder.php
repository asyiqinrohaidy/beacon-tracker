<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            ['name' => 'Reception', 'description' => 'Main entrance area'],
            ['name' => 'Meeting Room A', 'description' => 'Ground floor meeting room'],
            ['name' => 'Meeting Room B', 'description' => 'First floor meeting room'],
            ['name' => 'Pantry', 'description' => 'Kitchen and break area'],
            ['name' => 'HR Office', 'description' => 'Human Resources department'],
            ['name' => 'IT Room', 'description' => 'Information Technology department'],
        ];

        foreach ($locations as $location) {
            Location::create($location);
        }
    }
}