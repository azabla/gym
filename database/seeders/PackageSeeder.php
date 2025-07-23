<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       // Create Packages
        $durations = [
            ['unit' => 'day', 'price' => 200],
            ['unit' => 'week', 'price' => 400],
            ['unit' => 'month', 'price' => 900],
            ['unit' => 'year',  'price' => 5000],
        ];

        foreach ($durations as $d) {
            Package::factory()->create([
                'duration_unit' => $d['unit'],
                'price' => $d['price'],
            ]);
    }
    }
}
