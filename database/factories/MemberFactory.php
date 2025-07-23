<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Pick a random user (or create one if none exists)
        $user = User::where('role', 'member')->inRandomOrder()->first() ?? User::factory()->create(['role' => 'member']);

        // Optionally pick a package (or leave null)
        $package = Package::inRandomOrder()->first() ?? Package::factory()->create();

        // Define realistic start/validity dates
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
            // Set the payment's valid_from and valid_until to match the payments's dates
            $member->update([
                'duration_value' => match ($unit) {
                'day'   => $this->faker->numberBetween(1, 30),
                'week'  => $this->faker->numberBetween(1, 4),
                'month' => $this->faker->numberBetween(1, 12),
                'year'  => $this->faker->numberBetween(1, 5),
                },
            ]);
        });
    }
}
