<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberFactory extends Factory
{
    public function definition(): array
    {
        // 1. Find a user who HAS the 'member' role via Shield/Spatie
        // If none exist, create a new user.
        $user = User::role('member')->inRandomOrder()->first() 
                ?? User::factory()->create();

        // 2. Ensure the user definitely has the role (in case a new one was created)
        if (!$user->hasRole('member')) {
            $user->assignRole('member');
        }

        $package = Package::inRandomOrder()->first() ?? Package::factory()->create();

        $start = $this->faker->dateTimeBetween('-3 year', 'now');
        $validFrom = $start;
        $validUntil = (clone $validFrom)->modify('+6 months');

        return [
            'user_id'                 => $user->id,
            'package_id'              => $package->id,
            'starting_date'           => $start->format('Y-m-d'),
            'valid_from'              => $validFrom->format('Y-m-d'),
            'valid_until'             => $validUntil->format('Y-m-d'),
            'status'                  => $this->faker->randomElement(['active', 'inactive', 'suspended']),
            'emergency_contact_name'  => $this->faker->name(),
            'emergency_contact_phone' => $this->faker->phoneNumber(),
            'membership_id'           => strtoupper($this->faker->bothify('MEM-####-???')),
            'notes'                   => $this->faker->optional()->sentence(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Member $member) {
            $unit = $member->package->duration_unit ?? 'month';
            
            $member->update([
                'duration_value' => match ($unit) {
                    'day'   => $this->faker->numberBetween(1, 30),
                    'week'  => $this->faker->numberBetween(1, 4),
                    'month' => $this->faker->numberBetween(1, 12),
                    'year'  => $this->faker->numberBetween(1, 5),
                    default => 1,
                },
            ]);
        });
    }
}