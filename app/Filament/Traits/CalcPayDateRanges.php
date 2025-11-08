<?php

namespace App\Filament\Traits;

use Carbon\Carbon;
use Filament\Forms\Get;
use Filament\Forms\Set;

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

        $startingDate = $get($startingDatePath);
        $duration = (int)($get($durationValuePath) ?? $defaultDuration);
        $durationUnit = $get($durationUnitPath ?? $defaultUnit);

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

        $set($outputFromPath, $from->toDateString());
        $set($outputUntilPath, $until->toDateString());
    }
}

