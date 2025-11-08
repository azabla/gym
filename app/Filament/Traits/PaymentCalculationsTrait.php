<?php

namespace App\Filament\Traits;

use App\Models\Member;
use App\Models\Package;
use Carbon\Carbon;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Illuminate\Support\Facades\Log;

trait PaymentCalculationsTrait
{
    // CORE CALCULATION METHODS 
    
    /**
     * Core method to calculate amount - works with both models and form data
     */
    protected static function calculateCoreAmount(
        ?Package $package = null,
        ?int $durationValue = null,
        ?Member $member = null
    ): float {
        // If member is provided, use their package and duration
        if ($member && $member->package && $member->duration_value) {
            $package = $member->package;
            $durationValue = $member->duration_value;
        }

        // Validate inputs
        if (!$package || !$durationValue || $durationValue <= 0) {
            return 0.0;
        }

        return $package->price * $durationValue;
    }

    /**
     * Core method to determine valid from date - works with both models and form data
     */
    protected static function determineCoreValidFromDate(
        ?Member $member = null,
        ?string $memberId = null
    ): Carbon {
        $today = now()->startOfDay();

        // If we have a member model, use it directly
        if ($member) {
            $currentExpiry = $member->valid_until ? Carbon::parse($member->valid_until) : null;
            
            // Only add day if expiry is in the future AND we want to start AFTER expiry
            if ($currentExpiry && $currentExpiry->isFuture()) {
                // Start from the day after expiry (not including expiry day)
                return $currentExpiry->addDay()->startOfDay();
            }
            return $today;
        }

        // If we only have member ID, fetch the member
        if ($memberId) {
            $member = Member::find($memberId);
            if ($member && $member->valid_until) {
                $expiryDate = Carbon::parse($member->valid_until);
                if ($expiryDate->isFuture()) {
                    return $expiryDate->addDay()->startOfDay();
                }
            }
        }

        return $today;
    }

    /**
     * Core method to calculate valid until date - works with both models and form data
     */
    protected static function calculateCoreValidUntil(
        Carbon $validFrom,
        ?Package $package = null,
        ?int $durationValue = null,
        ?Member $member = null
    ): Carbon {
        $validUntil = clone $validFrom;

        // If member is provided, use their package and duration
        if ($member && $member->package && $member->duration_value) {
            $package = $member->package;
            $durationValue = $member->duration_value;
        }

        // Validate inputs
        if (!$package || !$durationValue || $durationValue <= 0) {
            return $validUntil;
        }

        $durationUnit = strtolower(trim($package->duration_unit ?? 'day'));
        $originalUnit = $durationUnit;
        $originalDuration = $durationValue;

        // Debug logging
        Log::info('🔄 Starting duration calculation', [
            'valid_from' => $validFrom->format('Y-m-d H:i:s'),
            'package_id' => $package->id ?? 'null',
            'package_name' => $package->name ?? 'null',
            'duration_unit' => $durationUnit,
            'duration_value' => $durationValue,
            'member_id' => $member?->id ?? 'null'
        ]);

        // Work with pure dates (no time) for calculation
        $baseDate = $validFrom->copy()->startOfDay();
        $resultDate = $baseDate->copy();

        switch ($durationUnit) {
            case 'year':
            
                $resultDate->addYears($durationValue)->subDay();
                break;
                
            case 'month':
            
                $resultDate->addMonths($durationValue)->subDay();
                break;
                
            case 'week':
            
                
                $resultDate->addWeeks($durationValue )->subDay();
                break;
                
            case 'day':
            
                $resultDate->addDays($durationValue)->subDay();
                break;
                
            default:
                // Handle common variations
                if (str_contains($durationUnit, 'week')) {
                    $resultDate->addWeeks($durationValue )->subDay();
                } elseif (str_contains($durationUnit, 'month')) {
                    $resultDate->addMonths($durationValue)->subDay();
                } elseif (str_contains($durationUnit, 'year')) {
                    $resultDate->addYears($durationValue)->subDay();
                } else {
                    $resultDate->addDays($durationValue)->subDay();
                }
                break;
        }

        // Set to end of day for database storage (full day access)
        $result = $resultDate->endOfDay();
        
        // Calculate actual duration for debugging
        $actualDurationDays = $baseDate->diffInDays($result->copy()->startOfDay()) + 1;
        
        // Debug logging
        Log::info('✅ Duration calculation complete', [
            'base_date' => $baseDate->format('Y-m-d'),
            'result_date' => $resultDate->format('Y-m-d'),
            'valid_until' => $result->format('Y-m-d H:i:s'),
            'expected_duration' => "{$durationValue} {$durationUnit}(s)",
            'actual_duration_days' => $actualDurationDays,
            'calculation_method' => "add duration then subtract 1 day",
        ]);

        return $result;
    }

    //FORM-BASED WRAPPERS 
    
    /**
     * Calculate amount for Filament forms using core method
     */
    protected static function calculateAmount(Set $set, Get $get): void
    {
        $packageId = $get('package_id');
        $durationValue = (int) $get('duration_value');
        $package = $packageId ? Package::find($packageId) : null;

        $amount = self::calculateCoreAmount($package, $durationValue);
        $set('amount', $amount);
    }

    /**
     * Determine valid from date for Filament forms using core method
     */
    protected static function determineValidFromDate(Set $set, Get $get): void
    {
        $memberId = $get('member_id');
        $validFrom = self::determineCoreValidFromDate(memberId: $memberId);
        $set('valid_from', $validFrom->format('Y-m-d'));
    }

    /**
     * Calculate valid until date for Filament forms using core method
     */
    protected static function calculateValidUntil(Set $set, Get $get): void
    {
        $packageId = $get('package_id');
        $validFrom = $get('valid_from');
        $durationValue = (int) ($get('duration_value') ?? 1);

        if (!$packageId || !$validFrom || $durationValue <= 0) {
            $set('valid_until', null);
            return;
        }

        $package = Package::find($packageId);
        $validFromDate = Carbon::parse($validFrom)->startOfDay();

        $validUntil = self::calculateCoreValidUntil($validFromDate, $package, $durationValue);
        $set('valid_until', $validUntil->format('Y-m-d'));
    }

    //  MODEL-BASED WRAPPERS 
    
    /**
     * Calculate payment amount for member model using core method
     */
    protected static function calculatePaymentAmountForMember(Member $member): float
    {
        return self::calculateCoreAmount(member: $member);
    }

    /**
     * Determine valid from date for member model using core method
     */
    protected static function determineValidFromDateForMember(Member $member): Carbon
    {
        return self::determineCoreValidFromDate(member: $member);
    }

    /**
     * Calculate valid until date for member model using core method
     */
    protected static function calculateValidUntilForMember(Member $member, Carbon $validFrom): Carbon
    {
        return self::calculateCoreValidUntil($validFrom, member: $member);
    }
}