<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Package;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class MemberUpdateService
{
    public function update(
        Member $member,
        array $data
    ): Member {

        try {

            return DB::transaction(function () use (
                $member,
                $data
            ) {

                $addonIds = $data['addons'] ?? [];

                unset($data['addons']);

                $userData = $data['user'] ?? [];

                unset($data['user']);

                if (! empty($userData)) {

                    if (
                        empty(
                            $userData['password']
                        )
                    ) {

                        unset(
                            $userData['password']
                        );

                    } else {

                        $userData['password']
                            = Hash::make(
                                $userData['password']
                            );
                    }

                    $member->user?->update(
                        $userData
                    );
                }

                $package = Package::findOrFail(
                    $data['package_id']
                    ?? $member->package_id
                );

                $startingDate = Carbon::parse(
                    $data['starting_date']
                    ?? $member->starting_date
                );

                $durationValue = (int) (
                    $data['duration_value']
                    ?? $member->duration_value
                );

                $validUntil = match (
                    $package->duration_unit
                ) {
                    'day'
                        => $startingDate
                            ->copy()
                            ->addDays(
                                $durationValue
                            ),

                    'week'
                        => $startingDate
                            ->copy()
                            ->addWeeks(
                                $durationValue
                            ),

                    'month'
                        => $startingDate
                            ->copy()
                            ->addMonths(
                                $durationValue
                            ),

                    'year'
                        => $startingDate
                            ->copy()
                            ->addYears(
                                $durationValue
                            ),

                    default
                        => $startingDate
                            ->copy()
                            ->addMonths(
                                $durationValue
                            ),
                };

                $data['valid_from']
                    = $startingDate
                        ->toDateString();

                $data['valid_until']
                    = $validUntil
                        ->toDateString();

                $member->update(
                    $data
                );

                $pivotData = [];

                foreach (
                    $addonIds as $addonId
                ) {

                    $pivotData[$addonId] = [
                        'starts_at'
                            => $member->valid_from,

                        'ends_at'
                            => $member->valid_until,
                    ];
                }

                $member->addons()->sync(
                    $pivotData
                );

                return $member->fresh([
                    'user',
                    'package',
                    'addons',
                    'payments',
                ]);
            });

        } catch (Throwable $e) {

            Log::error(
                'Member update failed',
                [
                    'member_id'
                        => $member->id,

                    'message'
                        => $e->getMessage(),

                    'trace'
                        => $e->getTraceAsString(),
                ]
            );

            throw $e;
        }
    }
}