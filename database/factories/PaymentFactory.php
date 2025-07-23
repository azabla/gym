<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'member_id' => null,
            'package_id' => null,
            'amount' => 0,
            'payment_method' => $this->faker->randomElement(['cash', 'online']),
            'payment_date' => $this->faker->optional()->date(),
            'transaction_id' => Str::uuid(),
            'valid_from' => null,
            'valid_until' => null,
            'notes' => $this->faker->optional()->text(),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
        ];
    }

    public function configure()
    {
        return $this->afterMaking(function (Payment $payment) {
            // // Get or create user
            // $user = User::where('role', 'member')->inRandomOrder()->first()
            //     ?? User::factory()->create(['role' => 'member']);

            // Get or create member for user
            // $member = Member::firstWhere('user_id', $user->id)
            //     ?? Member::factory()->create(['user_id' => $user->id]);

            // Get or create package
            $package = $payment->member->package;
            $member = $payment->member;
            // Determine the new payment's valid_from
            $lastPayment = $member->payments()->latest('valid_until')->first();
            $start = Carbon::parse($lastPayment ? $lastPayment->valid_until : now());

            // Determine valid_until based on package duration
            $durationValue = $package->duration_value ?? 1;
            $unit = $package->duration_unit ?? 'month';

            $end = match ($unit) {
                'day' => $start->copy()->addDays($durationValue),
                'week' => $start->copy()->addWeeks($durationValue),
                'month' => $start->copy()->addMonths($durationValue),
                'year' => $start->copy()->addYears($durationValue),
                default => $start->copy()->addDays(30),
            };

            // Assign to payment
            $payment->package_id = $package->id;
            $payment->amount = $package->price * $member->duration_value ?? $this->faker->randomFloat(2, 100, 1000);
            $payment->valid_from = $start;
            $payment->valid_until = $end;
        })->afterCreating(function (Payment $payment) {
            // Update member's valid_from and valid_until to reflect latest payment
            $payment->member->update([
                'valid_from' => $payment->valid_from,
                'valid_until' => $payment->valid_until,
            ]);
        });
    }
}
