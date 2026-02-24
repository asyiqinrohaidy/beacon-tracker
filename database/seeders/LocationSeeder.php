<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            ['name' => 'Workshop First Floor', 'description' => 'First floor workshop area'],
            ['name' => 'Meeting Room Second Floor', 'description' => 'Second floor meeting room'],
        ];

        foreach ($locations as $location) {
            Location::create($location);
        }
    }
}