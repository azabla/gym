<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call individual seeders
        $this->call([
            UserSeeder::class,
            PackageSeeder::class,
            MemberPaymentSeeder::class,
        ]);

        // Optionally, you can create additional seeders for other models
        // $this->call(OtherModelSeeder::class);
    }
}
