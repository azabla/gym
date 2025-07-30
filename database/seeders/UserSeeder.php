<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {User::factory()->create([
            'name' => 'Admin User',
            
        // Create Admin User
        'email' => 'admin@example.com',
            'role' => 'admin',
            'password' => Hash::make('password123'), 
        ]);

        // Create Cashiers
        User::factory()->count(2)->create([
            'role' => 'cashier',
        ]);
    }
}
