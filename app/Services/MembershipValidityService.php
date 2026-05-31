<?php

namespace App\Services;

use Carbon\Carbon;
use InvalidArgumentException;

class MembershipValidityService
{
    public function calculate(
        string|Carbon $startingDate,
        int $duration,
        string $durationUnit
    ): array {

        if ($duration <= 0) {
            throw new InvalidArgumentException(
                'Duration must be greater than zero.'
            );
        }

        $validFrom = Carbon::parse(
            $startingDate
        );

        $validUntil = match ($durationUnit) {

            'day'
                => $validFrom->copy()
                    ->addDays($duration),

            'week'
                => $validFrom->copy()
                    ->addWeeks($duration),

            'month'
                => $validFrom->copy()
                    ->addMonths($duration),

            'year'
                => $validFrom->copy()
                    ->addYears($duration),

            default
                => throw new InvalidArgumentException(
                    "Invalid duration unit: {$durationUnit}"
                ),
        };

        return [
            'valid_from' => $validFrom->toDateString(),
            'valid_until' => $validUntil->toDateString(),
        ];
    }
}