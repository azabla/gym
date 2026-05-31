<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MemberCreationService
{
    public function create(array $data): Member
    {
        try {

            return DB::transaction(function () use ($data) {

                $addonIds = $data['addons'] ?? [];

                unset($data['addons']);

                if (empty($data['package_id'])) {
                    throw new \InvalidArgumentException(
                        'Package is required.'
                    );
                }

                $package = Package::findOrFail(
                    $data['package_id']
                );

                $userData = $data['user'] ?? [];

                unset($data['user']);

                if (blank($userData['name'] ?? null)) {
                    throw new \InvalidArgumentException(
                        'Member name is required.'
                    );
                }

                if (blank($userData['password'] ?? null)) {
                    $userData['password'] = Hash::make(
                        Str::random(12)
                    );
                }

                $user = User::create($userData);

                $user->assignRole('member');

                $startingDate = Carbon::parse(
                    $data['starting_date'] ?? now()
                );

                $durationValue = (int) (
                    $data['duration_value'] ?? 1
                );

                $validUntil = match ($package->duration_unit) {
                    'day'   => $startingDate->copy()->addDays($durationValue),
                    'week'  => $startingDate->copy()->addWeeks($durationValue),
                    'month' => $startingDate->copy()->addMonths($durationValue),
                    'year'  => $startingDate->copy()->addYears($durationValue),
                    default => $startingDate->copy()->addMonths($durationValue),
                };

                $memberData = $data;

                $memberData['user_id'] = $user->id;
                $memberData['valid_from'] = $startingDate->toDateString();
                $memberData['valid_until'] = $validUntil->toDateString();

                $member = Member::create(
                    $memberData
                );

                $pivotData = [];

                foreach ($addonIds as $addonId) {

                    $pivotData[$addonId] = [
                        'starts_at' => $member->valid_from,
                        'ends_at' => $member->valid_until,
                    ];
                }

                $member->addons()->sync(
                    $pivotData
                );

                $member->load([
                    'package',
                    'addons',
                ]);

                $addonTotal =
                    $member->addons->sum('price');

                $total =
                    $member->package->price +
                    $addonTotal;

                Payment::create([
                    'member_id' => $member->id,
                    'package_id' => $member->package_id,
                    'amount' => $total,
                    'payment_method' => 'cash',
                    'payment_date' => now(),
                    'transaction_id' => $this->generateTransactionId(),
                    'valid_from' => $member->valid_from,
                    'valid_until' => $member->valid_until,
                    'duration_value' => $member->duration_value,
                    'status' => 'completed',
                    'notes' => 'Initial membership payment',

                    'addons' => $member->addons
                        ->map(fn ($addon) => [
                            'id' => $addon->id,
                            'name' => $addon->name,
                            'price' => $addon->price,
                        ])
                        ->values()
                        ->toArray(),
                ]);

                return $member->fresh([
                    'user',
                    'package',
                    'addons',
                    'payments',
                ]);
            });

        } catch (Throwable $e) {

            Log::error(
                'Member creation failed',
                [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            throw $e;
        }
    }

    protected function generateTransactionId(): string
    {
        do {

            $id =
                'TXN-' .
                strtoupper(Str::random(10));

        } while (
            Payment::where(
                'transaction_id',
                $id
            )->exists()
        );

        return $id;
    }
}