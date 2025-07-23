<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MemberPaymentSeeder extends Seeder
{
    
    public function run(): void
    {
        // 1. Create 50 users with role "member"
        $users = User::factory()->count(50)->create([
            'role' => 'member',
        ]);

        // 2. Randomly pick 30 users to become members
        $memberUsers = $users->random(50);

        // 3. For each member user, create a member and payment history
        $memberUsers->each(function ($user) {
            // Create member
            $member = Member::factory()->create([
                'user_id' => $user->id,
            ]);

            // Random payment count: 1, 3, or 5
            $paymentCount = collect([1, 3, 5])->random();

            // Create payments with user and member relationship
            Payment::factory()
                ->count($paymentCount)
                ->create([
                    'user_id' => $user->id,
                    'member_id' => $member->id,
                ]);
        });
    }
}
