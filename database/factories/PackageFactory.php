<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Package>
 */
class PackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unit = $this->faker->randomElement(['day', 'week', 'month', 'year']);

        $price = match ($unit) {
            'day' => 100,
            'week' => 400,
            'month' => 900,
            'year' => 5000,
            default => 0, // Optional: handle unexpected values
        };
        return [
            'name'          => $this->faker->unique()->word(),
            'description'    => $this->faker->optional()->paragraph(),
            'duration_unit' => $unit,
            'price' => $price,
            'image'          => $this->faker->optional()->imageUrl(640, 480, 'business'),
            'features'       => $this->faker->optional()->paragraph(),
            'status'         => $this->faker->randomElement(['active','inactive']),
        ];
    }
}
