<?php

namespace App\Services;

use App\Models\Addon;
use App\Models\Member;
use App\Models\Package;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class PaymentService
{
    /**
     * Create a membership payment.
     */
    public function createMembershipPayment(
        Member $member,
        ?int $packageId = null,
        ?array $addonIds = [],
        ?int $durationValue = null,
        string $paymentMethod = 'cash',
        ?string $notes = null,
    ): Payment {

        return DB::transaction(function () use (
            $member,
            $packageId,
            $addonIds,
            $durationValue,
            $paymentMethod,
            $notes
        ) {

            $package = Package::findOrFail(
                $packageId ?? $member->package_id
            );

            $durationValue ??= (
                $member->duration_value ?: 1
            );

            $validFrom = $this->determineValidFromDate(
                $member
            );

            $validUntil = $this->calculateValidUntil(
                $validFrom,
                $package,
                $durationValue
            );

            $addons = collect();

            if (! empty($addonIds)) {

                $addons = Addon::whereIn(
                    'id',
                    $addonIds
                )->get();
            }

            $total = $this->calculateAmount(
                $package,
                $addons,
                $durationValue
            );

            $payment = Payment::create([
                'member_id'       => $member->id,
                'package_id'      => $package->id,
                'amount'          => $total,
                'payment_method'  => $paymentMethod,
                'payment_date'    => now(),
                'transaction_id'  => $this->generateTransactionId(),
                'valid_from'      => $validFrom,
                'valid_until'     => $validUntil,
                'duration_value'  => $durationValue,
                'status'          => 'completed',
                'notes'           => $notes,
                'addons'          => $addons
                    ->map(fn ($addon) => [
                        'id' => $addon->id,
                        'name' => $addon->name,
                        'price' => $addon->price,
                    ])
                    ->values()
                    ->toArray(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Sync recurring addons
            |--------------------------------------------------------------------------
            */

            $recurringAddonIds = $addons
                ->where('is_recurring', true)
                ->pluck('id')
                ->toArray();

            if (! empty($recurringAddonIds)) {

                $pivotData = [];

                foreach ($recurringAddonIds as $addonId) {

                    $pivotData[$addonId] = [
                        'starts_at' => $validFrom,
                        'ends_at'   => $validUntil,
                    ];
                }

                $member
                    ->addons()
                    ->syncWithoutDetaching(
                        $pivotData
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Update member subscription
            |--------------------------------------------------------------------------
            */

            $member->update([
                'package_id'     => $package->id,
                'duration_value' => $durationValue,
                'valid_from'     => $validFrom,
                'valid_until'    => $validUntil,
                'status'         => 'active',
            ]);

            return $payment;
        });
    }

    /**
     * Calculate payment amount.
     */
    public function calculateAmount(
        Package $package,
        $addons,
        int $durationValue
    ): float {

        $packageTotal =
            $package->price * $durationValue;

        $addonTotal =
            $addons->sum('price') * $durationValue;

        return $packageTotal + $addonTotal;
    }

    /**
     * Determine next subscription start.
     */
    public function determineValidFromDate(
        Member $member
    ): string {

        if (
            $member->valid_until &&
            Carbon::parse(
                $member->valid_until
            )->isFuture()
        ) {

            return Carbon::parse(
                $member->valid_until
            )
                ->addDay()
                ->toDateString();
        }

        return now()->toDateString();
    }

    /**
     * Calculate expiry.
     */
    public function calculateValidUntil(
        string $validFrom,
        Package $package,
        int $duration
    ): string {

        $date = Carbon::parse(
            $validFrom
        );

        match ($package->duration_unit) {

            'day'
                => $date->addDays(
                    $duration
                ),

            'week'
                => $date->addWeeks(
                    $duration
                ),

            'month'
                => $date->addMonths(
                    $duration
                ),

            'year'
                => $date->addYears(
                    $duration
                ),

            default
                => $date->addMonths(
                    $duration
                ),
        };

        return $date
        ->subDay()
        ->toDateString();
    }

    /**
     * Unique transaction number.
     */
    public function generateTransactionId(): string
    {
        do {

            $id =
                'TXN-' .
                strtoupper(
                    Str::random(8)
                );

        } while (
            Payment::where(
                'transaction_id',
                $id
            )->exists()
        );

        return $id;
    }
}