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
        // 1. Create Roles FIRST
        $this->call(ShieldSeeder::class);
        // Call individual seeders
        $this->call([
            UserSeeder::class,
            PackageSeeder::class,
            MemberPaymentSeeder::class,
            ExpenseSeeder::class
        ]);

        // Optionally, you can create additional seeders for other models
        // $this->call(OtherModelSeeder::class);
    }
}
