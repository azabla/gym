<?php

namespace App\Filament\Traits;

use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Carbon\Carbon;

trait CalcPayDateRanges{

    protected static function calcPayDateRanges(
        Set $set,
        Get $get,
        string $startingDatePath,
        string $durationValuePath,
        string $durationUnitPath,
        string $outputFromPath,
        string $outputUntilPath,
        int $defaultDuration = 1,
        string $defaultUnit = 'month'
        
    ): void {

        $startingDate = $get($startingDatePath) ?? now();
        $duration = (int)($get($durationValuePath) ?? $defaultDuration);
        $durationUnit = $get($durationUnitPath) ?? $defaultUnit;

        // 
        
        if(!$startingDate){
            return ;
        }

        $from = Carbon::parse($startingDate);
        $until = $from->copy();

        match($durationUnit){
            'day' => $until->addDays($duration),
            'week'  => $until->addWeeks($duration),
            'month' => $until->addMonths($duration),
            'year'  => $until->addYears($duration),
            default => $until->addMonths($duration)
        };

        // dd($until);
        $set($outputFromPath, $from->toDateString());
        $set($outputUntilPath, $until->toDateString());
    }
}